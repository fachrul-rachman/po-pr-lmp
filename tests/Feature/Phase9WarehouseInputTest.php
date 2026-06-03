<?php

use App\Livewire\Warehouse\InputPage as WarehouseInputPage;
use App\Livewire\Warehouse\Documents\EditPage as WarehouseDocumentEditPage;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\DocumentDecision;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\ItemPhotoService;
use App\Services\Workflow\WarehouseWorkflowService;
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

function setAccurateConfigForWarehouse(): void
{
    config()->set('accurate.api_token', 'tok');
    config()->set('accurate.signature_secret', 'sec');
    config()->set('accurate.default_host', 'https://fallback.example');
    config()->set('accurate.timeout_seconds', 5);
    config()->set('accurate.host_cache_ttl_days', 30);
}

function makeWarehouseUser(string $username = 'warehouse1'): User
{
    return User::create([
        'username' => $username,
        'password' => Hash::make('secret'),
        'role' => UserRoles::WAREHOUSE,
    ]);
}

function makeDraftDocument(int $itemsCount = 1, ?string $status = null): Document
{
    $doc = Document::create([
        'accurate_id' => 'ACC-1',
        'document_number' => 'PO.TEST.'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
        'document_type' => DocumentTypes::PO,
        'status' => $status,
        'accurate_synced_at' => now(),
    ]);

    for ($i = 1; $i <= $itemsCount; $i++) {
        DocumentItem::create([
            'document_id' => $doc->id,
            'accurate_item_id' => 'ACC-ITEM-'.$i,
            'nama_barang' => 'Item '.$i,
            'keterangan' => null,
            'quantity' => 1,
            'satuan' => 'PCS',
            'match_status' => null,
            'warehouse_reason' => null,
        ]);
    }

    return $doc->refresh();
}

function attachOnePhotoPerItem(Document $doc, User $actor): void
{
    Storage::fake('r2');

    $svc = new ItemPhotoService();
    foreach ($doc->items as $item) {
        $svc->upload($item, UploadedFile::fake()->image('a.jpg', 800, 600), $actor);
    }
}

test('warehouse can search valid po/pr', function () {
    setAccurateConfigForWarehouse();
    Cache::forget('accurate.host');

    Http::fake([
        'https://account.accurate.id/api/api-token.do' => Http::response([
            's' => true,
            'd' => ['database' => ['host' => 'https://host.example']],
        ], 200),
        'https://host.example/accurate/api/purchase-requisition/list.do*' => Http::response([
            's' => true,
            'd' => [
                ['id' => 11, 'number' => 'PR.001', 'status' => 'DRAFT', 'statusName' => 'Draf', 'transDateView' => '28/05/2026'],
            ],
        ], 200),
        'https://host.example/accurate/api/purchase-order/list.do*' => Http::response([
            's' => true,
            'd' => [
                ['id' => 22, 'number' => 'PO.001', 'status' => 'ONPROCESS', 'statusName' => 'Onprocess', 'transDateView' => '28/05/2026'],
            ],
        ], 200),
    ]);

    $actor = makeWarehouseUser();

    $lw = Livewire::actingAs($actor)
        ->test(WarehouseInputPage::class)
        ->set('term', 'PO.0')
        ->set('type', '')
        ->call('search')
        ->assertHasNoErrors('term');

    $results = $lw->get('results');
    expect($results)->toHaveCount(2);
    expect(collect($results)->pluck('document_type')->all())->toContain(DocumentTypes::PR, DocumentTypes::PO);
});

test('warehouse cannot submit not found po/pr (no selected document)', function () {
    $actor = makeWarehouseUser();

    Livewire::actingAs($actor)
        ->test(WarehouseInputPage::class)
        ->call('submit')
        ->assertHasErrors(['submit']);
});

test('clicking tidak_sesuai without reason does not violate db constraint and shows error', function () {
    $actor = makeWarehouseUser();
    $doc = makeDraftDocument(itemsCount: 1, status: null);

    $item = $doc->items()->firstOrFail();

    Livewire::actingAs($actor)
        ->test(WarehouseInputPage::class)
        ->set('selectedDocumentId', $doc->id)
        ->call('setMatch', $item->id, ItemMatchStatuses::TIDAK_SESUAI)
        ->assertHasErrors(['reason_'.$item->id]);

    $item->refresh();
    expect($item->match_status)->toBeNull();
});

test('submit uploads staged photos automatically (multi-file supported)', function () {
    Storage::fake('r2');

    $actor = makeWarehouseUser();
    $doc = makeDraftDocument(itemsCount: 1, status: null);
    $item = $doc->items()->firstOrFail();

    Livewire::actingAs($actor)
        ->test(WarehouseInputPage::class)
        ->set('selectedDocumentId', $doc->id)
        ->call('setMatch', $item->id, ItemMatchStatuses::SESUAI)
        ->set('uploads.'.$item->id, [
            UploadedFile::fake()->image('a.jpg', 800, 600),
            UploadedFile::fake()->image('b.jpg', 800, 600),
        ])
        ->call('submit')
        ->assertRedirect(route('warehouse.history'));

    $doc->refresh();
    expect($doc->status)->toBe(DocumentStatuses::WAREHOUSE_SUBMITTED);

    $item->refresh();
    expect($item->photos()->count())->toBe(2);
});

test('warehouse cannot submit without checking every item', function () {
    $actor = makeWarehouseUser();
    $doc = makeDraftDocument(itemsCount: 1, status: null);

    attachOnePhotoPerItem($doc, $actor);

    $workflow = new WarehouseWorkflowService(new ActivityLogService());

    expect(fn () => $workflow->submit($doc, $actor))
        ->toThrow(RuntimeException::class, 'Every item must be checked before submit.');
});

test('warehouse cannot submit without at least one photo per item', function () {
    $actor = makeWarehouseUser();
    $doc = makeDraftDocument(itemsCount: 1, status: null);

    $item = $doc->items()->firstOrFail();
    $item->match_status = ItemMatchStatuses::SESUAI;
    $item->save();

    $workflow = new WarehouseWorkflowService(new ActivityLogService());

    expect(fn () => $workflow->submit($doc, $actor))
        ->toThrow(RuntimeException::class, 'Every item must have at least one photo before submit.');
});

test('warehouse cannot submit tidak_sesuai item without reason', function () {
    $actor = makeWarehouseUser();
    $doc = makeDraftDocument(itemsCount: 1, status: null);

    $item = $doc->items()->firstOrFail();
    $item->match_status = ItemMatchStatuses::TIDAK_SESUAI;
    $item->warehouse_reason = null;
    $item->save();

    attachOnePhotoPerItem($doc, $actor);

    $workflow = new WarehouseWorkflowService(new ActivityLogService());

    expect(fn () => $workflow->submit($doc, $actor))
        ->toThrow(RuntimeException::class, 'Reason is required for tidak_sesuai item.');
});

test('successful submit sets warehouse_submitted and creates a decision', function () {
    $actor = makeWarehouseUser();
    $doc = makeDraftDocument(itemsCount: 2, status: null);

    foreach ($doc->items as $item) {
        $item->match_status = ItemMatchStatuses::SESUAI;
        $item->warehouse_reason = null;
        $item->save();
    }

    attachOnePhotoPerItem($doc, $actor);

    $workflow = new WarehouseWorkflowService(new ActivityLogService());
    $workflow->submit($doc, $actor);

    $doc->refresh();
    expect($doc->status)->toBe(DocumentStatuses::WAREHOUSE_SUBMITTED);
    expect($doc->warehouse_submitted_at)->not->toBeNull();
    expect($doc->warehouse_submitted_by)->toBe($actor->id);

    expect(DocumentDecision::query()
        ->where('document_id', $doc->id)
        ->where('decision_type', DecisionTypes::WAREHOUSE_SUBMIT)
        ->count())->toBe(1);
});

test('submitted document appears in warehouse history', function () {
    $actor = makeWarehouseUser();
    $doc = makeDraftDocument(itemsCount: 1, status: null);

    $item = $doc->items()->firstOrFail();
    $item->match_status = ItemMatchStatuses::SESUAI;
    $item->save();

    attachOnePhotoPerItem($doc, $actor);

    $workflow = new WarehouseWorkflowService(new ActivityLogService());
    $workflow->submit($doc, $actor);

    $this->actingAs($actor)
        ->get('/warehouse/history')
        ->assertOk()
        ->assertSee($doc->document_number);
});

test('warehouse cannot submit same document again while not returned', function () {
    $actor = makeWarehouseUser();
    $doc = makeDraftDocument(itemsCount: 1, status: DocumentStatuses::WAREHOUSE_SUBMITTED);

    $item = $doc->items()->firstOrFail();
    $item->match_status = ItemMatchStatuses::SESUAI;
    $item->save();

    attachOnePhotoPerItem($doc, $actor);

    $workflow = new WarehouseWorkflowService(new ActivityLogService());

    expect(fn () => $workflow->submit($doc, $actor))
        ->toThrow(RuntimeException::class, 'Document is already in workflow and cannot be submitted again.');
});

test('warehouse can edit and resubmit only when status is spv_rejected', function () {
    $actor = makeWarehouseUser();
    $doc = makeDraftDocument(itemsCount: 1, status: DocumentStatuses::SPV_REJECTED);

    $item = $doc->items()->firstOrFail();
    $item->match_status = ItemMatchStatuses::SESUAI;
    $item->warehouse_reason = null;
    $item->save();

    attachOnePhotoPerItem($doc, $actor);

    $workflow = new WarehouseWorkflowService(new ActivityLogService());
    $workflow->resubmit($doc, $actor);

    $doc->refresh();
    expect($doc->status)->toBe(DocumentStatuses::WAREHOUSE_SUBMITTED);

    expect(DocumentDecision::query()
        ->where('document_id', $doc->id)
        ->where('decision_type', DecisionTypes::WAREHOUSE_RESUBMIT)
        ->count())->toBe(1);

    $other = makeDraftDocument(itemsCount: 1, status: null);
    foreach ($other->items as $it) {
        $it->match_status = ItemMatchStatuses::SESUAI;
        $it->save();
    }
    attachOnePhotoPerItem($other, $actor);

    expect(fn () => $workflow->resubmit($other, $actor))
        ->toThrow(RuntimeException::class, 'Warehouse can resubmit only when status is spv_rejected.');
});

test('warehouse can edit warehouse_submitted document before spv approves', function () {
    Storage::fake('r2');

    $actor = makeWarehouseUser();
    $doc = makeDraftDocument(itemsCount: 1, status: DocumentStatuses::WAREHOUSE_SUBMITTED);
    $doc->warehouse_submitted_at = now();
    $doc->warehouse_submitted_by = $actor->id;
    $doc->save();

    $item = $doc->items()->firstOrFail();
    $item->match_status = ItemMatchStatuses::TIDAK_SESUAI;
    $item->warehouse_reason = 'Wrong item';
    $item->save();

    attachOnePhotoPerItem($doc, $actor);

    Livewire::actingAs($actor)
        ->test(WarehouseDocumentEditPage::class, ['document' => $doc])
        ->call('setMatch', $item->id, ItemMatchStatuses::SESUAI)
        ->set('uploads.'.$item->id, [UploadedFile::fake()->image('new.jpg', 800, 600)])
        ->call('saveChanges')
        ->assertRedirect(route('warehouse.documents.show', $doc));

    $doc->refresh();
    expect($doc->status)->toBe(DocumentStatuses::WAREHOUSE_SUBMITTED);

    $item->refresh();
    expect($item->match_status)->toBe(ItemMatchStatuses::SESUAI);
    expect($item->warehouse_reason)->toBeNull();
    expect($item->photos()->count())->toBe(2);
});
