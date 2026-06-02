# WORKFLOW.md

## Purpose

This file defines PO/PR workflow status, transitions, validation, rejection, closure, Accurate refresh impact, and Admin override.

Role permissions are defined in `ROLE-PERMISSION.md`.

## Workflow Scope

* Workflow applies to PO and PR.
* PO and PR use the same workflow.
* Workflow status is stored at document level.
* Item checking is stored at item level.
* One document can contain multiple items.
* If one item blocks approval, the whole document is blocked.

## Status Values

The application MUST only use these workflow statuses:

```text
warehouse_submitted
spv_approved
spv_rejected
finance_rejected
finance_closed
```

## Status Meaning

### `warehouse_submitted`

Warehouse has completed item checking and submitted the document to SPV.

### `spv_approved`

SPV has approved the Warehouse submission and sent the document to Finance.

### `spv_rejected`

SPV has rejected the document and returned it to Warehouse.

### `finance_rejected`

Finance has rejected the document and returned it to SPV.

### `finance_closed`

Finance has closed the document and the workflow is complete.

## Pre-Submission Rule

* Searching a PO/PR number is not a workflow status.
* Viewing Accurate data before Warehouse submit is not a workflow status.
* The application MUST NOT create draft workflow status.
* The first workflow status MUST be `warehouse_submitted`.

## Main Flow

The normal workflow MUST be:

```text
Warehouse submit
→ warehouse_submitted
→ SPV approve
→ spv_approved
→ Finance close
→ finance_closed
```

## Rejection Flow

The rejection workflow MUST be:

```text
warehouse_submitted
→ SPV reject
→ spv_rejected
→ Warehouse edit and resubmit
→ warehouse_submitted
```

```text
spv_approved
→ Finance reject
→ finance_rejected
→ SPV approve again
→ spv_approved
```

```text
finance_rejected
→ SPV reject to Warehouse
→ spv_rejected
→ Warehouse edit and resubmit
→ warehouse_submitted
```

## Warehouse Submit Rules

Warehouse can submit only when all rules are true:

* Every item has been checked.
* Every item has `sesuai` or `tidak_sesuai` selected.
* Every item has at least one photo.
* Every item marked `tidak_sesuai` has item reason.
* The document is not `finance_closed`.

After Warehouse submits:

* Status MUST become `warehouse_submitted`.
* Submit time MUST be stored.
* Submit actor MUST be stored.
* The document MUST appear in SPV Request.
* The document MUST appear in Warehouse Riwayat.

## Warehouse Resubmit Rules

Warehouse can resubmit only when status is `spv_rejected`.

After Warehouse resubmits:

* Status MUST become `warehouse_submitted`.
* New submit time MUST be stored.
* New submit actor MUST be stored.
* The document MUST return to SPV Request.

## Duplicate Submit Rule

* A document MUST NOT be submitted twice by Warehouse while status is still `warehouse_submitted`, `spv_approved`, `finance_rejected`, or `finance_closed`.
* Warehouse can submit again only after status becomes `spv_rejected`.

## SPV Approval Rules

SPV can approve only when all rules are true:

* Current status is `warehouse_submitted`.
* All items are marked `sesuai`.
* No item is marked `tidak_sesuai`.
* Every item has at least one photo.
* The document is not `finance_closed`.

After SPV approves:

* Status MUST become `spv_approved`.
* SPV approval time MUST be stored.
* SPV actor MUST be stored.
* The document MUST appear in Finance Request.
* The document MUST appear in SPV Riwayat.

## SPV Rejection Rules

SPV can reject when status is:

* `warehouse_submitted`
* `finance_rejected`

SPV rejection requires:

* Document-level rejection reason.
* Optional item-level rejection reasons.

After SPV rejects:

* Status MUST become `spv_rejected`.
* SPV rejection time MUST be stored.
* SPV actor MUST be stored.
* The document MUST appear in Warehouse Non Valid.
* The document MUST appear in SPV Non Valid.

## Finance Close Rules

Finance can close only when all rules are true:

* Current status is `spv_approved`.
* No item is marked `tidak_sesuai`.
* The document is not `finance_closed`.

After Finance closes:

* Status MUST become `finance_closed`.
* Finance close time MUST be stored.
* Finance actor MUST be stored.
* The document MUST become read-only for Warehouse, SPV, Finance, and Purchasing.
* The document MUST appear in Finance Riwayat.

## Finance Rejection Rules

Finance can reject only when status is `spv_approved`.

Finance rejection requires:

* Document-level rejection reason.
* Optional item-level rejection reasons.

After Finance rejects:

* Status MUST become `finance_rejected`.
* Finance rejection time MUST be stored.
* Finance actor MUST be stored.
* The document MUST appear in SPV Non Close.
* The document MUST appear in Finance Riwayat.

## Edit Rules by Status

### `warehouse_submitted`

* Warehouse MUST NOT edit.
* SPV can approve or reject.
* Finance MUST NOT process.

### `spv_rejected`

* Warehouse can edit item checks, item reasons, and item photos.
* Warehouse can delete or replace photos.
* SPV MUST NOT approve until Warehouse resubmits.
* Finance MUST NOT process.

### `spv_approved`

* Warehouse MUST NOT edit.
* SPV MUST NOT edit Warehouse item checks.
* Finance can close or reject.

### `finance_rejected`

* Warehouse MUST NOT edit directly.
* SPV can approve again to Finance.
* SPV can reject to Warehouse.
* Finance MUST NOT close until SPV approves again.

### `finance_closed`

* Warehouse MUST NOT edit.
* SPV MUST NOT edit.
* Finance MUST NOT edit.
* Purchasing MUST NOT edit.
* Admin can override status.

Admin permission detail is defined in `ROLE-PERMISSION.md`.

## Item Check Rules

Each item MUST have:

* Match status: `sesuai` or `tidak_sesuai`
* At least one photo
* Reason if match status is `tidak_sesuai`

The application MUST NOT allow SPV approval when any item is `tidak_sesuai`.

The application MUST NOT allow Finance close when any item is `tidak_sesuai`.

## Reason Rules

* Warehouse item reason is required only when item is marked `tidak_sesuai`.
* SPV rejection requires document-level reason.
* SPV rejection may include item-level reasons.
* Finance rejection requires document-level reason.
* Finance rejection may include item-level reasons.
* Admin override requires override reason.
* System rejection caused by Accurate refresh MUST use system-generated reason.

## Photo Rules

* Every item MUST have at least one photo before Warehouse submit.
* More than one photo per item is allowed.
* Warehouse can delete or replace photos only when the document is editable by Warehouse.
* Deleted photos MUST be deleted from storage.
* Photo storage detail is defined in `DATA-MODEL.md`.

## Accurate Refresh Rule

* Only Admin can refresh from Accurate.
* Refresh can be done at any status, including `finance_closed`.
* Refresh MUST compare saved document data with latest Accurate data.
* If there is no data change, status MUST NOT change.
* If there is data change, item data in the application MUST follow Accurate.
* If refresh adds, removes, or changes items, affected previous item checks and photos MUST be deleted.
* Deleted photos MUST be deleted from storage.
* Refresh action MUST be logged.

Accurate field mapping is defined in `ACCURATE-INTEGRATION.md`.

## Accurate Refresh Status Impact

### If current status is `warehouse_submitted`

When Accurate refresh changes data:

* Status MUST become `spv_rejected`.
* Reason MUST be system-generated.
* The document MUST return to Warehouse for completion.

### If current status is `spv_approved`

When Accurate refresh changes data:

* Status MUST become `finance_rejected`.
* Reason MUST be system-generated.
* The document MUST return to SPV first.

### If current status is `finance_rejected`

When Accurate refresh changes data:

* Status MUST remain `finance_rejected`.
* Reason MUST be system-generated.
* The document MUST remain with SPV.

### If current status is `spv_rejected`

When Accurate refresh changes data:

* Status MUST remain `spv_rejected`.
* Reason MUST be system-generated.
* The document MUST remain with Warehouse.

### If current status is `finance_closed`

When Accurate refresh changes data:

* Status MUST become `finance_rejected`.
* Reason MUST be system-generated.
* The document MUST return to SPV first.

## Admin Override Rule

Admin can override document status to any valid status:

* `warehouse_submitted`
* `spv_approved`
* `spv_rejected`
* `finance_rejected`
* `finance_closed`

Admin override requires:

* Target status
* Override reason
* Admin actor
* Timestamp
* JSON payload log

After Admin override:

* The document MUST appear in the menu that matches the new status.
* The application MUST NOT silently change item checks.
* The application MUST NOT silently delete photos.
* The application MUST NOT call Accurate unless Admin separately performs Accurate refresh.

## Menu Placement by Status

### Warehouse

* `spv_rejected` appears in Warehouse Non Valid.
* Any document submitted by Warehouse appears in Warehouse Riwayat.
* `finance_closed` appears read-only in Warehouse Riwayat.

### SPV

* `warehouse_submitted` appears in SPV Request.
* `spv_rejected` appears in SPV Non Valid.
* `finance_rejected` appears in SPV Non Close.
* Documents processed by SPV appear in SPV Riwayat.

### Finance

* `spv_approved` appears in Finance Request.
* Documents processed by Finance appear in Finance Riwayat.

### Purchasing

* All statuses appear in Purchasing Dashboard.

### Admin

* All statuses appear in Admin document list.

Route detail is defined in `UI-ROUTE.md`.

## Read-Only Rules

A document is read-only when:

* The role does not own the current workflow step.
* The document status is `finance_closed`.
* The screen is a detail view without edit action.
* The role is Purchasing.

## Activity Log Events

The application MUST log these workflow events:

* Warehouse submit
* Warehouse resubmit
* Warehouse edit rejected document
* SPV approve
* SPV reject
* Finance close
* Finance reject
* Admin status override
* Admin Accurate refresh
* System status change caused by Accurate refresh

Each log MUST contain:

* Actor ID
* Actor role
* Action
* Document ID
* Previous status
* New status
* JSON payload
* Timestamp

Database detail is defined in `DATA-MODEL.md`.

## Forbidden Workflow Behavior

The application MUST NOT:

* Create manual PO/PR.
* Create draft workflow status.
* Allow Warehouse submit without checking all items.
* Allow Warehouse submit without photo on every item.
* Allow SPV approve if any item is `tidak_sesuai`.
* Allow Finance close if any item is `tidak_sesuai`.
* Allow Finance process before SPV approval.
* Allow Purchasing to change workflow.
* Allow Warehouse edit `finance_rejected` directly.
* Allow any non-Admin role to edit `finance_closed`.
* Automatically refresh Accurate in background.
* Update Accurate.
