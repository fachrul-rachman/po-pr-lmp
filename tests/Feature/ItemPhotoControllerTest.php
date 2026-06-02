<?php

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\ItemPhoto;
use App\Models\User;
use App\Support\Enums\DocumentStatuses;
use App\Support\Enums\DocumentTypes;
use App\Support\Enums\ItemMatchStatuses;
use App\Support\Enums\UserRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeUserForPhotoTest(string $role, string $username): User
{
    return User::create([
        'username' => $username,
        'password' => Hash::make('secret'),
        'role' => $role,
    ]);
}

test('spv can view item photo for non-null status document', function () {
    Storage::fake('r2');

    $spv = makeUserForPhotoTest(UserRoles::SPV, 'spv1');

    $doc = Document::create([
        'accurate_id' => 'ACC-1',
        'document_number' => 'PO.TEST.001',
        'document_type' => DocumentTypes::PO,
        'status' => DocumentStatuses::WAREHOUSE_SUBMITTED,
        'accurate_synced_at' => now(),
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

    Storage::disk('r2')->put('item-photos/'.$item->id.'/a.jpg', 'x');

    $photo = ItemPhoto::create([
        'document_item_id' => $item->id,
        'uploaded_by' => makeUserForPhotoTest(UserRoles::WAREHOUSE, 'w1')->id,
        'disk' => 'r2',
        'path' => 'item-photos/'.$item->id.'/a.jpg',
        'original_name' => 'a.jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 1,
    ]);

    $this->actingAs($spv)
        ->get(route('item-photos.show', $photo))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');
});

test('finance cannot view item photo for warehouse_submitted document', function () {
    Storage::fake('r2');

    $finance = makeUserForPhotoTest(UserRoles::FINANCE, 'f1');

    $doc = Document::create([
        'accurate_id' => 'ACC-1',
        'document_number' => 'PO.TEST.002',
        'document_type' => DocumentTypes::PO,
        'status' => DocumentStatuses::WAREHOUSE_SUBMITTED,
        'accurate_synced_at' => now(),
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

    Storage::disk('r2')->put('item-photos/'.$item->id.'/a.jpg', 'x');

    $photo = ItemPhoto::create([
        'document_item_id' => $item->id,
        'uploaded_by' => makeUserForPhotoTest(UserRoles::WAREHOUSE, 'w2')->id,
        'disk' => 'r2',
        'path' => 'item-photos/'.$item->id.'/a.jpg',
        'original_name' => 'a.jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 1,
    ]);

    $this->actingAs($finance)
        ->get(route('item-photos.show', $photo))
        ->assertForbidden();
});
