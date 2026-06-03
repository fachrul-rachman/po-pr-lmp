<?php

namespace App\Livewire\Warehouse\Documents;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\ItemPhoto;
use App\Services\ActivityLogService;
use App\Services\ItemPhotoService;
use App\Services\Workflow\WarehouseWorkflowService;
use App\Support\Enums\DocumentStatuses;
use App\Support\Enums\ItemMatchStatuses;
use App\Support\Enums\UserRoles;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class EditPage extends Component
{
    use WithFileUploads;

    public Document $document;

    /** @var array<string, string> */
    public array $match = [];

    /** @var array<string, string> */
    public array $reasons = [];

    /** @var array<string, mixed> */
    public array $uploads = [];

    /** @var array<string, mixed> */
    public array $replaceUploads = [];

    public function mount(Document $document): void
    {
        if (! $this->isEditableStatus($document->status)) {
            abort(403);
        }

        $this->document = $document;

        foreach ($document->items as $item) {
            $this->match[$item->id] = (string) ($item->match_status ?? '');
            $this->reasons[$item->id] = (string) ($item->warehouse_reason ?? '');
        }
    }

    private function isEditableStatus(?string $status): bool
    {
        return in_array($status, [
            DocumentStatuses::WAREHOUSE_SUBMITTED,
            DocumentStatuses::SPV_REJECTED,
        ], true);
    }

    private function assertEditable(): Document
    {
        $this->document = $this->document->fresh();
        if (! $this->document) {
            abort(404);
        }

        if (! $this->isEditableStatus($this->document->status)) {
            abort(403);
        }

        return $this->document;
    }

    public function setMatch(string $itemId, string $status): void
    {
        $this->resetErrorBag();

        if (! in_array($status, ItemMatchStatuses::all(), true)) {
            return;
        }

        $this->assertEditable();

        /** @var DocumentItem $item */
        $item = $this->document->items()->whereKey($itemId)->firstOrFail();

        $this->match[$itemId] = $status;

        if ($status !== ItemMatchStatuses::TIDAK_SESUAI) {
            $item->match_status = $status;
            $item->warehouse_reason = null;
            $item->save();

            $this->reasons[$itemId] = '';
            return;
        }

        $reason = (string) ($this->reasons[$itemId] ?? '');
        if (trim($reason) === '') {
            $this->addError('reason_'.$itemId, 'Alasan wajib diisi jika Tidak Sesuai.');
            return;
        }

        $item->match_status = ItemMatchStatuses::TIDAK_SESUAI;
        $item->warehouse_reason = $reason;
        $item->save();
    }

    public function setReason(string $itemId, string $reason): void
    {
        $this->resetErrorBag();

        $this->assertEditable();

        /** @var DocumentItem $item */
        $item = $this->document->items()->whereKey($itemId)->firstOrFail();
        if (($this->match[$itemId] ?? null) !== ItemMatchStatuses::TIDAK_SESUAI && $item->match_status !== ItemMatchStatuses::TIDAK_SESUAI) {
            return;
        }

        if (trim($reason) === '') {
            $this->reasons[$itemId] = $reason;
            $this->addError('reason_'.$itemId, 'Alasan wajib diisi jika Tidak Sesuai.');
            return;
        }

        $item->match_status = ItemMatchStatuses::TIDAK_SESUAI;
        $item->warehouse_reason = $reason;
        $item->save();

        $this->reasons[$itemId] = $reason;
    }

    public function removeStagedUpload(string $itemId, int $index): void
    {
        $this->resetErrorBag();

        $this->assertEditable();

        $current = $this->uploads[$itemId] ?? [];
        if (! is_array($current)) {
            return;
        }

        if (! array_key_exists($index, $current)) {
            return;
        }

        unset($current[$index]);
        $this->uploads[$itemId] = array_values($current);
    }

    public function uploadPhoto(string $itemId, ItemPhotoService $photos, ActivityLogService $logs): void
    {
        $this->resetErrorBag();

        $this->assertEditable();

        $file = $this->uploads[$itemId] ?? null;
        if (! $file) {
            $this->addError('upload_'.$itemId, 'Pilih file foto.');
            return;
        }

        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        if ($actor->role !== UserRoles::WAREHOUSE) {
            abort(403);
        }

        /** @var DocumentItem $item */
        $item = $this->document->items()->whereKey($itemId)->firstOrFail();

        $photo = $photos->upload($item, $file, $actor);

        $logs->logUserAction(
            actor: $actor,
            action: 'warehouse_upload_photo',
            payload: [
                'document_id' => $this->document->id,
                'document_item_id' => $item->id,
                'item_photo_id' => $photo->id,
            ],
            document: $this->document,
            previousStatus: $this->document->status,
            newStatus: $this->document->status,
        );

        $this->uploads[$itemId] = null;
    }

    public function deletePhoto(string $photoId, ItemPhotoService $photos, ActivityLogService $logs): void
    {
        $this->resetErrorBag();

        $this->assertEditable();

        /** @var ItemPhoto $photo */
        $photo = ItemPhoto::query()
            ->whereKey($photoId)
            ->whereHas('documentItem', fn ($q) => $q->where('document_id', $this->document->id))
            ->firstOrFail();

        /** @var \App\Models\User $actor */
        $actor = Auth::user();

        $photos->delete($photo);

        $logs->logUserAction(
            actor: $actor,
            action: 'warehouse_delete_photo',
            payload: [
                'document_id' => $this->document->id,
                'item_photo_id' => $photoId,
            ],
            document: $this->document,
            previousStatus: $this->document->status,
            newStatus: $this->document->status,
        );
    }

    public function replacePhoto(string $photoId, ItemPhotoService $photos, ActivityLogService $logs): void
    {
        $this->resetErrorBag();

        $this->assertEditable();

        $file = $this->replaceUploads[$photoId] ?? null;
        if (! $file) {
            $this->addError('replace_'.$photoId, 'Pilih file pengganti.');
            return;
        }

        /** @var ItemPhoto $old */
        $old = ItemPhoto::query()
            ->whereKey($photoId)
            ->whereHas('documentItem', fn ($q) => $q->where('document_id', $this->document->id))
            ->firstOrFail();

        /** @var \App\Models\User $actor */
        $actor = Auth::user();

        $new = $photos->replace($old, $file, $actor);

        $logs->logUserAction(
            actor: $actor,
            action: 'warehouse_replace_photo',
            payload: [
                'document_id' => $this->document->id,
                'old_item_photo_id' => $photoId,
                'new_item_photo_id' => $new->id,
            ],
            document: $this->document,
            previousStatus: $this->document->status,
            newStatus: $this->document->status,
        );

        $this->replaceUploads[$photoId] = null;
    }

    public function saveChanges(WarehouseWorkflowService $workflow, ItemPhotoService $photos, ActivityLogService $logs): void
    {
        $this->resetErrorBag();

        $doc = $this->assertEditable();

        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        if ($actor->role !== UserRoles::WAREHOUSE) {
            abort(403);
        }

        try {
            // Ensure tidak_sesuai + reason is persisted safely.
            $items = $doc->items()->withCount('photos')->get();
            foreach ($items as $item) {
                $chosen = (string) ($this->match[$item->id] ?? '');
                $reason = (string) ($this->reasons[$item->id] ?? '');

                if ($chosen === ItemMatchStatuses::SESUAI) {
                    if ($item->match_status !== ItemMatchStatuses::SESUAI) {
                        $item->match_status = ItemMatchStatuses::SESUAI;
                        $item->warehouse_reason = null;
                        $item->save();
                    }
                    continue;
                }

                if ($chosen === ItemMatchStatuses::TIDAK_SESUAI) {
                    if (trim($reason) === '') {
                        $this->addError('reason_'.$item->id, 'Alasan wajib diisi jika Tidak Sesuai.');
                        $this->addError('submit', 'Lengkapi alasan untuk item yang Tidak Sesuai.');
                        return;
                    }

                    if ($item->match_status !== ItemMatchStatuses::TIDAK_SESUAI || $item->warehouse_reason !== $reason) {
                        $item->match_status = ItemMatchStatuses::TIDAK_SESUAI;
                        $item->warehouse_reason = $reason;
                        $item->save();
                    }
                    continue;
                }
            }

            // Upload staged photos before resubmit/save (it can affect photo count).
            $items = $doc->items()->withCount('photos')->get();
            foreach ($items as $item) {
                $staged = $this->uploads[$item->id] ?? [];
                if ($staged && ! is_array($staged)) {
                    $staged = [$staged];
                }

                $stagedCount = is_array($staged) ? count($staged) : 0;
                if (((int) $item->photos_count + $stagedCount) < 1) {
                    $this->addError('upload_'.$item->id, 'Minimal 1 foto per item (bisa dari kamera atau upload).');
                    $this->addError('submit', 'Lengkapi foto untuk setiap item.');
                    return;
                }

                if (! is_array($staged) || count($staged) === 0) {
                    continue;
                }

                foreach ($staged as $file) {
                    if (! $file) {
                        continue;
                    }

                    $photo = $photos->upload($item, $file, $actor);

                    $logs->logUserAction(
                        actor: $actor,
                        action: 'warehouse_upload_photo',
                        payload: [
                            'document_id' => $doc->id,
                            'document_item_id' => $item->id,
                            'item_photo_id' => $photo->id,
                        ],
                        document: $doc,
                        previousStatus: $doc->status,
                        newStatus: $doc->status,
                    );
                }

                $this->uploads[$item->id] = [];
            }

            $doc = $doc->fresh();

            if (! $doc) {
                abort(404);
            }

            if ($doc->status === DocumentStatuses::SPV_REJECTED) {
                $workflow->resubmit($doc, $actor);
                $this->redirectRoute('warehouse.history', navigate: true);
                return;
            }

            if ($doc->status === DocumentStatuses::WAREHOUSE_SUBMITTED) {
                $logs->logUserAction(
                    actor: $actor,
                    action: 'warehouse_edit_submitted_document',
                    payload: [
                        'document_id' => $doc->id,
                    ],
                    document: $doc,
                    previousStatus: $doc->status,
                    newStatus: $doc->status,
                );

                $this->redirectRoute('warehouse.documents.show', ['document' => $doc], navigate: true);
                return;
            }

            abort(403);
        } catch (\Throwable $e) {
            $this->addError('submit', $e->getMessage());
        }
    }

    public function render()
    {
        $doc = $this->document->load(['items.photos']);

        return view('livewire.warehouse.documents.edit-page', [
            'document' => $doc,
            'items' => $doc->items,
        ])
            ->layoutData([
                'title' => 'Warehouse Edit Document',
                'pageTitle' => 'Edit',
            ]);
    }
}
