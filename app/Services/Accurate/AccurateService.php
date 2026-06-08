<?php

namespace App\Services\Accurate;

use App\Models\Document;
use App\Models\DocumentItem;
use App\Support\Enums\DocumentTypes;
use Illuminate\Support\Facades\DB;

final class AccurateService
{
    public function __construct(
        private AccuratePurchaseOrderClient $poClient,
        private AccuratePurchaseRequisitionClient $prClient,
        private AccurateDocumentMapper $mapper,
    ) {}

    /**
     * @param  string|null  $type  'po'|'pr'|null
     * @return array<int, array{document_type:string, accurate_id:string, document_number:string, accurate_status:?string, accurate_status_name:?string, trans_date:?string, dibuat_oleh:?string}>
     */
    public function search(string $term, ?string $type = null, int $limit = 5, ?string $company = null): array
    {
        $term = trim($term);
        if (mb_strlen($term) < 4) {
            throw AccurateException::integration('Search term must be at least 4 characters.');
        }

        if ($type === DocumentTypes::PO) {
            $json = $this->poClient->listByNumberContains($term, $limit, $company);
            $mapped = $this->mapper->mapList(DocumentTypes::PO, $json);

            // Some Accurate list endpoints can return minimal rows (id only).
            // If so, hydrate by fetching detail for each id to produce the required search result shape.
            if (count($mapped) > 0) {
                return $mapped;
            }

            return $this->hydrateSearchResultsFromListIds(DocumentTypes::PO, $json, $limit, $company);
        }

        if ($type === DocumentTypes::PR) {
            $json = $this->prClient->listByNumberContains($term, $limit, $company);
            $mapped = $this->mapper->mapList(DocumentTypes::PR, $json);

            if (count($mapped) > 0) {
                return $mapped;
            }

            return $this->hydrateSearchResultsFromListIds(DocumentTypes::PR, $json, $limit, $company);
        }

        $poJson = $this->poClient->listByNumberContains($term, $limit, $company);
        $prJson = $this->prClient->listByNumberContains($term, $limit, $company);

        $po = $this->mapper->mapList(DocumentTypes::PO, $poJson);
        $pr = $this->mapper->mapList(DocumentTypes::PR, $prJson);

        if (count($po) === 0) {
            $po = $this->hydrateSearchResultsFromListIds(DocumentTypes::PO, $poJson, $limit, $company);
        }
        if (count($pr) === 0) {
            $pr = $this->hydrateSearchResultsFromListIds(DocumentTypes::PR, $prJson, $limit, $company);
        }

        return array_values(array_merge($pr, $po));
    }

    public function fetchDetail(string $type, string|int $accurateId, ?string $company = null): array
    {
        $type = strtolower($type);

        return match ($type) {
            DocumentTypes::PO => $this->poClient->detailById($accurateId, $company),
            DocumentTypes::PR => $this->prClient->detailById($accurateId, $company),
            default => throw AccurateException::integration('Invalid document type.'),
        };
    }

    public function createFromAccurateDetail(string $type, string|int $accurateId, ?string $company = null): Document
    {
        $json = $this->fetchDetail($type, $accurateId, $company);
        $mapped = $this->mapper->mapDetail($type, $json);

        // Persist company + trans date when available so future refresh knows which credentials to use.
        $mapped['document']['accurate_company'] = $company ? strtolower(trim($company)) : null;

        $docNumber = $mapped['document']['document_number'];

        $existing = Document::query()->where('document_number', $docNumber)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($mapped) {
            $doc = Document::create([
                ...$mapped['document'],
                'status' => null,
                'accurate_synced_at' => now(),
            ]);

            foreach ($mapped['items'] as $row) {
                DocumentItem::create([
                    'document_id' => $doc->id,
                    'accurate_item_id' => $row['accurate_item_id'],
                    'nama_barang' => $row['nama_barang'],
                    'keterangan' => $row['keterangan'],
                    'quantity' => $row['quantity'],
                    'satuan' => $row['satuan'],
                    'match_status' => null,
                    'warehouse_reason' => null,
                ]);
            }

            return $doc;
        });
    }

    /**
     * @param  array<string, mixed>  $listJson
     * @return array<int, array{document_type:string, accurate_id:string, document_number:string, accurate_status:?string, accurate_status_name:?string, trans_date:?string, dibuat_oleh:?string}>
     */
    private function hydrateSearchResultsFromListIds(string $type, array $listJson, int $limit, ?string $company = null): array
    {
        $type = strtolower($type);

        $ids = $this->extractListIds($listJson);
        if (count($ids) === 0) {
            return [];
        }

        $ids = array_slice($ids, 0, max(1, $limit));

        $out = [];
        foreach ($ids as $id) {
            $detail = $this->fetchDetail($type, (string) $id, $company);
            $d = $detail['d'] ?? null;
            if (! is_array($d)) {
                continue;
            }

            $accurateId = $d['id'] ?? null;
            $number = $d['number'] ?? null;

            if (! is_scalar($accurateId) || ! is_string($number) || $number === '') {
                continue;
            }

            $out[] = [
                'document_type' => $type,
                'accurate_id' => (string) $accurateId,
                'document_number' => $number,
                'accurate_status' => is_string($d['status'] ?? null) ? $d['status'] : null,
                'accurate_status_name' => is_string($d['statusName'] ?? null) ? $d['statusName'] : null,
                'trans_date' => is_string($d['transDateView'] ?? null) ? $d['transDateView'] : (is_string($d['transDate'] ?? null) ? $d['transDate'] : null),
                'dibuat_oleh' => is_string($d['charField10'] ?? null) ? $d['charField10'] : null,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<int, string>
     */
    private function extractListIds(array $json): array
    {
        $d = $json['d'] ?? null;
        $items = null;

        if (is_array($d) && array_is_list($d)) {
            $items = $d;
        } elseif (is_array($d) && isset($d['list']) && is_array($d['list'])) {
            $items = $d['list'];
        } elseif (is_array($d) && isset($d['data']) && is_array($d['data'])) {
            $items = $d['data'];
        }

        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach ($items as $row) {
            if (is_scalar($row)) {
                $out[] = (string) $row;
                continue;
            }

            if (is_array($row) && isset($row['id']) && is_scalar($row['id'])) {
                $out[] = (string) $row['id'];
                continue;
            }
        }

        return array_values(array_unique(array_filter($out, fn ($v) => is_string($v) && $v !== '')));
    }
}
