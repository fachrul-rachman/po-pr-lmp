<?php

namespace App\Livewire\Warehouse;

use App\Models\Document;
use App\Support\Enums\DocumentStatuses;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class HistoryPage extends Component
{
    use WithPagination;

    #[Url(as: 'status')]
    public string $status = '';

    public function documents()
    {
        $q = Document::query()
            ->whereNotNull('warehouse_submitted_at')
            // Non Valid has its own menu.
            ->where('status', '!=', DocumentStatuses::SPV_REJECTED);

        if (trim($this->status) !== '') {
            $q->where('status', trim($this->status));
        }

        return $q
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
