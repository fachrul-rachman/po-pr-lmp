<?php

namespace App\Livewire\Admin\Documents;

use App\Models\Document;
use App\Support\Enums\DocumentStatuses;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class IndexPage extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function documents()
    {
        return Document::query()
            ->when(trim($this->search) !== '', function ($q) {
                $q->where('document_number', 'like', '%'.trim($this->search).'%');
            })
            ->when(trim($this->status) !== '', function ($q) {
                $q->where('status', trim($this->status));
            })
            ->orderByDesc('updated_at')
            ->paginate(20);
    }

    public function render()
    {
        $docs = $this->documents();

        return view('livewire.admin.documents.index-page', [
            'documents' => $docs,
            'count' => $docs->total(),
            'statuses' => DocumentStatuses::all(),
        ])
            ->layoutData([
                'title' => 'Admin Documents',
                'pageTitle' => 'Documents',
            ]);
    }
}
