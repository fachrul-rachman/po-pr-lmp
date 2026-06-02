<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Services\ActivityLogService;
use App\Support\Enums\UserRoles;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class EditPage extends Component
{
    public User $user;

    public string $username = '';
    public string $role = '';
    public string $newPassword = '';

    public bool $showDeleteModal = false;

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->username = $user->username;
        $this->role = $user->role;
    }

    public function save(ActivityLogService $logs): void
    {
        $validated = $this->validate([
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'username')->ignore($this->user->id),
            ],
            'role' => ['required', 'string', 'in:'.implode(',', UserRoles::all())],
            'newPassword' => ['nullable', 'string', 'min:4', 'max:255'],
        ]);

        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        UserRoles::assertValid($actor->role);

        $before = [
            'username' => $this->user->username,
            'role' => $this->user->role,
        ];

        $this->user->username = $validated['username'];
        $this->user->role = $validated['role'];
        if (is_string($validated['newPassword']) && $validated['newPassword'] !== '') {
            $this->user->password = $validated['newPassword']; // hashed cast
        }
        $this->user->save();

        $after = [
            'username' => $this->user->username,
            'role' => $this->user->role,
            'password_changed' => (is_string($validated['newPassword']) && $validated['newPassword'] !== ''),
        ];

        $logs->logUserAction(
            actor: $actor,
            action: 'admin_update_user',
            payload: [
                'updated_user_id' => $this->user->id,
                'before' => $before,
                'after' => $after,
            ],
        );
    }

    public function confirmDelete(): void
    {
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
    }

    public function deleteUser(ActivityLogService $logs): void
    {
        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        UserRoles::assertValid($actor->role);

        $userId = $this->user->id;
        $username = $this->user->username;
        $role = $this->user->role;

        $this->user->delete();

        $logs->logUserAction(
            actor: $actor,
            action: 'admin_delete_user',
            payload: [
                'deleted_user_id' => $userId,
                'deleted_username' => $username,
                'deleted_role' => $role,
            ],
        );

        $this->redirectRoute('admin.users.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.users.edit-page')
            ->layoutData([
                'title' => 'Admin Edit User',
                'pageTitle' => 'Edit User',
            ]);
    }
}
