<?php

use App\Models\User;
use App\Support\Enums\UserRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use App\Livewire\Auth\LoginPage;

uses(RefreshDatabase::class);

test('login page is accessible', function () {
    $this->get('/login')->assertOk();
});

test('login success redirects to dashboard, then role target', function () {
    $user = User::create([
        'username' => 'admin1',
        'password' => Hash::make('secret'),
        'role' => UserRoles::ADMIN,
    ]);

    Livewire::test(LoginPage::class)
        ->set('username', $user->username)
        ->set('password', 'secret')
        ->call('login')
        ->assertRedirect(route('dashboard'));

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect('/admin/documents');
});

test('login failure stays unauthenticated', function () {
    User::create([
        'username' => 'u1',
        'password' => Hash::make('secret'),
        'role' => UserRoles::WAREHOUSE,
    ]);

    Livewire::test(LoginPage::class)
        ->set('username', 'u1')
        ->set('password', 'wrong')
        ->call('login')
        ->assertHasErrors(['username']);

    $this->assertGuest();
});

test('protected routes require login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('user cannot access another role route', function () {
    $warehouse = User::create([
        'username' => 'w1',
        'password' => Hash::make('secret'),
        'role' => UserRoles::WAREHOUSE,
    ]);

    $this->actingAs($warehouse)->get('/admin/documents')->assertForbidden();
});

test('logout ends session', function () {
    $user = User::create([
        'username' => 'w2',
        'password' => Hash::make('secret'),
        'role' => UserRoles::WAREHOUSE,
    ]);

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/login');

    $this->assertGuest();
});
