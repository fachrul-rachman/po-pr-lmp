<?php

namespace App\Livewire\Warehouse;

use App\Models\Document;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class HistoryPage extends Component
{
    public function documents()
    {
        return Document::query()
            ->whereNotNull('warehouse_submitted_at')
            ->orderByDesc('warehouse_submitted_at')
            ->paginate(20);
    }

    public function render()
    {
        $docs = $this->documents();

        return view('livewire.warehouse.history-page', [
            'documents' => $docs,
            'count' => $docs->total(),
        ])
            ->layoutData([
                'title' => 'Warehouse Riwayat',
                'pageTitle' => 'Riwayat',
            ]);
    }
}
