<?php

use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\ItemPhoto;
use App\Models\User;
use App\Services\Accurate\AccurateAuth;
use App\Services\Accurate\AccurateDocumentMapper;
use App\Services\Accurate\AccurateException;
use App\Services\Accurate\AccurateHostDiscovery;
use App\Services\Accurate\AccurateHttpClient;
use App\Services\Accurate\AccuratePurchaseOrderClient;
use App\Services\Accurate\AccuratePurchaseRequisitionClient;
use App\Services\Accurate\AccurateRefreshService;
use App\Services\Accurate\AccurateService;
use App\Services\ActivityLogService;
use App\Services\ItemPhotoService;
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

uses(RefreshDatabase::class);

function setAccurateConfig(): void
{
    config()->set('accurate.api_token', 'tok');
    config()->set('accurate.signature_secret', 'sec');
    config()->set('accurate.default_host', 'https://fallback.example');
    config()->set('accurate.timeout_seconds', 5);
    config()->set('accurate.host_cache_ttl_days', 30);
}

function makeAccurateServices(): array
{
    $auth = new AccurateAuth();
    $http = new AccurateHttpClient($auth);
    $host = new AccurateHostDiscovery($http);
    $po = new AccuratePurchaseOrderClient($host, $http);
    $pr = new AccuratePurchaseRequisitionClient($host, $http);
    $mapper = new AccurateDocumentMapper();
    $svc = new AccurateService($po, $pr, $mapper);

    return [$svc, $mapper, $host];
}

test('auth headers include timestamp + signature + bearer token', function () {
    setAccurateConfig();

    $auth = new AccurateAuth();
    $headers = $auth->buildHeaders(now());

    expect($headers['Authorization'])->toBe('Bearer tok');
    expect($headers['X-Api-Timestamp'])->toBeString();
    expect($headers['X-Api-Signature'])->toBeString();
    expect($headers['X-Api-Signature'])->not->toBeEmpty();
});

test('signature generation matches expected base64 hmac', function () {
    $auth = new AccurateAuth();
    $ts = '2026-05-28T10:30:00+07:00';
    $secret = 'abc123';

    $expected = base64_encode(hash_hmac('sha256', $ts, $secret, true));
    expect($auth->signatureForTimestamp($ts, $secret))->toBe($expected);
});

test('host discovery fetches and caches host', function () {
    setAccurateConfig();
    Cache::forget('accurate.host');

    Http::fake([
        'https://account.accurate.id/api/api-token.do' => Http::response([
            's' => true,
            'd' => ['database' => ['host' => 'https://host.example']],
        ], 200),
    ]);

    [$svc, $mapper, $host] = makeAccurateServices();

    expect($host->getHost())->toBe('https://host.example/accurate');
    expect($host->getHost())->toBe('https://host.example/accurate');

    Http::assertSentCount(1);
});

test('http client follows 302 redirects and preserves headers', function () {
    setAccurateConfig();

    $auth = new AccurateAuth();
    $http = new AccurateHttpClient($auth);

    Http::fake([
        'https://account.accurate.id/api/api-token.do' => Http::response('', 302, ['Location' => 'https://account.accurate.id/api/api-token-redirected.do']),
        'https://account.accurate.id/api/api-token-redirected.do' => Http::response([
            's' => true,
            'd' => ['database' => ['host' => 'https://host.example']],
        ], 200),
    ]);

    $json = $http->post('https://account.accurate.id/api/api-token.do');

    expect($json['d']['database']['host'])->toBe('https://host.example');

    Http::assertSentCount(2);
});

test('search merges pr and po results and normalizes document_type to pr/po', function () {
    setAccurateConfig();
    Cache::forget('accurate.host');

    Http::fake([
        'https://account.accurate.id/api/api-token.do' => Http::response([
            's' => true,
            'd' => ['database' => ['host' => 'https://host.example']],
        ], 200),
        'https://host.example/accurate/api/purchase-requisition/list.do*' => Http::response([
            's' => true,
            'd' => [
                ['id' => 11],
            ],
        ], 200),
        'https://host.example/accurate/api/purchase-order/list.do*' => Http::response([
            's' => true,
            'd' => [
                ['id' => 22],
            ],
        ], 200),
        'https://host.example/accurate/api/purchase-requisition/detail.do*' => Http::response([
            's' => true,
            'd' => [
                'id' => 11,
                'number' => 'PR.001',
                'status' => 'DRAFT',
                'statusName' => 'Draf',
                'transDateView' => '28/05/2026',
                'detailItem' => [
                    [
                        'item' => ['id' => 6063],
                        'detailName' => 'Item A',
                        'quantity' => 1,
                        'itemUnit' => ['name' => 'PCS'],
                    ],
                ],
            ],
        ], 200),
        'https://host.example/accurate/api/purchase-order/detail.do*' => Http::response([
            's' => true,
            'd' => [
                'id' => 22,
                'number' => 'PO.001',
                'status' => 'ONPROCESS',
                'statusName' => 'Onprocess',
                'transDateView' => '28/05/2026',
                'detailItem' => [
                    [
                        'item' => ['id' => 6063],
                        'detailName' => 'Item A',
                        'quantity' => 1,
                        'itemUnit' => ['name' => 'PCS'],
                    ],
                ],
            ],
        ], 200),
    ]);

    [$svc] = makeAccurateServices();

    $results = $svc->search('PO.0', null, 5);

    expect($results)->toHaveCount(2);
    expect(collect($results)->pluck('document_type')->all())->toContain(DocumentTypes::PR, DocumentTypes::PO);
});

test('detail fetch creates document and items', function () {
    setAccurateConfig();
    Cache::forget('accurate.host');

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
                'charField5' => 'Tujuan',
                'charField7' => 'ShipTo',
                'charField8' => 'Requester',
                'charField9' => 'Dept',
                'charField10' => 'Creator',
                'detailItem' => [
                    [
                        'item' => ['id' => 6063],
                        'detailName' => 'Item A',
                        'detailNotes' => 'Note',
                        'quantity' => 1,
                        'itemUnit' => ['name' => 'PCS'],
                    ],
                ],
            ],
        ], 200),
    ]);

    [$svc] = makeAccurateServices();

    $doc = $svc->createFromAccurateDetail(DocumentTypes::PO, '22');

    expect(Document::query()->count())->toBe(1);
    expect($doc->document_number)->toBe('PO.001');
    expect($doc->document_type)->toBe(DocumentTypes::PO);
    expect($doc->status)->toBeNull();
    expect($doc->tujuan_pembelian)->toBe('Tujuan');
    expect($doc->items()->count())->toBe(1);
});

test('required mapping failure creates no document', function () {
    setAccurateConfig();
    Cache::forget('accurate.host');

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
                // Missing detailItem
            ],
        ], 200),
    ]);

    [$svc] = makeAccurateServices();

    expect(fn () => $svc->createFromAccurateDetail(DocumentTypes::PO, '22'))->toThrow(AccurateException::class);
    expect(Document::query()->count())->toBe(0);
});

test('not found creates no document', function () {
    setAccurateConfig();
    Cache::forget('accurate.host');

    Http::fake([
        'https://account.accurate.id/api/api-token.do' => Http::response([
            's' => true,
            'd' => ['database' => ['host' => 'https://host.example']],
        ], 200),
        'https://host.example/accurate/api/purchase-order/detail.do*' => Http::response([
            's' => false,
            'd' => ['message' => 'Not found'],
        ], 200),
    ]);

    [$svc] = makeAccurateServices();

    expect(fn () => $svc->createFromAccurateDetail(DocumentTypes::PO, '999'))->toThrow(AccurateException::class);
    expect(Document::query()->count())->toBe(0);
});

test('refresh with item change resets affected item checks and deletes affected photos', function () {
    setAccurateConfig();
    Cache::forget('accurate.host');
    Storage::fake('r2');

    $actor = User::create([
        'username' => 'warehouse1',
        'password' => Hash::make('secret'),
        'role' => UserRoles::WAREHOUSE,
    ]);

    $doc = Document::create([
        'accurate_id' => '22',
        'document_number' => 'PO.001',
        'document_type' => DocumentTypes::PO,
        'status' => DocumentStatuses::WAREHOUSE_SUBMITTED,
        'accurate_synced_at' => now(),
    ]);

    $item = DocumentItem::create([
        'document_id' => $doc->id,
        'accurate_item_id' => '6063',
        'nama_barang' => 'Item A',
        'keterangan' => null,
        'quantity' => 1,
        'satuan' => 'PCS',
        'match_status' => ItemMatchStatuses::SESUAI,
        'warehouse_reason' => null,
    ]);

    $photoSvc = new ItemPhotoService();
    $photo = $photoSvc->upload($item, UploadedFile::fake()->image('a.jpg'), $actor);
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
                        // Quantity changed -> should reset & delete photo.
                        'quantity' => 2,
                        'itemUnit' => ['name' => 'PCS'],
                    ],
                ],
            ],
        ], 200),
    ]);

    [$accurate, $mapper] = makeAccurateServices();
    $refresh = new AccurateRefreshService($accurate, $mapper, $photoSvc, new ActivityLogService());

    $refresh->refresh($doc);

    $item->refresh();
    expect($item->match_status)->toBeNull();
    expect($item->warehouse_reason)->toBeNull();
    expect(ItemPhoto::query()->count())->toBe(0);
    Storage::disk('r2')->assertMissing($photo->path);

    $doc->refresh();
    expect($doc->status)->toBe(DocumentStatuses::SPV_REJECTED);
});
