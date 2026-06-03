<?php

namespace App\Livewire\Purchasing\Documents;

use App\Models\Document;
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
        $doc = $this->document->load([
            'items.photos',
            'warehouseSubmittedBy',
            'spvProcessedBy',
            'financeProcessedBy',
            'decisions.actor',
            'decisions.itemReasons.documentItem',
        ]);

        return view('livewire.purchasing.documents.show-page', [
            'document' => $doc,
            'items' => $doc->items,
            'decisions' => $doc->decisions->sortByDesc('created_at')->values(),
        ])
            ->layoutData([
                'title' => 'Purchasing Document Detail',
                'pageTitle' => 'Detail',
            ]);
    }
}
