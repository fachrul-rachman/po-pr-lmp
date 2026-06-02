<?php

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
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function makePurchasingUser(string $username = 'purch1'): User
{
    return User::create([
        'username' => $username,
        'password' => Hash::make('secret'),
        'role' => UserRoles::PURCHASING,
    ]);
}

function makeFinanceUser2(string $username = 'fin2'): User
{
    return User::create([
        'username' => $username,
        'password' => Hash::make('secret'),
        'role' => UserRoles::FINANCE,
    ]);
}

function makeWarehouseUser5(string $username = 'wh5'): User
{
    return User::create([
        'username' => $username,
        'password' => Hash::make('secret'),
        'role' => UserRoles::WAREHOUSE,
    ]);
}

test('purchasing can view dashboard and search/filter', function () {
    $p = makePurchasingUser();
    $doc1 = Document::create([
        'accurate_id' => 'ACC-1',
        'document_number' => 'PR.PUR.001',
        'document_type' => DocumentTypes::PR,
        'status' => DocumentStatuses::SPV_APPROVED,
        'accurate_synced_at' => now(),
    ]);
    $doc2 = Document::create([
        'accurate_id' => 'ACC-2',
        'document_number' => 'PO.PUR.002',
        'document_type' => DocumentTypes::PO,
        'status' => DocumentStatuses::FINANCE_CLOSED,
        'accurate_synced_at' => now(),
    ]);

    $this->actingAs($p)->get('/purchasing/dashboard')
        ->assertOk()
        ->assertSee($doc1->document_number)
        ->assertSee($doc2->document_number);

    $this->actingAs($p)->get('/purchasing/dashboard?search=PR.PUR')
        ->assertOk()
        ->assertSee($doc1->document_number);
});

test('purchasing can view full document detail and workflow decisions', function () {
    $p = makePurchasingUser();
    $finance = makeFinanceUser2();
    $warehouse = makeWarehouseUser5();

    $doc = Document::create([
        'accurate_id' => 'ACC-1',
        'document_number' => 'PO.PUR.DET.001',
        'document_type' => DocumentTypes::PO,
        'status' => DocumentStatuses::FINANCE_REJECTED,
        'accurate_synced_at' => now(),
        'finance_processed_at' => now(),
        'finance_processed_by' => $finance->id,
    ]);

    $item = DocumentItem::create([
        'document_id' => $doc->id,
        'accurate_item_id' => 'ACC-ITEM-1',
        'nama_barang' => 'Item A',
        'keterangan' => null,
        'quantity' => 1,
        'satuan' => 'PCS',
        'match_status' => ItemMatchStatuses::SESUAI,
        'warehouse_reason' => null,
    ]);

    ItemPhoto::create([
        'document_item_id' => $item->id,
        'uploaded_by' => $warehouse->id,
        'disk' => 'r2',
        'path' => 'item-photos/'.$item->id.'/x.jpg',
        'original_name' => 'x.jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 123,
    ]);

    DocumentDecision::create([
        'document_id' => $doc->id,
        'decision_type' => DecisionTypes::FINANCE_REJECT,
        'from_status' => DocumentStatuses::SPV_APPROVED,
        'to_status' => DocumentStatuses::FINANCE_REJECTED,
        'reason' => 'Alasan Finance',
        'actor_id' => $finance->id,
        'actor_role' => $finance->role,
    ]);

    $this->actingAs($p)
        ->get('/purchasing/documents/'.$doc->id)
        ->assertOk()
        ->assertSee($doc->document_number)
        ->assertSee('Riwayat Keputusan')
        ->assertSee('Finance Reject');
});

test('purchasing cannot access other role routes', function () {
    $p = makePurchasingUser();
    $this->actingAs($p)->get('/finance/request')->assertForbidden();
    $this->actingAs($p)->get('/admin/documents')->assertForbidden();
});
