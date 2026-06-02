<?php

namespace App\Livewire\Admin\Logs;

use App\Models\ActivityLog;
use App\Support\Enums\ActorRoles;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class IndexPage extends Component
{
    use WithPagination;

    public string $search = '';
    public string $actorRole = '';
    public string $action = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedActorRole(): void
    {
        $this->resetPage();
    }

    public function updatedAction(): void
    {
        $this->resetPage();
    }

    public function logs()
    {
        return ActivityLog::query()
            ->with(['actor', 'document'])
            ->when(trim($this->search) !== '', function ($q) {
                $q->forDocumentNumberLike(trim($this->search));
            })
            ->when(trim($this->actorRole) !== '', function ($q) {
                $q->where('actor_role', trim($this->actorRole));
            })
            ->when(trim($this->action) !== '', function ($q) {
                $q->where('action', 'like', '%'.trim($this->action).'%');
            })
            ->latest('created_at')
            ->paginate(20);
    }

    public function render()
    {
        $logs = $this->logs();

        return view('livewire.admin.logs.index-page', [
            'logs' => $logs,
            'count' => $logs->total(),
            'roles' => ActorRoles::all(),
        ])
            ->layoutData([
                'title' => 'Admin Logs',
                'pageTitle' => 'Logs',
            ]);
    }
}
