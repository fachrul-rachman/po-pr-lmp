<?php

namespace App\Services\Accurate;

use App\Support\Enums\DocumentTypes;

final class AccurateDocumentMapper
{
    /**
     * @return array<int, array{document_type:string, accurate_id:string, document_number:string, accurate_status:?string, accurate_status_name:?string, trans_date:?string, dibuat_oleh:?string}>
     */
    public function mapList(string $type, array $json): array
    {
        $docType = $this->normalizeType($type);

        $items = $this->extractListItems($json);

        $out = [];
        foreach ($items as $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = $row['id'] ?? null;
            $number = $row['number'] ?? null;
            if ($id === null || ! is_scalar($id) || ! is_string($number) || $number === '') {
                continue;
            }

            $out[] = [
                'document_type' => $docType,
                'accurate_id' => (string) $id,
                'document_number' => $number,
                'accurate_status' => is_string($row['status'] ?? null) ? $row['status'] : null,
                'accurate_status_name' => is_string($row['statusName'] ?? null) ? $row['statusName'] : null,
                'trans_date' => is_string($row['transDateView'] ?? null) ? $row['transDateView'] : (is_string($row['transDate'] ?? null) ? $row['transDate'] : null),
                // Optional: list endpoints may or may not include creator fields.
                'dibuat_oleh' => is_string($row['charField10'] ?? null)
                    ? $row['charField10']
                    : (is_string($row['createdByName'] ?? null) ? $row['createdByName'] : null),
            ];
        }

        return $out;
    }

    /**
     * Map Accurate detail response (`d`) into the application document header and items.
     *
     * @return array{document: array, items: array<int, array>}
     */
    public function mapDetail(string $type, array $json): array
    {
        $docType = $this->normalizeType($type);

        $d = $json['d'] ?? null;
        if (! is_array($d)) {
            throw AccurateException::mapping('Accurate detail response missing d object.');
        }

        $accurateId = $d['id'] ?? null;
        $number = $d['number'] ?? null;

        if (! is_scalar($accurateId) || ! is_string($number) || $number === '') {
            throw AccurateException::mapping('Accurate detail response missing required id/number.');
        }

        // Header fields used by this application (see PRODUCT-SPEC.md and DATA-MODEL.md).
        $doc = [
            'accurate_id' => (string) $accurateId,
            'document_number' => $number,
            'document_type' => $docType,
            'accurate_trans_date' => is_string($d['transDateView'] ?? null)
                ? $d['transDateView']
                : (is_string($d['transDate'] ?? null) ? $d['transDate'] : null),

            // Custom fields (nullable in DB).
            'tujuan_pembelian' => is_string($d['charField5'] ?? null) ? $d['charField5'] : null,
            'dikirim_ke' => is_string($d['charField7'] ?? null) ? $d['charField7'] : null,
            'department' => is_string($d['charField9'] ?? null) ? $d['charField9'] : null,
            'dibuat_oleh' => is_string($d['charField10'] ?? null) ? $d['charField10'] : null,
            'diminta_oleh' => is_string($d['charField8'] ?? null) ? $d['charField8'] : null,
        ];

        $detailItems = $d['detailItem'] ?? null;
        if (! is_array($detailItems)) {
            throw AccurateException::mapping('Accurate detail response missing detailItem array.');
        }

        $items = [];
        foreach ($detailItems as $row) {
            if (! is_array($row)) {
                continue;
            }

            $itemObj = $row['item'] ?? null;
            $itemUnit = $row['itemUnit'] ?? null;

            $accurateItemId = is_array($itemObj) ? ($itemObj['id'] ?? null) : null;
            $namaBarang = $row['detailName'] ?? null;
            $qty = $row['quantity'] ?? null;
            $unitName = is_array($itemUnit) ? ($itemUnit['name'] ?? null) : null;

            if (! is_scalar($accurateItemId) || ! is_string($namaBarang) || $namaBarang === '' || ! is_numeric($qty) || ! is_string($unitName) || $unitName === '') {
                throw AccurateException::mapping('Accurate detail item missing required fields (item.id/detailName/quantity/itemUnit.name).');
            }

            $items[] = [
                'accurate_item_id' => (string) $accurateItemId,
                'nama_barang' => $namaBarang,
                'keterangan' => is_string($row['detailNotes'] ?? null) ? $row['detailNotes'] : null,
                'quantity' => $qty,
                'satuan' => $unitName,
            ];
        }

        return [
            'document' => $doc,
            'items' => $items,
        ];
    }

    private function normalizeType(string $type): string
    {
        return match (strtolower($type)) {
            'po' => DocumentTypes::PO,
            'pr' => DocumentTypes::PR,
            default => throw AccurateException::mapping('Unknown document type: '.$type),
        };
    }

    /**
     * @return array<int, mixed>
     */
    private function extractListItems(array $json): array
    {
        $d = $json['d'] ?? null;

        if (is_array($d) && array_is_list($d)) {
            return $d;
        }

        if (is_array($d) && isset($d['list']) && is_array($d['list'])) {
            return $d['list'];
        }

        if (is_array($d) && isset($d['data']) && is_array($d['data'])) {
            return $d['data'];
        }

        return [];
    }
}
