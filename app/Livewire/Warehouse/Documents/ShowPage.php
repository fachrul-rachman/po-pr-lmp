<?php

namespace App\Livewire\Warehouse\Documents;

use App\Models\Document;
use App\Support\Enums\DocumentStatuses;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ShowPage extends Component
{
    public Document $document;

    public function mount(Document $document): void
    {
        $this->document = $document;
    }

    public function render()
    {
        $doc = $this->document->load(['items.photos']);

        return view('livewire.warehouse.documents.show-page', [
            'document' => $doc,
            'canEdit' => $doc->status === DocumentStatuses::SPV_REJECTED,
        ])
            ->layoutData([
                'title' => 'Warehouse Document Detail',
                'pageTitle' => 'Detail',
            ]);
    }
}
