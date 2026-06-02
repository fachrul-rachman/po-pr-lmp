# Accurate Integration

Dokumen ini mendefinisikan integrasi Accurate untuk PR/PO saja.

## Scope

Integrasi Accurate hanya digunakan untuk:

```text
PR search
PR detail
PO search
PO detail
API token validation
host discovery
```

Tidak termasuk:

```text
create PR
update PR
approve PR
create PO
update PO
approve PO
receive item
close PO
purchase invoice
payment
inventory mutation
```

Aplikasi hanya melakukan `GET`/read terhadap data PR/PO Accurate.

## Authorization Model

Aplikasi menggunakan Accurate API Token.

Required credentials:

```env
ACCURATE_API_TOKEN=
ACCURATE_SIGNATURE_SECRET=
ACCURATE_DEFAULT_HOST=
```

Setiap request ke Accurate wajib mengirim header:

```http
Authorization: Bearer {ACCURATE_API_TOKEN}
X-Api-Timestamp: {timestamp}
X-Api-Signature: {signature}
```

`X-Api-Signature` dibuat dari:

```text
HMAC-SHA256(timestamp, ACCURATE_SIGNATURE_SECRET)
```

Output signature harus di-encode Base64.

## Timestamp Rule

Gunakan timestamp saat request dibuat.

Recommended format:

```text
ISO 8601
```

Contoh:

```text
2026-05-28T10:30:00+07:00
```

Accurate menolak request jika timestamp berbeda terlalu jauh dari waktu server.

## Host Discovery

Sebelum request PR/PO, aplikasi perlu tahu host database Accurate.

Endpoint:

```http
POST https://account.accurate.id/api/api-token.do
```

Response berisi host database:

```json
{
  "s": true,
  "d": {
    "database": {
      "host": "https://iris.accurate.id"
    }
  }
}
```

Host tersebut digunakan sebagai base URL request PR/PO.

Contoh:

```text
https://iris.accurate.id/accurate/api/...
```

## Host Change Handling

Host Accurate dapat berubah.

Rules:

* HTTP client harus support redirect `308 Permanent Redirect`.
* Authorization header harus tetap dikirim saat redirect.
* Original HTTP method harus dipertahankan saat redirect.
* Aplikasi tetap perlu update host terbaru dari `/api-token.do`.
* Lakukan pengecekan host berkala.

Recommended:

```text
Refresh Accurate host every 30 days.
```

## Rate Limit

Accurate API memiliki batas:

```text
8 requests per second
8 parallel processes
```

Implementation rule:

* Batasi request Accurate di service layer.
* Jangan melakukan bulk fetch paralel tanpa throttling.
* Jika banyak data perlu diambil, gunakan queue atau sequential request.

## Accurate Service

Gunakan service khusus:

```text
AccurateService
AccurateMapper
```

Controller tidak boleh langsung call Accurate.

Suggested responsibilities:

```text
AccurateService
- build auth headers
- call /api-token.do
- search PR
- get PR detail
- search PO
- get PO detail
- handle redirect
- handle error
- return raw response

AccurateMapper
- map raw PR detail to internal snapshot
- map raw PO detail to internal snapshot
- normalize item data
```

## Supported Document Types

```text
PR
PO
```

Internal storage enum (database):

```text
document_type = pr | po
```

Normalization rule:

* Accurate `PR` MUST be stored as `pr`.
* Accurate `PO` MUST be stored as `po`.

## PR Endpoints

Base path:

```text
/api/purchase-requisition
```

Required operations:

```http
GET {host}/api/purchase-requisition/list.do
GET {host}/api/purchase-requisition/detail.do
```

Detail lookup supports:

```text
id
number
```

Preferred detail request:

```http
GET {host}/api/purchase-requisition/detail.do?id={accurate_id}
```

Fallback:

```http
GET {host}/api/purchase-requisition/detail.do?number={document_number}
```

## PO Endpoints

Base path:

```text
/api/purchase-order
```

Required operations:

```http
GET {host}/api/purchase-order/list.do
GET {host}/api/purchase-order/detail.do
```

Detail lookup supports:

```text
id
number
```

Preferred detail request:

```http
GET {host}/api/purchase-order/detail.do?id={accurate_id}
```

Fallback:

```http
GET {host}/api/purchase-order/detail.do?number={document_number}
```

## Search Behavior

Search input rule:

```text
minimum 4 characters
maximum 5 results
```

User can:

* choose PR;
* choose PO;
* leave type empty and let system show both types.

If type is selected:

```text
PR selected → search PR only
PO selected → search PO only
```

If type is empty:

```text
search PR and PO
merge result
show choices
```

If the same input matches PR and PO:

```text
show both options
user must choose one
```

Do not auto-pick.

## Search Result Shape

Normalize PR/PO search result into:

```json
{
  "document_type": "PR",
  "accurate_id": 36302,
  "document_number": "DFT.04559 [INI_NOMOR-1]",
  "accurate_status": "DRAFT",
  "accurate_status_name": "Draf",
  "trans_date": "28/05/2026"
}
```

For PO:

```json
{
  "document_type": "PO",
  "accurate_id": 30750,
  "document_number": "PO.K.26.05.120",
  "accurate_status": "ONPROCESS",
  "accurate_status_name": "Menunggu diproses",
  "trans_date": "19/05/2026"
}
```

## Duplicate Rule

Before creating a Validation Card, check existing card by:

```text
document_type
accurate_document_id
document_number
```

If card exists:

* do not create new card;
* show warning;
* show Warehouse owner name;
* only open detail if current user has permission.

1 PR/PO can only have 1 Validation Card.

## Accurate API Failure

If Accurate API fails:

* do not create card;
* do not submit form;
* show clear error;
* log failed attempt if useful;
* do not fallback to manual card.

Manual card creation is forbidden.

## Raw JSON Storage

The application MAY store raw Accurate JSON for audit/debug if explicitly implemented.

If implemented, recommended storage cases:

```text
initial fetch
detail fetch
refresh from Accurate
```

Raw JSON is used for:

* audit;
* debugging;
* remapping if Accurate field behavior changes.

Raw JSON should not be shown to normal users.

Admin/debug view may show raw JSON if implemented.

## Snapshot Model

If raw JSON storage is implemented, the application MAY keep:

```text
raw_accurate_json
normalized_snapshot
```

`raw_accurate_json` adalah response asli Accurate.

`normalized_snapshot` adalah data bersih untuk UI dan workflow.

## PR Header Mapping

Map PR detail response into internal snapshot:

| Internal Field                | Accurate Field           |
| ----------------------------- | ------------------------ |
| `document_type`               | fixed `PR`               |
| `accurate_document_id`        | `d.id`                   |
| `document_number`             | `d.number`               |
| `manual_number`               | `d.manualApprovalNumber` |
| `trans_date`                  | `d.transDate`            |
| `trans_date_view`             | `d.transDateView`        |
| `accurate_status`             | `d.status`               |
| `accurate_status_name`        | `d.statusName`           |
| `approval_status`             | `d.approvalStatus`       |
| `request_type`                | `d.requisitionTypeName`  |
| `description`                 | `d.description`          |
| `created_by_accurate_user_id` | `d.createdBy`            |

## PR Custom Field Mapping

PR custom fields:

| Internal Field         | Accurate Field  |
| ---------------------- | --------------- |
| `suggested_source`     | `d.charField4`  |
| `purchase_purpose`     | `d.charField5`  |
| `ship_to`              | `d.charField7`  |
| `requested_by`         | `d.charField8`  |
| `department`           | `d.charField9`  |
| `created_by_requester` | `d.charField10` |

Use `charFieldX` as source of truth.

`searchCharFieldX` may exist but is optional.

## PR Item Mapping

Source:

```text
d.detailItem[]
```

Map each item:

| Internal Field       | Accurate Field                  |
| -------------------- | ------------------------------- |
| `accurate_detail_id` | `detailItem[].id`               |
| `accurate_item_id`   | `detailItem[].item.id`          |
| `item_code`          | `detailItem[].item.no`          |
| `item_name`          | `detailItem[].detailName`       |
| `master_item_name`   | `detailItem[].item.name`        |
| `quantity`           | `detailItem[].quantity`         |
| `unit_name`          | `detailItem[].itemUnit.name`    |
| `required_date`      | `detailItem[].requiredDate`     |
| `required_date_view` | `detailItem[].requiredDateView` |
| `ordered_quantity`   | `detailItem[].orderedQuantity`  |
| `received_quantity`  | `detailItem[].receivedQuantity` |
| `closed`             | `detailItem[].closed`           |
| `unit_price`         | `detailItem[].unitPrice`        |
| `total_price`        | `detailItem[].totalPrice`       |
| `detail_notes`       | `detailItem[].detailNotes`      |

Important rule:

```text
Use detailName as display item name.
Do not use item.name as primary display name.
```

Reason:

```text
item.name can be generic master item name.
detailName is the name used in the PR/PO row.
```

## PO Header Mapping

Map PO detail response into internal snapshot:

| Internal Field                | Accurate Field           |
| ----------------------------- | ------------------------ |
| `document_type`               | fixed `PO`               |
| `accurate_document_id`        | `d.id`                   |
| `document_number`             | `d.number`               |
| `manual_number`               | `d.manualApprovalNumber` |
| `trans_date`                  | `d.transDate`            |
| `trans_date_view`             | `d.transDateView`        |
| `ship_date`                   | `d.shipDate`             |
| `ship_date_view`              | `d.shipDateView`         |
| `accurate_status`             | `d.status`               |
| `accurate_status_name`        | `d.statusName`           |
| `approval_status`             | `d.approvalStatus`       |
| `description`                 | `d.description`          |
| `vendor_id`                   | `d.vendor.id`            |
| `vendor_name`                 | `d.vendor.name`          |
| `total_amount`                | `d.totalAmount`          |
| `sub_total`                   | `d.subTotal`             |
| `taxable`                     | `d.taxable`              |
| `created_by_accurate_user_id` | `d.createdBy`            |

## PO Item Mapping

Source:

```text
d.detailItem[]
```

Map each item:

| Internal Field                | Accurate Field                            |
| ----------------------------- | ----------------------------------------- |
| `accurate_detail_id`          | `detailItem[].id`                         |
| `accurate_item_id`            | `detailItem[].item.id`                    |
| `item_code`                   | `detailItem[].item.no`                    |
| `item_name`                   | `detailItem[].detailName`                 |
| `master_item_name`            | `detailItem[].item.name`                  |
| `quantity`                    | `detailItem[].quantity`                   |
| `unit_name`                   | `detailItem[].itemUnit.name`              |
| `unit_price`                  | `detailItem[].unitPrice`                  |
| `total_price`                 | `detailItem[].totalPrice`                 |
| `remaining_quantity`          | `detailItem[].remainingQuantity`          |
| `available_quantity`          | `detailItem[].availableQuantity`          |
| `ship_quantity`               | `detailItem[].shipQuantity`               |
| `return_quantity`             | `detailItem[].returnQuantity`             |
| `closed`                      | `detailItem[].closed`                     |
| `detail_notes`                | `detailItem[].detailNotes`                |
| `purchase_requisition_id`     | `detailItem[].purchaseRequisition.id`     |
| `purchase_requisition_number` | `detailItem[].purchaseRequisition.number` |

## Normalized Detail Shape

After mapping, PR/PO detail should become:

```json
{
  "document_type": "PR",
  "accurate_document_id": 36302,
  "document_number": "DFT.04559 [INI_NOMOR-1]",
  "manual_number": "INI_NOMOR-1",
  "trans_date": "28/05/2026",
  "accurate_status": "DRAFT",
  "accurate_status_name": "Draf",
  "description": "INI BAGIAN KETERANGAN",
  "suggested_source": "INI SUMBER YANG DISARANKAN",
  "purchase_purpose": "INI TUJUAN PEMBELIAN",
  "ship_to": "INI DIKIRIM KE",
  "requested_by": "INI DIMINTA OLEH",
  "department": "INI DEPARTMENT",
  "created_by_requester": "INI DIBUAT OLEH",
  "items": [
    {
      "accurate_detail_id": 39052,
      "accurate_item_id": 6063,
      "item_code": "LEMBAR14",
      "item_name": "INI NAMA BARANG",
      "master_item_name": "Umum-LEMBAR",
      "quantity": 1,
      "unit_name": "LEMBAR",
      "unit_price": 0,
      "total_price": 0,
      "required_date": "28/05/2026",
      "closed": false
    }
  ]
}
```

## Refresh from Accurate

Refresh only applies to an existing stored document.

Rules:

* only Admin can refresh;
* fetch detail again from Accurate;
* update stored document header and items based on latest Accurate detail;
* preserve item checklist and photos only when the item still matches;
* mark new items as unchecked;
* remove deleted Accurate items and delete their photos from storage and database;
* log refresh action.

Matching item during refresh should use:

```text
accurate_item_id
```

Fallback if Accurate item ID is not reliable (optional, if implemented):

```text
accurate_item_id + item_code + item_name
```

If match is uncertain, treat as new item.

Workflow status impact:

* If refresh changes stored data, the document workflow status MUST follow `WORKFLOW.md`.
* The integration MUST NOT introduce new workflow statuses.

## Draft Document Rule

Accurate document with status `DRAFT` is allowed.

Rules:

* allow create Validation Card;
* allow process until `FINANCE_CLOSED`;
* show warning in UI;
* no automatic refresh required when Accurate status changes.

Warning text:

```text
Dokumen ini masih DRAFT di Accurate. Data dapat berubah.
```

## Error Handling

If Accurate response has:

```json
{
  "s": false
}
```

Treat as failed request.

Do not create or update card.

Store error message for debugging if available.

Common error handling:

| Case              | Behavior                            |
| ----------------- | ----------------------------------- |
| Invalid token     | show integration error              |
| Invalid signature | show integration error              |
| Timestamp invalid | show integration error              |
| Not found         | show document not found             |
| Rate limited      | retry later or show temporary error |
| 308 redirect      | follow redirect and update host     |
| Network error     | stop process and show error         |

## Accurate Client Requirements

HTTP client must:

* send auth headers on every request;
* generate fresh timestamp per request;
* generate signature per request;
* follow `308` redirect;
* preserve original HTTP method on redirect;
* preserve Authorization header on redirect;
* timeout safely;
* return structured error.

Recommended timeout:

```text
10-30 seconds
```

## Security

Never expose to frontend:

```text
ACCURATE_API_TOKEN
ACCURATE_SIGNATURE_SECRET
```

Never store credentials in database unless explicitly required.

Never commit credentials.

All Accurate requests must be made server-side.

## Testing Requirements

Mock Accurate API for tests.

Minimum tests:

* build auth headers;
* validate signature generation;
* host discovery success;
* PR search returns normalized result;
* PO search returns normalized result;
* PR detail maps header fields;
* PR detail maps custom fields;
* PR detail maps item fields;
* PO detail maps vendor and item fields;
* duplicate card prevention;
* Accurate failure blocks card creation;
* refresh preserves matched item checklist;
* refresh adds new item as unchecked;
* refresh removes deleted item from active display;
* refresh applies status rollback rules from `WORKFLOW.md` (no new status).
