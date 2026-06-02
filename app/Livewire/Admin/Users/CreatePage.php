<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Services\ActivityLogService;
use App\Support\Enums\UserRoles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CreatePage extends Component
{
    public string $username = '';
    public string $password = '';
    public string $role = UserRoles::WAREHOUSE;

    public function save(ActivityLogService $logs): void
    {
        $username = trim($this->username);

        $validated = $this->validate([
            'username' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }

                    $existsActive = User::query()
                        ->where('username', trim($value))
                        ->whereNull('deleted_at')
                        ->exists();

                    if ($existsActive) {
                        $fail('Username sudah digunakan.');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:4', 'max:255'],
            'role' => ['required', 'string', 'in:'.implode(',', UserRoles::all())],
        ]);

        /** @var \App\Models\User $actor */
        $actor = Auth::user();
        UserRoles::assertValid($actor->role);

        // If the username exists in soft-deleted rows, restore it instead of creating a duplicate username.
        $trashed = User::withTrashed()
            ->where('username', $username)
            ->whereNotNull('deleted_at')
            ->first();

        if ($trashed) {
            $before = [
                'username' => $trashed->username,
                'role' => $trashed->role,
                'was_deleted' => true,
            ];

            $trashed->restore();
            $trashed->role = $validated['role'];
            $trashed->password = $validated['password']; // hashed cast
            $trashed->save();

            $logs->logUserAction(
                actor: $actor,
                action: 'admin_update_user',
                payload: [
                    'updated_user_id' => $trashed->id,
                    'before' => $before,
                    'after' => [
                        'username' => $trashed->username,
                        'role' => $trashed->role,
                        'password_changed' => true,
                        'restored' => true,
                    ],
                ],
            );

            $this->redirectRoute('admin.users.index', navigate: true);
            return;
        }

        $user = User::create([
            'username' => $validated['username'],
            'password' => $validated['password'], // hashed cast
            'role' => $validated['role'],
        ]);

        $logs->logUserAction(
            actor: $actor,
            action: 'admin_create_user',
            payload: [
                'created_user_id' => $user->id,
                'created_username' => $user->username,
                'created_role' => $user->role,
            ],
        );

        $this->redirectRoute('admin.users.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.users.create-page')
            ->layoutData([
                'title' => 'Admin Create User',
                'pageTitle' => 'Create User',
            ]);
    }
}
