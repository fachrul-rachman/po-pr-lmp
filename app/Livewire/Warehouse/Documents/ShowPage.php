<?php

namespace App\Livewire\Warehouse\Documents;

use App\Models\Document;
use App\Support\Enums\DecisionTypes;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ShowPage extends Component
{
    public Document $document;

    public bool $showPhotoModal = false;
    public ?string $previewPhotoId = null;

    public function mount(Document $document): void
    {
        $this->document = $document;
    }

    public function previewPhoto(string $photoId): void
    {
        $this->resetErrorBag();
        $this->previewPhotoId = $photoId;
        $this->showPhotoModal = true;
    }

    public function render()
    {
        $doc = $this->document->load([
            'items.photos',
            'decisions.itemReasons.documentItem',
        ]);

        $latestSpvReject = $doc->decisions
            ->where('decision_type', DecisionTypes::SPV_REJECT)
            ->sortByDesc('created_at')
            ->first();

        $spvItemReasons = [];
        if ($latestSpvReject) {
            foreach ($latestSpvReject->itemReasons as $r) {
                $reason = trim((string) ($r->reason ?? ''));
                if ($reason === '') {
                    continue;
                }

                $spvItemReasons[] = [
                    'item_name' => (string) ($r->documentItem?->nama_barang ?? ''),
                    'reason' => $reason,
                ];
            }
        }

        return view('livewire.warehouse.documents.show-page', [
            'document' => $doc,
            'canEdit' => $doc->isEditableByWarehouse(),
            'spvRejectReason' => $latestSpvReject ? (string) ($latestSpvReject->reason ?? '') : null,
            'spvItemReasons' => $spvItemReasons,
        ])
            ->layoutData([
                'title' => 'Warehouse Document Detail',
                'pageTitle' => 'Detail',
            ]);
    }
}
