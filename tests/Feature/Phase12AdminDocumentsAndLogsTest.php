<?php

use App\Livewire\Admin\Documents\ShowPage as AdminDocShowPage;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\DocumentDecision;
use App\Models\DocumentItem;
use App\Models\ItemPhoto;
use App\Models\User;
use App\Support\Enums\DecisionTypes;
use App\Support\Enums\DocumentStatuses;
use App\Support\Enums\DocumentTypes;
use App\Support\Enums\ItemMatchStatuses;
use App\Support\Enums\UserRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function setAccurateConfig2(): void
{
    config()->set('accurate.api_token', 'tok');
    config()->set('accurate.signature_secret', 'sec');
    config()->set('accurate.default_host', 'https://fallback.example');
    config()->set('accurate.timeout_seconds', 5);
    config()->set('accurate.host_cache_ttl_days', 30);
}

function makeAdminUser(string $username = 'admin1'): User
{
    return User::create([
        'username' => $username,
        'password' => Hash::make('secret'),
        'role' => UserRoles::ADMIN,
    ]);
}

function makeWarehouseUser4(string $username = 'w4'): User
{
    return User::create([
        'username' => $username,
        'password' => Hash::make('secret'),
        'role' => UserRoles::WAREHOUSE,
    ]);
}

function makeDocForRefresh(string $status, int $qty = 1): array
{
    $warehouse = makeWarehouseUser4('wh_photo');
    $doc = Document::create([
        'accurate_id' => '22',
        'document_number' => 'PO.001',
        'document_type' => DocumentTypes::PO,
        'status' => $status,
        'accurate_synced_at' => now(),
        'warehouse_submitted_at' => $status === DocumentStatuses::WAREHOUSE_SUBMITTED ? now() : null,
    ]);

    $item = DocumentItem::create([
        'document_id' => $doc->id,
        'accurate_item_id' => '6063',
        'nama_barang' => 'Item A',
        'keterangan' => null,
        'quantity' => $qty,
        'satuan' => 'PCS',
        'match_status' => ItemMatchStatuses::SESUAI,
        'warehouse_reason' => null,
    ]);

    return [$doc, $item, $warehouse];
}

test('non-admin cannot access admin routes', function () {
    $w = makeWarehouseUser4('w_no_admin');
    $doc = Document::create([
        'accurate_id' => 'ACC-1',
        'document_number' => 'PO.TEST.001',
        'document_type' => DocumentTypes::PO,
        'status' => null,
        'accurate_synced_at' => now(),
    ]);

    $this->actingAs($w)->get('/admin/documents')->assertForbidden();
    $this->actingAs($w)->get('/admin/documents/'.$doc->id)->assertForbidden();
    $this->actingAs($w)->get('/admin/logs')->assertForbidden();
});

test('admin can view documents list and detail', function () {
    $admin = makeAdminUser();
    $doc = Document::create([
        'accurate_id' => 'ACC-1',
        'document_number' => 'PR.TEST.123',
        'document_type' => DocumentTypes::PR,
        'status' => DocumentStatuses::SPV_REJECTED,
        'accurate_synced_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get('/admin/documents')
        ->assertOk()
        ->assertSee($doc->document_number);

    $this->actingAs($admin)
        ->get('/admin/documents/'.$doc->id)
        ->assertOk()
        ->assertSee($doc->document_number);
});

test('admin accurate refresh without change keeps status', function () {
    Storage::fake('r2');
    setAccurateConfig2();
    Cache::forget('accurate.host');

    $admin = makeAdminUser();
    [$doc, $item, $warehouse] = makeDocForRefresh(DocumentStatuses::SPV_APPROVED, 1);

    $photo = app(\App\Services\ItemPhotoService::class)->upload($item, UploadedFile::fake()->image('a.jpg'), $warehouse);
    Storage::disk('r2')->assertExists($photo->path);

    Http::fake([
        'https://account.accurate.id/api/api-token.do' => Http::response([
            's' => true,
            'd' => ['database' => ['host' => 'https://host.example']],
        ], 200),
        'https://host.example/accurate/api/purchase-order/detail.do*' => Http::response([
            's' => true,
            'd' => [
                'id' => 22,
                'number' => 'PO.001',
                'detailItem' => [
                    [
                        'item' => ['id' => 6063],
                        'detailName' => 'Item A',
                        'detailNotes' => null,
                        'quantity' => 1,
                        'itemUnit' => ['name' => 'PCS'],
                    ],
                ],
            ],
        ], 200),
    ]);

    Livewire::actingAs($admin)
        ->test(AdminDocShowPage::class, ['document' => $doc])
        ->call('refreshFromAccurate');

    $doc->refresh();
    expect($doc->status)->toBe(DocumentStatuses::SPV_APPROVED);

    expect(ActivityLog::query()
        ->where('document_id', $doc->id)
        ->where('action', 'accurate_refresh_no_change')
        ->exists())->toBeTrue();

    expect(ActivityLog::query()
        ->where('document_id', $doc->id)
        ->where('action', 'admin_accurate_refresh')
        ->exists())->toBeTrue();
});

test('admin accurate refresh with item change applies status impact and deletes affected photos', function () {
    Storage::fake('r2');
    setAccurateConfig2();
    Cache::forget('accurate.host');

    $admin = makeAdminUser();
    [$doc, $item, $warehouse] = makeDocForRefresh(DocumentStatuses::WAREHOUSE_SUBMITTED, 1);

    $photo = app(\App\Services\ItemPhotoService::class)->upload($item, UploadedFile::fake()->image('a.jpg'), $warehouse);
    Storage::disk('r2')->assertExists($photo->path);

    Http::fake([
        'https://account.accurate.id/api/api-token.do' => Http::response([
            's' => true,
            'd' => ['database' => ['host' => 'https://host.example']],
        ], 200),
        'https://host.example/accurate/api/purchase-order/detail.do*' => Http::response([
            's' => true,
            'd' => [
                'id' => 22,
                'number' => 'PO.001',
                'detailItem' => [
                    [
                        'item' => ['id' => 6063],
                        'detailName' => 'Item A',
                        'detailNotes' => null,
                        // Quantity changed -> reset and delete photo.
                        'quantity' => 2,
                        'itemUnit' => ['name' => 'PCS'],
                    ],
                ],
            ],
        ], 200),
    ]);

    Livewire::actingAs($admin)
        ->test(AdminDocShowPage::class, ['document' => $doc])
        ->call('refreshFromAccurate');

    $doc->refresh();
    expect($doc->status)->toBe(DocumentStatuses::SPV_REJECTED);

    $item->refresh();
    expect($item->match_status)->toBeNull();
    expect(ItemPhoto::query()->count())->toBe(0);
    Storage::disk('r2')->assertMissing($photo->path);

    expect(ActivityLog::query()
        ->where('document_id', $doc->id)
        ->where('action', 'system_item_data_replacement')
        ->exists())->toBeTrue();

    expect(ActivityLog::query()
        ->where('document_id', $doc->id)
        ->where('action', 'system_photo_deletion')
        ->exists())->toBeTrue();

    expect(DocumentDecision::query()
        ->where('document_id', $doc->id)
        ->where('decision_type', DecisionTypes::SYSTEM_STATUS_CHANGE)
        ->exists())->toBeTrue();
});

test('admin override requires reason and creates decision + log', function () {
    $admin = makeAdminUser();
    $doc = Document::create([
        'accurate_id' => 'ACC-1',
        'document_number' => 'PO.TEST.777',
        'document_type' => DocumentTypes::PO,
        'status' => DocumentStatuses::SPV_APPROVED,
        'accurate_synced_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(AdminDocShowPage::class, ['document' => $doc])
        ->set('overrideStatus', DocumentStatuses::FINANCE_CLOSED)
        ->set('overrideReason', '')
        ->call('override')
        ->assertHasErrors(['overrideReason']);

    Livewire::actingAs($admin)
        ->test(AdminDocShowPage::class, ['document' => $doc])
        ->set('overrideStatus', DocumentStatuses::FINANCE_CLOSED)
        ->set('overrideReason', 'Perbaikan status')
        ->call('override');

    $doc->refresh();
    expect($doc->status)->toBe(DocumentStatuses::FINANCE_CLOSED);
    expect($doc->admin_overridden_at)->not->toBeNull();
    expect($doc->admin_overridden_by)->toBe($admin->id);

    expect(DocumentDecision::query()
        ->where('document_id', $doc->id)
        ->where('decision_type', DecisionTypes::ADMIN_OVERRIDE)
        ->exists())->toBeTrue();

    expect(ActivityLog::query()
        ->where('document_id', $doc->id)
        ->where('action', 'admin_override')
        ->exists())->toBeTrue();
});

test('admin logs page is accessible and shows log rows', function () {
    $admin = makeAdminUser();
    $doc = Document::create([
        'accurate_id' => 'ACC-1',
        'document_number' => 'PO.LOG.001',
        'document_type' => DocumentTypes::PO,
        'status' => null,
        'accurate_synced_at' => now(),
    ]);

    ActivityLog::create([
        'actor_id' => $admin->id,
        'actor_role' => $admin->role,
        'action' => 'admin_override',
        'document_id' => $doc->id,
        'previous_status' => null,
        'new_status' => DocumentStatuses::SPV_APPROVED,
        'payload' => ['k' => 'v'],
    ]);

    $this->actingAs($admin)
        ->get('/admin/logs')
        ->assertOk()
        ->assertSee('admin_override')
        ->assertSee($doc->document_number);
});

