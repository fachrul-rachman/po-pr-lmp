<?php

namespace App\Livewire\Spv;

use App\Models\Document;
use App\Support\Enums\DocumentStatuses;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class NonClosePage extends Component
{
    public function documents()
    {
        return Document::query()
            ->where('status', DocumentStatuses::FINANCE_REJECTED)
            ->orderByDesc('finance_processed_at')
            ->paginate(20);
    }

    public function render()
    {
        $docs = $this->documents();

        return view('livewire.spv.non-close-page', [
            'documents' => $docs,
            'count' => $docs->total(),
        ])
            ->layoutData([
                'title' => 'SPV Non Close',
                'pageTitle' => 'Non Close',
            ]);
    }
}
