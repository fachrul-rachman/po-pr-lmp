<?php

namespace App\Livewire\Spv;

use App\Models\Document;
use App\Support\Enums\DocumentStatuses;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class NonValidPage extends Component
{
    public function documents()
    {
        return Document::query()
            ->where('status', DocumentStatuses::SPV_REJECTED)
            ->orderByDesc('spv_processed_at')
            ->paginate(20);
    }

    public function render()
    {
        $docs = $this->documents();

        return view('livewire.spv.non-valid-page', [
            'documents' => $docs,
            'count' => $docs->total(),
        ])
            ->layoutData([
                'title' => 'SPV Non Valid',
                'pageTitle' => 'Non Valid',
            ]);
    }
}
