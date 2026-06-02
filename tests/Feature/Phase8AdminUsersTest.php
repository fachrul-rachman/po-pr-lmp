<?php

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Enums\UserRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use App\Livewire\Admin\Users\CreatePage;
use App\Livewire\Admin\Users\EditPage;

uses(RefreshDatabase::class);

function admin(): User
{
    return User::create([
        'username' => 'admin1',
        'password' => Hash::make('secret'),
        'role' => UserRoles::ADMIN,
    ]);
}

function warehouse(): User
{
    return User::create([
        'username' => 'w1',
        'password' => Hash::make('secret'),
        'role' => UserRoles::WAREHOUSE,
    ]);
}

test('admin can create user and action is logged', function () {
    $actor = admin();

    Livewire::actingAs($actor)
        ->test(CreatePage::class)
        ->set('username', 'newuser')
        ->set('password', 'pass1234')
        ->set('role', UserRoles::FINANCE)
        ->call('save')
        ->assertRedirect(route('admin.users.index'));

    $created = User::query()->where('username', 'newuser')->first();
    expect($created)->not->toBeNull();
    expect($created->role)->toBe(UserRoles::FINANCE);

    expect(ActivityLog::query()->where('action', 'admin_create_user')->count())->toBe(1);
});

test('admin can update user and action is logged', function () {
    $actor = admin();
    $u = User::create([
        'username' => 'u1',
        'password' => Hash::make('secret'),
        'role' => UserRoles::WAREHOUSE,
    ]);

    Livewire::actingAs($actor)
        ->test(EditPage::class, ['user' => $u])
        ->set('username', 'u1x')
        ->set('role', UserRoles::SPV)
        ->set('newPassword', 'newpass')
        ->call('save');

    $u->refresh();
    expect($u->username)->toBe('u1x');
    expect($u->role)->toBe(UserRoles::SPV);
    expect(Hash::check('newpass', $u->password))->toBeTrue();

    expect(ActivityLog::query()->where('action', 'admin_update_user')->count())->toBe(1);
});

test('admin can delete user (soft delete) and action is logged', function () {
    $actor = admin();
    $u = User::create([
        'username' => 'u2',
        'password' => Hash::make('secret'),
        'role' => UserRoles::WAREHOUSE,
    ]);

    Livewire::actingAs($actor)
        ->test(EditPage::class, ['user' => $u])
        ->call('deleteUser')
        ->assertRedirect(route('admin.users.index'));

    expect(User::withTrashed()->where('username', 'u2')->exists())->toBeTrue();
    $trashed = User::withTrashed()->where('username', 'u2')->first();
    expect($trashed->deleted_at)->not->toBeNull();

    expect(ActivityLog::query()->where('action', 'admin_delete_user')->count())->toBe(1);
});

test('admin can reuse a deleted username by restoring the soft-deleted user', function () {
    $actor = admin();

    $u = User::create([
        'username' => 'warehouse',
        'password' => Hash::make('old'),
        'role' => UserRoles::WAREHOUSE,
    ]);
    $u->delete();

    Livewire::actingAs($actor)
        ->test(CreatePage::class)
        ->set('username', 'warehouse')
        ->set('password', 'newpass')
        ->set('role', UserRoles::SPV)
        ->call('save')
        ->assertRedirect(route('admin.users.index'));

    $restored = User::query()->where('username', 'warehouse')->firstOrFail();
    expect($restored->deleted_at)->toBeNull();
    expect($restored->role)->toBe(UserRoles::SPV);
    expect(Hash::check('newpass', $restored->password))->toBeTrue();

    expect(ActivityLog::query()->where('action', 'admin_update_user')->count())->toBeGreaterThanOrEqual(1);
});

test('non-admin cannot access user management routes', function () {
    $w = warehouse();

    $this->actingAs($w)->get('/admin/users')->assertForbidden();
    $this->actingAs($w)->get('/admin/users/create')->assertForbidden();
});
