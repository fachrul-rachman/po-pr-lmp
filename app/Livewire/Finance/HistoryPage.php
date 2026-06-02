<?php

namespace App\Livewire\Finance;

use App\Models\Document;
use App\Support\Enums\DocumentStatuses;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class HistoryPage extends Component
{
    public function documents()
    {
        return Document::query()
            ->whereIn('status', [DocumentStatuses::FINANCE_REJECTED, DocumentStatuses::FINANCE_CLOSED])
            ->orderByDesc('finance_processed_at')
            ->paginate(20);
    }

    public function render()
    {
        $docs = $this->documents();

        return view('livewire.finance.history-page', [
            'documents' => $docs,
            'count' => $docs->total(),
        ])
            ->layoutData([
                'title' => 'Finance Riwayat',
                'pageTitle' => 'Riwayat',
            ]);
    }
}
