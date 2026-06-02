<?php

namespace App\Livewire\Warehouse;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\ItemPhoto;
use App\Services\Accurate\AccurateException;
use App\Services\Accurate\AccurateService;
use App\Services\ActivityLogService;
use App\Services\ItemPhotoService;
use App\Services\Workflow\WarehouseWorkflowService;
use App\Support\Enums\DocumentTypes;
use App\Support\Enums\ItemMatchStatuses;
use App\Support\Enums\UserRoles;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class InputPage extends Component
{
    use WithFileUploads;

    public string $term = '';
    public string $type = ''; // '', 'po', 'pr'

    /** @var array<int, array> */
    public array $results = [];

    public ?string $selectedDocumentId = null;

    /** @var array<string, string> */
    public array $match = [];

    /** @var array<string, string> */
    public array $reasons = [];

    /** @var array<string, mixed> */
    public array $uploads = [];

    /** @var array<string, mixed> */
    public array $replaceUploads = [];

    public function search(AccurateService $accurate): void
    {
        $this->resetErrorBag();
        $this->results = [];

        try {
            $type = $this->type !== '' ? $this->type : null;
            $this->results = $accurate->search($this->term, $type, 5);

            if (count($this->results) === 0) {
                $this->addError('term', 'Dokumen tidak ditemukan.');
            }
        } catch (AccurateException $e) {
            $this->addError('term', $e->getMessage());
        }
    }

    public function choose(string $documentType, string $accurateId, AccurateService $accurate): void
    {
        $this->resetErrorBag();

        try {
            $doc = $accurate->createFromAccurateDetail($documentType, $accurateId);
            $this->selectedDocumentId = $doc->id;
            $this->hydrateFromDatabase();
        } catch (\Throwable $e) {
            $this->addError('term', 'Gagal mengambil detail dokumen dari Accurate.');
        }
    }

    public function setMatch(string $itemId, string $status): void
    {
        $this->resetErrorBag();

        if (! in_array($status, ItemMatchStatuses::all(), true)) {
            return;
        }

        $doc = $this->document();
        if (! $doc || ! $this->isEditableByWarehouse($doc)) {
            abort(403);
        }

        /** @var DocumentItem $item */
        $item = $doc->items()->whereKey($itemId)->firstOrFail();

        $this->match[$itemId] = $status;

        if ($status !== ItemMatchStatuses::TIDAK_SESUAI) {
            $item->match_status = $status;
            $item->warehouse_reason = null;
            $item->save();

            $this->reasons[$itemId] = '';
            return;
        }

        // DB has a check constraint: tidak_sesuai requires a non-empty reason.
        // So we only persist tidak_sesuai when the reason is provided.
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

        $doc = $this->document();
        if (! $doc || ! $this->isEditableByWarehouse($doc)) {
            abort(403);
        }

        /** @var DocumentItem $item */
        $item = $doc->items()->whereKey($itemId)->firstOrFail();

        // If user has chosen "tidak_sesuai" in UI, allow persisting it here
        // together with the reason (to satisfy DB constraint).
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

        $doc = $this->document();
        if (! $doc || ! $this->isEditableByWarehouse($doc)) {
            abort(403);
        }

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
        $item = $doc->items()->whereKey($itemId)->firstOrFail();

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

        $this->uploads[$itemId] = null;
    }

    public function deletePhoto(string $photoId, ItemPhotoService $photos, ActivityLogService $logs): void
    {
        $this->resetErrorBag();

        $doc = $this->document();
        if (! $doc || ! $this->isEditableByWarehouse($doc)) {
            abort(403);
        }

        /** @var ItemPhoto $photo */
        $photo = ItemPhoto::query()
            ->whereKey($photoId)
            ->whereHas('documentItem', fn ($q) => $q->where('document_id', $doc->id))
            ->firstOrFail();

        /** @var \App\Models\User $actor */
        $actor = Auth::user();

        $photos->delete($photo);

        $logs->logUserAction(
            actor: $actor,
            action: 'warehouse_delete_photo',
            payload: [
                'document_id' => $doc->id,
                'item_photo_id' => $photoId,
            ],
            document: $doc,
            previousStatus: $doc->status,
            newStatus: $doc->status,
        );
    }

    public function replacePhoto(string $photoId, ItemPhotoService $photos, ActivityLogService $logs): void
    {
        $this->resetErrorBag();

        $doc = $this->document();
        if (! $doc || ! $this->isEditableByWarehouse($doc)) {
            abort(403);
        }

        $file = $this->replaceUploads[$photoId] ?? null;
        if (! $file) {
            $this->addError('replace_'.$photoId, 'Pilih file pengganti.');
            return;
        }

        /** @var ItemPhoto $old */
        $old = ItemPhoto::query()
            ->whereKey($photoId)
            ->whereHas('documentItem', fn ($q) => $q->where('document_id', $doc->id))
            ->firstOrFail();

        /** @var \App\Models\User $actor */
        $actor = Auth::user();

        $new = $photos->replace($old, $file, $actor);

        $logs->logUserAction(
            actor: $actor,
            action: 'warehouse_replace_photo',
            payload: [
                'document_id' => $doc->id,
                'old_item_photo_id' => $photoId,
                'new_item_photo_id' => $new->id,
            ],
            document: $doc,
            previousStatus: $doc->status,
            newStatus: $doc->status,
        );

        $this->replaceUploads[$photoId] = null;
    }

    public function submit(WarehouseWorkflowService $workflow, ItemPhotoService $photos, ActivityLogService $logs): void
    {
        $this->resetErrorBag();

        $doc = $this->document();
        if (! $doc) {
            $this->addError('submit', 'Pilih dokumen terlebih dahulu.');
            return;
        }

        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        if ($actor->role !== UserRoles::WAREHOUSE) {
            abort(403);
        }

        if (! $this->isEditableByWarehouse($doc)) {
            $this->addError('submit', 'Dokumen tidak bisa diedit.');
            return;
        }

        try {
            // Sync UI state into DB for items (especially tidak_sesuai + reason).
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

            // Upload staged photos (supports multi-file); must happen before workflow submit (it checks photo count).
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

            $workflow->submit($doc, $actor);
            $this->redirectRoute('warehouse.history', navigate: true);
        } catch (\Throwable $e) {
            $this->addError('submit', $e->getMessage());
        }
    }

    public function document(): ?Document
    {
        if (! $this->selectedDocumentId) {
            return null;
        }

        return Document::query()->whereKey($this->selectedDocumentId)->first();
    }

    private function hydrateFromDatabase(): void
    {
        $doc = $this->document();
        if (! $doc) {
            return;
        }

        $items = $doc->items()->get();
        foreach ($items as $item) {
            $this->match[$item->id] = (string) ($item->match_status ?? '');
            $this->reasons[$item->id] = (string) ($item->warehouse_reason ?? '');
        }
    }

    private function isEditableByWarehouse(Document $document): bool
    {
        return $document->status === null;
    }

    public function render()
    {
        $doc = $this->document();

        $items = [];
        if ($doc) {
            $items = $doc->items()
                ->with('photos')
                ->orderBy('created_at')
                ->get();
        }

        return view('livewire.warehouse.input-page', [
            'document' => $doc,
            'items' => $items,
        ])
            ->layoutData([
                'title' => 'Warehouse Input Barang',
                'pageTitle' => 'Input Barang',
            ]);
    }
}
