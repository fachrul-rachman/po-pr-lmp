<?php

use App\Livewire\Finance\Documents\ShowPage as FinanceShowPage;
use App\Models\ActivityLog;
use App\Models\DecisionItemReason;
use App\Models\Document;
use App\Models\DocumentDecision;
use App\Models\DocumentItem;
use App\Models\ItemPhoto;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Workflow\FinanceWorkflowService;
use App\Support\Enums\DecisionTypes;
use App\Support\Enums\DocumentStatuses;
use App\Support\Enums\DocumentTypes;
use App\Support\Enums\ItemMatchStatuses;
use App\Support\Enums\UserRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeFinanceUser(string $username = 'finance1'): User
{
    return User::create([
        'username' => $username,
        'password' => Hash::make('secret'),
        'role' => UserRoles::FINANCE,
    ]);
}

function makeSpvUser2(string $username = 'spv2'): User
{
    return User::create([
        'username' => $username,
        'password' => Hash::make('secret'),
        'role' => UserRoles::SPV,
    ]);
}

function makeWarehouseUser3(string $username = 'w3'): User
{
    return User::create([
        'username' => $username,
        'password' => Hash::make('secret'),
        'role' => UserRoles::WAREHOUSE,
    ]);
}

function makeSpvApprovedDoc(array $itemStatuses, string $status = DocumentStatuses::SPV_APPROVED): Document
{
    $warehouse = makeWarehouseUser3('wh_seed_'.random_int(100, 999));
    $spv = makeSpvUser2('spv_seed_'.random_int(100, 999));

    $doc = Document::create([
        'accurate_id' => 'ACC-1',
        'document_number' => 'PO.TEST.'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
        'document_type' => DocumentTypes::PO,
        'status' => $status,
        'accurate_synced_at' => now(),
        'warehouse_submitted_at' => now(),
        'spv_processed_at' => now(),
        'spv_processed_by' => $spv->id,
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

    DocumentDecision::create([
        'document_id' => $doc->id,
        'decision_type' => DecisionTypes::SPV_APPROVE,
        'from_status' => DocumentStatuses::WAREHOUSE_SUBMITTED,
        'to_status' => DocumentStatuses::SPV_APPROVED,
        'reason' => null,
        'actor_id' => $spv->id,
        'actor_role' => $spv->role,
    ]);

    return $doc->refresh();
}

test('finance request shows spv_approved', function () {
    $finance = makeFinanceUser();
    $doc = makeSpvApprovedDoc([ItemMatchStatuses::SESUAI], DocumentStatuses::SPV_APPROVED);

    $this->actingAs($finance)
        ->get('/finance/request')
        ->assertOk()
        ->assertSee($doc->document_number);
});

test('finance can close spv_approved', function () {
    $finance = makeFinanceUser();
    $doc = makeSpvApprovedDoc([ItemMatchStatuses::SESUAI, ItemMatchStatuses::SESUAI], DocumentStatuses::SPV_APPROVED);

    Livewire::actingAs($finance)
        ->test(FinanceShowPage::class, ['document' => $doc])
        ->call('close')
        ->assertRedirect(route('finance.history'));

    $doc->refresh();
    expect($doc->status)->toBe(DocumentStatuses::FINANCE_CLOSED);
    expect($doc->finance_processed_at)->not->toBeNull();
    expect($doc->finance_processed_by)->toBe($finance->id);

    expect(DocumentDecision::query()
        ->where('document_id', $doc->id)
        ->where('decision_type', DecisionTypes::FINANCE_CLOSE)
        ->count())->toBe(1);

    expect(ActivityLog::query()->where('action', 'finance_close')->count())->toBe(1);
});

test('finance cannot close if any item is tidak_sesuai', function () {
    $finance = makeFinanceUser();
    $doc = makeSpvApprovedDoc([ItemMatchStatuses::SESUAI, ItemMatchStatuses::TIDAK_SESUAI], DocumentStatuses::SPV_APPROVED);

    $svc = new FinanceWorkflowService(new ActivityLogService());

    expect(fn () => $svc->close($doc, $finance))
        ->toThrow(RuntimeException::class);
});

test('finance reject requires document-level reason', function () {
    $finance = makeFinanceUser();
    $doc = makeSpvApprovedDoc([ItemMatchStatuses::SESUAI], DocumentStatuses::SPV_APPROVED);

    Livewire::actingAs($finance)
        ->test(FinanceShowPage::class, ['document' => $doc])
        ->set('rejectReason', '')
        ->call('reject')
        ->assertHasErrors(['rejectReason']);
});

test('finance reject sets finance_rejected and appears in spv non close', function () {
    $finance = makeFinanceUser();
    $spv = makeSpvUser2('spv_viewer');
    $doc = makeSpvApprovedDoc([ItemMatchStatuses::SESUAI], DocumentStatuses::SPV_APPROVED);

    $itemId = (string) $doc->items()->first()->id;

    Livewire::actingAs($finance)
        ->test(FinanceShowPage::class, ['document' => $doc])
        ->set('rejectReason', 'Mohon dicek ulang')
        ->set('itemReasons.'.$itemId, 'Bukti kurang jelas')
        ->call('reject')
        ->assertRedirect(route('finance.history'));

    $doc->refresh();
    expect($doc->status)->toBe(DocumentStatuses::FINANCE_REJECTED);
    expect($doc->finance_processed_by)->toBe($finance->id);

    expect(DocumentDecision::query()
        ->where('document_id', $doc->id)
        ->where('decision_type', DecisionTypes::FINANCE_REJECT)
        ->count())->toBe(1);

    expect(DecisionItemReason::query()->count())->toBe(1);

    $this->actingAs($spv)->get('/spv/non-close')->assertOk()->assertSee($doc->document_number);
});

test('finance_closed is read-only for finance workflow actions', function () {
    $finance = makeFinanceUser();
    $doc = makeSpvApprovedDoc([ItemMatchStatuses::SESUAI], DocumentStatuses::FINANCE_CLOSED);

    $svc = new FinanceWorkflowService(new ActivityLogService());

    expect(fn () => $svc->close($doc, $finance))
        ->toThrow(RuntimeException::class);
    expect(fn () => $svc->reject($doc, $finance, 'x'))
        ->toThrow(RuntimeException::class);
});

test('non-finance cannot run finance actions', function () {
    $spv = makeSpvUser2();
    $doc = makeSpvApprovedDoc([ItemMatchStatuses::SESUAI], DocumentStatuses::SPV_APPROVED);

    $svc = new FinanceWorkflowService(new ActivityLogService());

    expect(fn () => $svc->close($doc, $spv))
        ->toThrow(RuntimeException::class, 'Only Finance can close.');
});

