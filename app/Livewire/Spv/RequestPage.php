<?php

namespace App\Livewire\Spv;

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
            ->where('status', DocumentStatuses::WAREHOUSE_SUBMITTED)
            ->orderByDesc('warehouse_submitted_at')
            ->paginate(20);
    }

    public function render()
    {
        $docs = $this->documents();

        return view('livewire.spv.request-page', [
            'documents' => $docs,
            'count' => $docs->total(),
        ])
            ->layoutData([
                'title' => 'SPV Request',
                'pageTitle' => 'Request',
            ]);
    }
}
