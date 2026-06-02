<?php

namespace App\Livewire\Finance;

use App\Models\Document;
use App\Support\Enums\DocumentStatuses;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class RequestPage extends Component
{
    public function documents()
    {
        return Document::query()
            ->where('status', DocumentStatuses::SPV_APPROVED)
            ->orderByDesc('spv_processed_at')
            ->paginate(20);
    }

    public function render()
    {
        $docs = $this->documents();

        return view('livewire.finance.request-page', [
            'documents' => $docs,
            'count' => $docs->total(),
        ])
            ->layoutData([
                'title' => 'Finance Request',
                'pageTitle' => 'Request',
            ]);
    }
}
