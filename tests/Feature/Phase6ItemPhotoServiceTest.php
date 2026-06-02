<?php

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\ItemPhoto;
use App\Models\User;
use App\Services\ItemPhotoService;
use App\Support\Enums\DocumentTypes;
use App\Support\Enums\UserRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeWarehouse(): User
{
    return User::create([
        'username' => 'warehouse_actor',
        'password' => Hash::make('secret'),
        'role' => UserRoles::WAREHOUSE,
    ]);
}

function makeItem(): DocumentItem
{
    $doc = Document::create([
        'accurate_id' => 'ACC-1',
        'document_number' => 'PO.TEST.001',
        'document_type' => DocumentTypes::PO,
        'status' => null,
        'accurate_synced_at' => now(),
    ]);

    return DocumentItem::create([
        'document_id' => $doc->id,
        'accurate_item_id' => 'ACC-ITEM-1',
        'nama_barang' => 'Item A',
        'keterangan' => null,
        'quantity' => 1,
        'satuan' => 'PCS',
        'match_status' => null,
        'warehouse_reason' => null,
    ]);
}

test('upload stores file on r2 and persists metadata', function () {
    Storage::fake('r2');

    $svc = new ItemPhotoService();
    $actor = makeWarehouse();
    $item = makeItem();

    $file = UploadedFile::fake()->image('photo.jpg', 1000, 800);

    $photo = $svc->upload($item, $file, $actor);

    expect($photo)->toBeInstanceOf(ItemPhoto::class);
    expect($photo->disk)->toBe('r2');
    expect($photo->document_item_id)->toBe($item->id);
    expect($photo->uploaded_by)->toBe($actor->id);
    expect($photo->size_bytes)->toBeGreaterThan(0);
    expect($photo->path)->not->toBeEmpty();

    Storage::disk('r2')->assertExists($photo->path);
});

test('delete removes storage object and database row', function () {
    Storage::fake('r2');

    $svc = new ItemPhotoService();
    $actor = makeWarehouse();
    $item = makeItem();

    $photo = $svc->upload($item, UploadedFile::fake()->image('a.jpg'), $actor);

    $path = $photo->path;
    expect(ItemPhoto::query()->whereKey($photo->id)->exists())->toBeTrue();
    Storage::disk('r2')->assertExists($path);

    $svc->delete($photo);

    Storage::disk('r2')->assertMissing($path);
    expect(ItemPhoto::query()->whereKey($photo->id)->exists())->toBeFalse();
});

test('replace uploads new photo and deletes old one', function () {
    Storage::fake('r2');

    $svc = new ItemPhotoService();
    $actor = makeWarehouse();
    $item = makeItem();

    $old = $svc->upload($item, UploadedFile::fake()->image('old.jpg'), $actor);
    $oldId = $old->id;
    $oldPath = $old->path;

    $new = $svc->replace($old, UploadedFile::fake()->image('new.jpg'), $actor);

    expect($new->id)->not->toBe($oldId);
    Storage::disk('r2')->assertMissing($oldPath);
    Storage::disk('r2')->assertExists($new->path);
    expect(ItemPhoto::query()->whereKey($oldId)->exists())->toBeFalse();
    expect(ItemPhoto::query()->whereKey($new->id)->exists())->toBeTrue();
});

