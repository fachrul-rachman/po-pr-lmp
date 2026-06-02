<?php

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Support\Enums\ActorRoles;
use App\Support\Enums\DocumentTypes;
use App\Support\Enums\UserRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function makeActor(string $role): User
{
    return User::create([
        'username' => $role.'_actor',
        'password' => Hash::make('secret'),
        'role' => $role,
    ]);
}

function makeDoc(): Document
{
    return Document::create([
        'accurate_id' => 'ACC-1',
        'document_number' => 'PO.TEST.001',
        'document_type' => DocumentTypes::PO,
        'status' => null,
        'accurate_synced_at' => now(),
    ]);
}

test('can write a user action log with JSON payload', function () {
    $svc = new ActivityLogService();
    $actor = makeActor(UserRoles::WAREHOUSE);
    $doc = makeDoc();

    $log = $svc->logUserAction(
        actor: $actor,
        action: 'warehouse_submit',
        payload: ['a' => 1, 'b' => ['c' => true]],
        document: $doc,
        previousStatus: null,
        newStatus: 'warehouse_submitted',
    );

    expect($log)->toBeInstanceOf(ActivityLog::class);
    expect($log->actor_id)->toBe($actor->id);
    expect($log->actor_role)->toBe(UserRoles::WAREHOUSE);
    expect($log->document_id)->toBe($doc->id);
    expect($log->payload)->toBe(['a' => 1, 'b' => ['c' => true]]);
});

test('can write a system action log with null actor_id', function () {
    $svc = new ActivityLogService();
    $doc = makeDoc();

    $log = $svc->logSystemAction(
        action: 'system_status_change',
        payload: ['reason' => 'system generated'],
        document: $doc,
        previousStatus: 'warehouse_submitted',
        newStatus: 'spv_rejected',
    );

    expect($log->actor_id)->toBeNull();
    expect($log->actor_role)->toBe(ActorRoles::SYSTEM);
});

test('activity logs are not editable through normal model flow', function () {
    $svc = new ActivityLogService();
    $actor = makeActor(UserRoles::ADMIN);

    $log = $svc->logUserAction(
        actor: $actor,
        action: 'admin_create_user',
        payload: ['x' => 'y'],
    );

    expect(fn () => $log->update(['action' => 'mutated']))->toThrow(RuntimeException::class);
});

test('activity logs are not deletable through normal model flow', function () {
    $svc = new ActivityLogService();
    $actor = makeActor(UserRoles::ADMIN);

    $log = $svc->logUserAction(
        actor: $actor,
        action: 'admin_create_user',
        payload: ['x' => 'y'],
    );

    expect(fn () => $log->delete())->toThrow(RuntimeException::class);
});

