# ROLE-PERMISSION.md

## Purpose

This file defines role access, allowed actions, forbidden actions, and required audit logs.

Workflow transitions are defined in `WORKFLOW.md`.

UI routes are defined in `UI-ROUTE.md`.

## Roles

The application MUST only use these roles:

```text
admin
warehouse
spv
finance
purchasing
```

Each user MUST have exactly one role.

## Global Access Rules

* Users MUST login before accessing the application.
* Users MUST only access pages allowed for their role.
* Users MUST NOT access another role dashboard.
* Users MUST NOT perform actions outside their role permission.
* The application MUST enforce permissions on server side.
* Hiding buttons in UI is not enough.
* Every protected action MUST be checked again in backend logic.

## Document Visibility Rule

* Warehouse, SPV, Finance, Purchasing, and Admin can see documents according to status/menu rules in `WORKFLOW.md`.
* There is no department-based document restriction.
* There is no user-based document restriction, except menu ownership rules already defined in `WORKFLOW.md`.

## Admin Permissions

### Admin CAN

Admin can:

* Create user.
* Read user list.
* Update user.
* Delete user.
* Read all PO/PR documents.
* Read all PO/PR details.
* Override any document status.
* Refresh any document from Accurate.
* Read activity logs.

### Admin MUST

Admin must:

* Provide override reason when overriding document status.
* Trigger Accurate refresh manually.
* Use only valid statuses defined in `WORKFLOW.md`.

### Admin MUST NOT

Admin must not:

* Create manual PO/PR.
* Update Accurate.
* Delete data from Accurate.
* Perform silent status override without reason.
* Perform silent Accurate refresh without log.

## Warehouse Permissions

### Warehouse CAN

Warehouse can:

* Input PO/PR number.
* Pull PO/PR data from Accurate through the application.
* View PO/PR detail before submission.
* Fill item check data.
* Upload item photos.
* Delete or replace item photos when document is editable by Warehouse.
* Submit document to SPV.
* View Warehouse Riwayat.
* View Warehouse Non Valid.
* Edit document only when status allows Warehouse edit in `WORKFLOW.md`.

### Warehouse MUST

Warehouse must:

* Check every item before submit.
* Upload at least one photo per item before submit.
* Choose `sesuai` or `tidak_sesuai` for every item before submit.
* Fill item reason when item is marked `tidak_sesuai`.

### Warehouse MUST NOT

Warehouse must not:

* Create manual PO/PR.
* Submit document not found in Accurate.
* Submit without checking all items.
* Submit without required photos.
* Submit the same document twice unless workflow returns it to Warehouse.
* Approve document.
* Reject document as SPV.
* Close document.
* Edit document with status `warehouse_submitted`.
* Edit document with status `spv_approved`.
* Edit document with status `finance_rejected`.
* Edit document with status `finance_closed`.
* Override status.
* Refresh from Accurate.
* View Admin logs.
* Manage users.

## SPV Permissions

### SPV CAN

SPV can:

* View SPV Request.
* View SPV Riwayat.
* View SPV Non Valid.
* View SPV Non Close.
* Read document details assigned to SPV workflow step.
* Approve document from `warehouse_submitted`.
* Reject document from `warehouse_submitted`.
* Approve document again from `finance_rejected`.
* Reject document to Warehouse from `finance_rejected`.
* Add document-level rejection reason.
* Add optional item-level rejection reasons.

### SPV MUST

SPV must:

* Reject with document-level reason.
* Follow valid transitions defined in `WORKFLOW.md`.
* Ensure all items are `sesuai` before approving.

### SPV MUST NOT

SPV must not:

* Approve document when any item is `tidak_sesuai`.
* Approve document without required item photos.
* Edit Warehouse item checks.
* Edit Warehouse photos.
* Close document.
* Process document before Warehouse submit.
* Process document after `finance_closed`.
* Override status.
* Refresh from Accurate.
* Manage users.
* View Admin logs.

## Finance Permissions

### Finance CAN

Finance can:

* View Finance Request.
* View Finance Riwayat.
* Read document details assigned to Finance workflow step.
* Close document from `spv_approved`.
* Reject document from `spv_approved`.
* Add document-level rejection reason.
* Add optional item-level rejection reasons.

### Finance MUST

Finance must:

* Reject with document-level reason.
* Close only documents with status `spv_approved`.
* Follow valid transitions defined in `WORKFLOW.md`.

### Finance MUST NOT

Finance must not:

* Close document not approved by SPV.
* Close document when any item is `tidak_sesuai`.
* Edit Warehouse item checks.
* Edit Warehouse photos.
* Edit SPV approval data.
* Process document with status `warehouse_submitted`.
* Process document with status `spv_rejected`.
* Process document with status `finance_rejected`.
* Process document after `finance_closed`.
* Override status.
* Refresh from Accurate.
* Manage users.
* View Admin logs.

## Purchasing Permissions

### Purchasing CAN

Purchasing can:

* View Purchasing Dashboard.
* View all PO/PR documents.
* View all document statuses.
* View full document detail.

### Purchasing MUST NOT

Purchasing must not:

* Create PO/PR.
* Edit PO/PR.
* Submit Warehouse checks.
* Upload photos.
* Delete photos.
* Approve document.
* Reject document.
* Close document.
* Override status.
* Refresh from Accurate.
* Manage users.
* View Admin logs.

## Permission Matrix

| Action                 | Admin | Warehouse | SPV | Finance | Purchasing |
| ---------------------- | ----: | --------: | --: | ------: | ---------: |
| Login                  |   Yes |       Yes | Yes |     Yes |        Yes |
| Create user            |   Yes |        No |  No |      No |         No |
| Read users             |   Yes |        No |  No |      No |         No |
| Update user            |   Yes |        No |  No |      No |         No |
| Delete user            |   Yes |        No |  No |      No |         No |
| Read all documents     |   Yes |       Yes | Yes |     Yes |        Yes |
| Input PO/PR number     |    No |       Yes |  No |      No |         No |
| Submit Warehouse check |    No |       Yes |  No |      No |         No |
| Upload item photos     |    No |       Yes |  No |      No |         No |
| Delete item photos     |    No |       Yes |  No |      No |         No |
| SPV approve            |    No |        No | Yes |      No |         No |
| SPV reject             |    No |        No | Yes |      No |         No |
| Finance close          |    No |        No |  No |     Yes |         No |
| Finance reject         |    No |        No |  No |     Yes |         No |
| Override status        |   Yes |        No |  No |      No |         No |
| Refresh Accurate data  |   Yes |        No |  No |      No |         No |
| Read activity logs     |   Yes |        No |  No |      No |         No |

## Status-Based Action Matrix

| Status                | Warehouse         | SPV                                  | Finance         | Admin                   | Purchasing |
| --------------------- | ----------------- | ------------------------------------ | --------------- | ----------------------- | ---------- |
| `warehouse_submitted` | Read only         | Approve or reject                    | No action       | Override, refresh, read | Read only  |
| `spv_rejected`        | Edit and resubmit | Read only                            | No action       | Override, refresh, read | Read only  |
| `spv_approved`        | Read only         | Read only                            | Close or reject | Override, refresh, read | Read only  |
| `finance_rejected`    | Read only         | Approve again or reject to Warehouse | Read only       | Override, refresh, read | Read only  |
| `finance_closed`      | Read only         | Read only                            | Read only       | Override, refresh, read | Read only  |

## Audit Log Rule

The application MUST log every important action.

Each audit log MUST store:

* Actor ID
* Actor role
* Action
* Document ID when related to document
* Previous status when status changes
* New status when status changes
* JSON payload
* Timestamp

## Required Logged Actions

### Authentication

* Login

### User Management

* Admin create user
* Admin update user
* Admin delete user

### Warehouse

* Warehouse submit
* Warehouse resubmit
* Warehouse edit rejected document
* Warehouse upload photo
* Warehouse delete photo
* Warehouse replace photo

### SPV

* SPV approve
* SPV reject

### Finance

* Finance close
* Finance reject

### Admin Document Actions

* Admin status override
* Admin Accurate refresh

### System Actions

* System status change caused by Accurate refresh
* System item data replacement caused by Accurate refresh
* System photo deletion caused by Accurate refresh

## Forbidden Permission Behavior

The application MUST NOT:

* Trust frontend-only permission checks.
* Allow route access without role validation.
* Allow action execution without role validation.
* Allow non-Admin users to manage users.
* Allow non-Admin users to read activity logs.
* Allow non-Admin users to override status.
* Allow non-Admin users to refresh Accurate data.
* Allow Purchasing to mutate any data.
* Allow Warehouse to process SPV or Finance actions.
* Allow SPV to process Finance close.
* Allow Finance to process SPV approval.
* Allow any role to update Accurate.
