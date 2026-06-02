<?php

namespace App\Services\Accurate;

use App\Models\Document;
use App\Models\DocumentDecision;
use App\Models\DocumentItem;
use App\Services\ActivityLogService;
use App\Services\ItemPhotoService;
use App\Support\Enums\ActorRoles;
use App\Support\Enums\DecisionTypes;
use App\Support\Enums\DocumentStatuses;
use Illuminate\Support\Facades\DB;

final class AccurateRefreshService
{
    public function __construct(
        private AccurateService $accurate,
        private AccurateDocumentMapper $mapper,
        private ItemPhotoService $photos,
        private ActivityLogService $logs,
    ) {}

    /**
     * Refresh header + items from Accurate, preserving checks/photos only for matching items.
     */
    public function refresh(Document $document): Document
    {
        $type = $document->document_type;
        $accurateId = $document->accurate_id;

        $json = $this->accurate->fetchDetail($type, $accurateId);
        $mapped = $this->mapper->mapDetail($type, $json);

        $incomingDoc = $mapped['document'];
        $incomingItems = $mapped['items'];

        return DB::transaction(function () use ($document, $incomingDoc, $incomingItems) {
            $headerChanged = $this->headerChanged($document, $incomingDoc);

            $sync = $this->syncItems($document, $incomingItems);
            $itemsChanged = (bool) $sync['changed'];

            if (! $headerChanged && ! $itemsChanged) {
                $document->accurate_synced_at = now();
                $document->save();

                $this->logs->logSystemAction(
                    action: 'accurate_refresh_no_change',
                    payload: ['document_id' => $document->id],
                    document: $document,
                    previousStatus: $document->status,
                    newStatus: $document->status,
                );

                return $document->refresh();
            }

            // Apply header updates (do not touch status here).
            $document->fill([
                'tujuan_pembelian' => $incomingDoc['tujuan_pembelian'],
                'dikirim_ke' => $incomingDoc['dikirim_ke'],
                'department' => $incomingDoc['department'],
                'dibuat_oleh' => $incomingDoc['dibuat_oleh'],
                'diminta_oleh' => $incomingDoc['diminta_oleh'],
                'accurate_synced_at' => now(),
            ]);

            $previousStatus = $document->status;
            $newStatus = $this->statusAfterRefreshChange($previousStatus);
            $document->status = $newStatus;
            $document->save();

            if ($itemsChanged) {
                $this->logs->logSystemAction(
                    action: 'system_item_data_replacement',
                    payload: [
                        'document_id' => $document->id,
                        'changed_item_ids' => $sync['changed_item_ids'],
                        'deleted_item_ids' => $sync['deleted_item_ids'],
                    ],
                    document: $document,
                    previousStatus: $previousStatus,
                    newStatus: $newStatus,
                );

                if ((int) $sync['deleted_photo_count'] > 0) {
                    $this->logs->logSystemAction(
                        action: 'system_photo_deletion',
                        payload: [
                            'document_id' => $document->id,
                            'deleted_photo_count' => (int) $sync['deleted_photo_count'],
                        ],
                        document: $document,
                        previousStatus: $previousStatus,
                        newStatus: $newStatus,
                    );
                }
            }

            $this->logs->logSystemAction(
                action: 'accurate_refresh_with_change',
                payload: [
                    'document_id' => $document->id,
                    'header_changed' => $headerChanged,
                    'items_changed' => $itemsChanged,
                ],
                document: $document,
                previousStatus: $previousStatus,
                newStatus: $newStatus,
            );

            if ($previousStatus !== $newStatus) {
                DocumentDecision::create([
                    'document_id' => $document->id,
                    'decision_type' => DecisionTypes::SYSTEM_STATUS_CHANGE,
                    'from_status' => $previousStatus,
                    'to_status' => $newStatus,
                    'reason' => 'Accurate refresh changed stored data.',
                    'actor_id' => null,
                    'actor_role' => ActorRoles::SYSTEM,
                ]);

                $this->logs->logSystemAction(
                    action: 'system_status_change',
                    payload: [
                        'reason' => 'Accurate refresh changed stored data.',
                    ],
                    document: $document,
                    previousStatus: $previousStatus,
                    newStatus: $newStatus,
                );
            }

            return $document->refresh();
        });
    }

    private function headerChanged(Document $document, array $incoming): bool
    {
        return (string) $document->document_number !== (string) $incoming['document_number']
            || (string) $document->document_type !== (string) $incoming['document_type']
            || (string) ($document->tujuan_pembelian ?? '') !== (string) ($incoming['tujuan_pembelian'] ?? '')
            || (string) ($document->dikirim_ke ?? '') !== (string) ($incoming['dikirim_ke'] ?? '')
            || (string) ($document->department ?? '') !== (string) ($incoming['department'] ?? '')
            || (string) ($document->dibuat_oleh ?? '') !== (string) ($incoming['dibuat_oleh'] ?? '')
            || (string) ($document->diminta_oleh ?? '') !== (string) ($incoming['diminta_oleh'] ?? '');
    }

    /**
     * @return array{changed:bool, changed_item_ids:array<int, string>, deleted_item_ids:array<int, string>, deleted_photo_count:int}
     */
    private function syncItems(Document $document, array $incomingItems): array
    {
        $existing = $document->items()->get()->keyBy('accurate_item_id');
        $incoming = collect($incomingItems)->keyBy('accurate_item_id');

        $changed = false;
        $changedItemIds = [];
        $deletedItemIds = [];
        $deletedPhotoCount = 0;

        // Deleted items
        foreach ($existing as $accurateItemId => $oldItem) {
            if (! $incoming->has($accurateItemId)) {
                $deletedPhotoCount += $this->deleteItemPhotos($oldItem);
                $oldItem->delete();
                $changed = true;
                $deletedItemIds[] = (string) $oldItem->id;
            }
        }

        // Added or changed items
        foreach ($incoming as $accurateItemId => $row) {
            if (! is_array($row)) {
                continue;
            }

            /** @var DocumentItem|null $old */
            $old = $existing->get($accurateItemId);

            if (! $old) {
                DocumentItem::create([
                    'document_id' => $document->id,
                    'accurate_item_id' => (string) $row['accurate_item_id'],
                    'nama_barang' => (string) $row['nama_barang'],
                    'keterangan' => $row['keterangan'],
                    'quantity' => $row['quantity'],
                    'satuan' => (string) $row['satuan'],
                    'match_status' => null,
                    'warehouse_reason' => null,
                ]);
                $changed = true;
                $changedItemIds[] = 'new:'.(string) $row['accurate_item_id'];
                continue;
            }

            $fieldsChanged =
                (string) $old->nama_barang !== (string) $row['nama_barang']
                || (string) ($old->keterangan ?? '') !== (string) ($row['keterangan'] ?? '')
                || $this->normalizeNumber($old->quantity) !== $this->normalizeNumber($row['quantity'])
                || (string) $old->satuan !== (string) $row['satuan'];

            if (! $fieldsChanged) {
                continue;
            }

            $deletedPhotoCount += $this->deleteItemPhotos($old);

            $old->fill([
                'nama_barang' => (string) $row['nama_barang'],
                'keterangan' => $row['keterangan'],
                'quantity' => $row['quantity'],
                'satuan' => (string) $row['satuan'],
                'match_status' => null,
                'warehouse_reason' => null,
            ]);
            $old->save();

            $changed = true;
            $changedItemIds[] = (string) $old->id;
        }

        return [
            'changed' => $changed,
            'changed_item_ids' => array_values(array_unique($changedItemIds)),
            'deleted_item_ids' => array_values(array_unique($deletedItemIds)),
            'deleted_photo_count' => $deletedPhotoCount,
        ];
    }

    private function deleteItemPhotos(DocumentItem $item): int
    {
        $photos = $item->photos()->get();
        $count = 0;
        foreach ($photos as $photo) {
            $this->photos->delete($photo);
            $count++;
        }
        return $count;
    }

    private function statusAfterRefreshChange(?string $current): ?string
    {
        if ($current === null) {
            return null;
        }

        return match ($current) {
            DocumentStatuses::WAREHOUSE_SUBMITTED => DocumentStatuses::SPV_REJECTED,
            DocumentStatuses::SPV_APPROVED => DocumentStatuses::FINANCE_REJECTED,
            DocumentStatuses::FINANCE_REJECTED => DocumentStatuses::FINANCE_REJECTED,
            DocumentStatuses::SPV_REJECTED => DocumentStatuses::SPV_REJECTED,
            DocumentStatuses::FINANCE_CLOSED => DocumentStatuses::FINANCE_REJECTED,
            default => $current,
        };
    }

    private function normalizeNumber(mixed $value, int $scale = 4): string
    {
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            return number_format((float) $value, $scale, '.', '');
        }

        return (string) $value;
    }
}
