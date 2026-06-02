<?php

use App\Livewire\Spv\Documents\ShowPage as SpvShowPage;
use App\Models\ActivityLog;
use App\Models\DecisionItemReason;
use App\Models\Document;
use App\Models\DocumentDecision;
use App\Models\DocumentItem;
use App\Models\ItemPhoto;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Workflow\SpvWorkflowService;
use App\Support\Enums\DecisionTypes;
use App\Support\Enums\DocumentStatuses;
use App\Support\Enums\DocumentTypes;
use App\Support\Enums\ItemMatchStatuses;
use App\Support\Enums\UserRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeSpvUser(string $username = 'spv1'): User
{
    return User::create([
        'username' => $username,
        'password' => Hash::make('secret'),
        'role' => UserRoles::SPV,
    ]);
}

function makeWarehouseUser2(string $username = 'w1'): User
{
    return User::create([
        'username' => $username,
        'password' => Hash::make('secret'),
        'role' => UserRoles::WAREHOUSE,
    ]);
}

function makeSubmittedDoc(array $itemStatuses, string $status = DocumentStatuses::WAREHOUSE_SUBMITTED): Document
{
    $warehouse = makeWarehouseUser2('wh_seed_'.random_int(100, 999));

    $doc = Document::create([
        'accurate_id' => 'ACC-1',
        'document_number' => 'PR.TEST.'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
        'document_type' => DocumentTypes::PR,
        'status' => $status,
        'accurate_synced_at' => now(),
        'warehouse_submitted_at' => now(),
    ]);

    foreach ($itemStatuses as $idx => $match) {
        $item = DocumentItem::create([
            'document_id' => $doc->id,
            'accurate_item_id' => 'ACC-ITEM-'.$idx,
            'nama_barang' => 'Item '.$idx,
            'keterangan' => null,
            'quantity' => 1,
            'satuan' => 'PCS',
            'match_status' => $match,
            'warehouse_reason' => $match === ItemMatchStatuses::TIDAK_SESUAI ? 'Reason' : null,
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
    }

    return $doc->refresh();
}

test('spv request shows warehouse_submitted', function () {
    $spv = makeSpvUser();
    $doc = makeSubmittedDoc([ItemMatchStatuses::SESUAI], DocumentStatuses::WAREHOUSE_SUBMITTED);

    $this->actingAs($spv)
        ->get('/spv/request')
        ->assertOk()
        ->assertSee($doc->document_number);
});

test('spv can approve valid document', function () {
    $spv = makeSpvUser();
    $doc = makeSubmittedDoc([ItemMatchStatuses::SESUAI, ItemMatchStatuses::SESUAI], DocumentStatuses::WAREHOUSE_SUBMITTED);

    Livewire::actingAs($spv)
        ->test(SpvShowPage::class, ['document' => $doc])
        ->call('approve')
        ->assertRedirect(route('spv.history'));

    $doc->refresh();
    expect($doc->status)->toBe(DocumentStatuses::SPV_APPROVED);
    expect($doc->spv_processed_at)->not->toBeNull();
    expect($doc->spv_processed_by)->toBe($spv->id);

    expect(DocumentDecision::query()
        ->where('document_id', $doc->id)
        ->where('decision_type', DecisionTypes::SPV_APPROVE)
        ->count())->toBe(1);

    expect(ActivityLog::query()->where('action', 'spv_approve')->count())->toBe(1);
});

test('spv cannot approve if any item is tidak_sesuai', function () {
    $spv = makeSpvUser();
    $doc = makeSubmittedDoc([ItemMatchStatuses::SESUAI, ItemMatchStatuses::TIDAK_SESUAI], DocumentStatuses::WAREHOUSE_SUBMITTED);

    $svc = new SpvWorkflowService(new ActivityLogService());

    expect(fn () => $svc->approve($doc, $spv))
        ->toThrow(RuntimeException::class);
});

test('spv reject requires document-level reason', function () {
    $spv = makeSpvUser();
    $doc = makeSubmittedDoc([ItemMatchStatuses::SESUAI], DocumentStatuses::WAREHOUSE_SUBMITTED);

    Livewire::actingAs($spv)
        ->test(SpvShowPage::class, ['document' => $doc])
        ->set('rejectReason', '')
        ->call('reject')
        ->assertHasErrors(['rejectReason']);
});

test('spv reject sets spv_rejected and appears in warehouse non valid and spv non valid', function () {
    $spv = makeSpvUser();
    $warehouse = makeWarehouseUser2('wh');
    $doc = makeSubmittedDoc([ItemMatchStatuses::SESUAI], DocumentStatuses::WAREHOUSE_SUBMITTED);

    Livewire::actingAs($spv)
        ->test(SpvShowPage::class, ['document' => $doc])
        ->set('rejectReason', 'Kurang jelas')
        ->set('itemReasons.'.$doc->items()->first()->id, 'Foto blur')
        ->call('reject')
        ->assertRedirect(route('spv.non-valid'));

    $doc->refresh();
    expect($doc->status)->toBe(DocumentStatuses::SPV_REJECTED);
    expect($doc->spv_processed_by)->toBe($spv->id);

    expect(DocumentDecision::query()
        ->where('document_id', $doc->id)
        ->where('decision_type', DecisionTypes::SPV_REJECT)
        ->count())->toBe(1);

    expect(DecisionItemReason::query()->count())->toBe(1);

    $this->actingAs($warehouse)->get('/warehouse/non-valid')->assertOk()->assertSee($doc->document_number);
    $this->actingAs($spv)->get('/spv/non-valid')->assertOk()->assertSee($doc->document_number);
});

test('spv can process finance_rejected', function () {
    $spv = makeSpvUser();
    $doc = makeSubmittedDoc([ItemMatchStatuses::SESUAI], DocumentStatuses::FINANCE_REJECTED);

    Livewire::actingAs($spv)
        ->test(SpvShowPage::class, ['document' => $doc])
        ->call('approve')
        ->assertRedirect(route('spv.history'));

    $doc->refresh();
    expect($doc->status)->toBe(DocumentStatuses::SPV_APPROVED);
});

test('non-spv cannot run spv actions', function () {
    $w = makeWarehouseUser2();
    $doc = makeSubmittedDoc([ItemMatchStatuses::SESUAI], DocumentStatuses::WAREHOUSE_SUBMITTED);

    $svc = new SpvWorkflowService(new ActivityLogService());

    expect(fn () => $svc->approve($doc, $w))
        ->toThrow(RuntimeException::class, 'Only SPV can approve.');
});
