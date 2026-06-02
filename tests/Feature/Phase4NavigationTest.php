<?php

use App\Models\User;
use App\Support\Enums\UserRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function makeUser(string $role): User
{
    return User::create([
        'username' => $role.'_user',
        'password' => Hash::make('secret'),
        'role' => $role,
    ]);
}

test('admin sees only admin menu links', function () {
    $admin = makeUser(UserRoles::ADMIN);

    $html = $this->actingAs($admin)->get('/admin/documents')->assertOk()->getContent();

    expect($html)->toContain('/admin/documents');
    expect($html)->toContain('/admin/users');
    expect($html)->toContain('/admin/logs');
    expect($html)->not->toContain('/warehouse/input');
    expect($html)->not->toContain('/spv/request');
    expect($html)->not->toContain('/finance/request');
    expect($html)->not->toContain('/purchasing/dashboard');
});

test('warehouse sees only warehouse menu links', function () {
    $warehouse = makeUser(UserRoles::WAREHOUSE);

    $html = $this->actingAs($warehouse)->get('/warehouse/history')->assertOk()->getContent();

    expect($html)->toContain('/warehouse/input');
    expect($html)->toContain('/warehouse/history');
    expect($html)->toContain('/warehouse/non-valid');
    expect($html)->not->toContain('/admin/documents');
    expect($html)->not->toContain('/spv/request');
    expect($html)->not->toContain('/finance/request');
    expect($html)->not->toContain('/purchasing/dashboard');
});

test('spv sees only spv menu links', function () {
    $spv = makeUser(UserRoles::SPV);

    $html = $this->actingAs($spv)->get('/spv/request')->assertOk()->getContent();

    expect($html)->toContain('/spv/request');
    expect($html)->toContain('/spv/history');
    expect($html)->toContain('/spv/non-valid');
    expect($html)->toContain('/spv/non-close');
    expect($html)->not->toContain('/admin/documents');
    expect($html)->not->toContain('/warehouse/input');
    expect($html)->not->toContain('/finance/request');
    expect($html)->not->toContain('/purchasing/dashboard');
});

test('finance sees only finance menu links', function () {
    $finance = makeUser(UserRoles::FINANCE);

    $html = $this->actingAs($finance)->get('/finance/request')->assertOk()->getContent();

    expect($html)->toContain('/finance/request');
    expect($html)->toContain('/finance/history');
    expect($html)->not->toContain('/admin/documents');
    expect($html)->not->toContain('/warehouse/input');
    expect($html)->not->toContain('/spv/request');
    expect($html)->not->toContain('/purchasing/dashboard');
});

test('purchasing sees only purchasing menu links', function () {
    $purchasing = makeUser(UserRoles::PURCHASING);

    $html = $this->actingAs($purchasing)->get('/purchasing/dashboard')->assertOk()->getContent();

    expect($html)->toContain('/purchasing/dashboard');
    expect($html)->not->toContain('/admin/documents');
    expect($html)->not->toContain('/warehouse/input');
    expect($html)->not->toContain('/spv/request');
    expect($html)->not->toContain('/finance/request');
});

