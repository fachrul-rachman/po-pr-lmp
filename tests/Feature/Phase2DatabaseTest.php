<?php

use App\Models\ActivityLog;
use App\Models\DecisionItemReason;
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
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('migrations create required domain tables', function () {
    expect(Schema::hasTable('users'))->toBeTrue();
    expect(Schema::hasTable('documents'))->toBeTrue();
    expect(Schema::hasTable('document_items'))->toBeTrue();
    expect(Schema::hasTable('item_photos'))->toBeTrue();
    expect(Schema::hasTable('document_decisions'))->toBeTrue();
    expect(Schema::hasTable('decision_item_reasons'))->toBeTrue();
    expect(Schema::hasTable('activity_logs'))->toBeTrue();
});

test('enum-like constants reject undocumented values', function () {
    expect(fn () => UserRoles::assertValid('nope'))->toThrow(InvalidArgumentException::class);
    expect(fn () => DocumentTypes::assertValid('nope'))->toThrow(InvalidArgumentException::class);
    expect(fn () => DocumentStatuses::assertValid('nope'))->toThrow(InvalidArgumentException::class);
    expect(fn () => ItemMatchStatuses::assertValid('nope'))->toThrow(InvalidArgumentException::class);
    expect(fn () => DecisionTypes::assertValid('nope'))->toThrow(InvalidArgumentException::class);
});

test('model relationships are wired', function () {
    $warehouse = User::factory()->create(['role' => UserRoles::WAREHOUSE]);
    $spv = User::factory()->create(['role' => UserRoles::SPV]);

    $document = Document::create([
        'accurate_id' => 'ACC-1',
        'document_number' => 'PO.TEST.001',
        'document_type' => DocumentTypes::PO,
        'status' => null,
        'accurate_synced_at' => now(),
        'warehouse_submitted_by' => $warehouse->id,
        'spv_processed_by' => $spv->id,
    ]);

    $item = DocumentItem::create([
        'document_id' => $document->id,
        'accurate_item_id' => 'ACC-ITEM-1',
        'nama_barang' => 'Item A',
        'keterangan' => 'Note',
        'quantity' => 1,
        'satuan' => 'PCS',
        'match_status' => ItemMatchStatuses::SESUAI,
        'warehouse_reason' => null,
    ]);

    $photo = ItemPhoto::create([
        'document_item_id' => $item->id,
        'uploaded_by' => $warehouse->id,
        'disk' => 'r2',
        'path' => 'documents/x.jpg',
        'original_name' => 'x.jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 123,
    ]);

    $decision = DocumentDecision::create([
        'document_id' => $document->id,
        'decision_type' => DecisionTypes::SPV_REJECT,
        'from_status' => DocumentStatuses::WAREHOUSE_SUBMITTED,
        'to_status' => DocumentStatuses::SPV_REJECTED,
        'reason' => 'Not valid',
        'actor_id' => $spv->id,
        'actor_role' => UserRoles::SPV,
    ]);

    $itemReason = DecisionItemReason::create([
        'document_decision_id' => $decision->id,
        'document_item_id' => $item->id,
        'reason' => 'Wrong item',
    ]);

    $log = ActivityLog::create([
        'actor_id' => $warehouse->id,
        'actor_role' => UserRoles::WAREHOUSE,
        'action' => 'warehouse_submit',
        'document_id' => $document->id,
        'previous_status' => null,
        'new_status' => DocumentStatuses::WAREHOUSE_SUBMITTED,
        'payload' => ['k' => 'v'],
    ]);

    expect($document->items()->count())->toBe(1);
    expect($document->warehouseSubmittedBy?->is($warehouse))->toBeTrue();
    expect($item->document?->is($document))->toBeTrue();
    expect($item->photos()->first()?->is($photo))->toBeTrue();
    expect($decision->document?->is($document))->toBeTrue();
    expect($decision->actor?->is($spv))->toBeTrue();
    expect($decision->itemReasons()->first()?->is($itemReason))->toBeTrue();
    expect($log->actor?->is($warehouse))->toBeTrue();
    expect($log->document?->is($document))->toBeTrue();
});

