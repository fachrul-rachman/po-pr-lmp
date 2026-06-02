<?php

use App\Models\User;
use App\Support\Enums\UserRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function makeAnyUser(string $role): User
{
    return User::create([
        'username' => $role.'_u',
        'password' => Hash::make('secret'),
        'role' => $role,
    ]);
}

test('manifest, service worker, and offline fallback are served', function () {
    $manifestPath = public_path('manifest.webmanifest');
    $swPath = public_path('sw.js');
    $offlinePath = public_path('offline.html');

    expect(file_exists($manifestPath))->toBeTrue();
    expect(file_exists($swPath))->toBeTrue();
    expect(file_exists($offlinePath))->toBeTrue();

    $manifest = json_decode((string) file_get_contents($manifestPath), true);
    expect($manifest['name'] ?? null)->toBe('PO PR Validation');
    expect($manifest['short_name'] ?? null)->toBe('PO PR');

    $sw = (string) file_get_contents($swPath);
    expect($sw)->toContain('po-pr-static-');

    $offline = (string) file_get_contents($offlinePath);
    expect($offline)->toContain('Koneksi internet terputus. Silakan coba lagi saat koneksi tersedia.');
});

test('layouts include viewport and manifest link', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('name="viewport"', false)
        ->assertSee('rel="manifest"', false);

    $admin = makeAnyUser(UserRoles::ADMIN);
    $this->actingAs($admin)->get('/admin/documents')
        ->assertOk()
        ->assertSee('name="viewport"', false)
        ->assertSee('rel="manifest"', false);
});
