<?php

namespace App\Livewire\Spv;

use App\Models\Document;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class HistoryPage extends Component
{
    public function documents()
    {
        return Document::query()
            ->whereNotNull('spv_processed_at')
            ->orderByDesc('spv_processed_at')
            ->paginate(20);
    }

    public function render()
    {
        $docs = $this->documents();

        return view('livewire.spv.history-page', [
            'documents' => $docs,
            'count' => $docs->total(),
        ])
            ->layoutData([
                'title' => 'SPV Riwayat',
                'pageTitle' => 'Riwayat',
            ]);
    }
}
