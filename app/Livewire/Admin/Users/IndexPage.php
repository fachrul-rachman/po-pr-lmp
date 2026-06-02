<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Services\ActivityLogService;
use App\Support\Enums\UserRoles;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class IndexPage extends Component
{
    public string $search = '';

    public bool $showDeleteModal = false;
    public ?string $deleteUserId = null;

    public function confirmDelete(string $userId): void
    {
        $this->deleteUserId = $userId;
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deleteUserId = null;
    }

    public function deleteSelected(ActivityLogService $logs): void
    {
        if (! $this->deleteUserId) {
            return;
        }

        /** @var User $actor */
        $actor = Auth::user();
        UserRoles::assertValid($actor->role);

        $user = User::query()->whereKey($this->deleteUserId)->firstOrFail();

        $userId = $user->id;
        $username = $user->username;
        $role = $user->role;

        $user->delete();

        $logs->logUserAction(
            actor: $actor,
            action: 'admin_delete_user',
            payload: [
                'deleted_user_id' => $userId,
                'deleted_username' => $username,
                'deleted_role' => $role,
            ],
        );

        $this->cancelDelete();
    }

    public function render()
    {
        $query = User::query()->orderBy('username');

        $term = trim($this->search);
        if ($term !== '') {
            $query->where('username', 'like', '%'.$term.'%');
        }

        return view('livewire.admin.users.index-page', [
            'users' => $query->paginate(20),
        ])
            ->layoutData([
                'title' => 'Admin Users',
                'pageTitle' => 'Users',
            ]);
    }
}
