# PRODUCT-SPEC.md

## Purpose

This application manages PO/PR receiving verification from Warehouse, SPV validation, Finance closure, and Purchasing visibility.

## Product Goal

The application MUST ensure every received PO/PR is checked against Accurate data before it can be closed.

## Source System

* Accurate is the only source for PO/PR data.
* The application MUST only read PO/PR data from Accurate.
* The application MUST NOT create PO/PR manually.
* The application MUST NOT update Accurate.
* The application MUST NOT delete data from Accurate.

## Document Types

* The application supports PO and PR.
* PO and PR use the same application flow.
* PO and PR numbers have different formats.
* When a user inputs a document number, the application MUST search both PO and PR sources from Accurate.
* If the document number exists, the application MUST show the document detail.
* If the document number does not exist, the application MUST reject the input.
* The application MUST NOT create fallback/manual data when a document is not found.

## Main Users

The application has these roles:

* Admin
* Warehouse
* SPV
* Finance
* Purchasing

Role access rules are defined in `ROLE-PERMISSION.md`.

## Main Workflow Summary

* Warehouse checks PO/PR items.
* SPV validates Warehouse submission.
* Finance closes or rejects SPV-approved documents.
* Purchasing views all documents and statuses.
* Admin manages users, workflow status override, Accurate refresh, and logs.

Detailed workflow rules are defined in `WORKFLOW.md`.

## PO/PR Business Rule

* One PO/PR is treated as one document.
* One PO/PR can contain multiple items.
* Item checking is done per item.
* Workflow status is applied per document.
* If one item is not valid, the whole document cannot continue as approved.

Detailed status and transition rules are defined in `WORKFLOW.md`.

## Accurate Data Fields

The application MUST store these fields from Accurate:

* Document number
* Document type
* Item name
* Item description / keterangan
* Quantity
* Unit / satuan
* Purchase purpose / tujuan pembelian
* Ship to / dikirim ke
* Department
* Created by / dibuat oleh
* Requested by / diminta oleh

Database structure is defined in `DATA-MODEL.md`.

Accurate mapping and refresh rules are defined in `ACCURATE-INTEGRATION.md`.

## Warehouse Scope

Warehouse MUST be able to:

* Input PO/PR number.
* View Accurate document data.
* View all document items.
* Check every item.
* Upload at least one photo per item.
* Upload more than one photo per item.
* Mark each item as sesuai or tidak sesuai.
* Add item reason when needed.
* Submit the document once.
* View own history.
* Edit rejected documents when the workflow returns to Warehouse.

Warehouse MUST NOT submit the same PO/PR more than once unless the workflow returns the document to Warehouse.

## SPV Scope

SPV MUST be able to:

* View Warehouse-submitted documents.
* View document detail, item checks, photos, and Warehouse submit time.
* Approve valid documents.
* Reject invalid documents.
* Add rejection reason.
* View own history.
* Handle documents returned from Finance.

SPV MUST NOT approve a document when any item is marked tidak sesuai.

## Finance Scope

Finance MUST be able to:

* View SPV-approved documents.
* View document detail, Warehouse data, SPV data, Warehouse submit time, and SPV validation time.
* Close valid documents.
* Reject documents.
* Add rejection reason.
* View own history.

Finance MUST NOT close a document that is not approved by SPV.

## Purchasing Scope

Purchasing MUST be able to:

* View all PO/PR documents.
* View all document statuses.
* View full document detail.

Purchasing MUST NOT create, update, delete, approve, reject, close, refresh, or override anything.

## Admin Scope

Admin MUST be able to:

* Manage users.
* View all PO/PR documents.
* Override document status.
* Refresh document data from Accurate.
* View activity logs.

Admin status override rules are defined in `WORKFLOW.md`.

Admin permission detail is defined in `ROLE-PERMISSION.md`.

## Submission Rule

* Warehouse submission is final for that workflow step.
* Warehouse MUST complete all required item checks before submitting.
* The application MUST NOT provide draft submission.

## Photo Rule

* Every item MUST have at least one photo before Warehouse can submit.
* An item MAY have more than one photo.
* Photo storage rules are defined in `DATA-MODEL.md`.

## Refresh Rule

* Only Admin can refresh PO/PR data from Accurate.
* Refresh can be done at any document status.
* If refreshed Accurate data changes the saved document, the workflow MUST move backward according to `WORKFLOW.md`.
* Changed item data from refresh MUST require re-check by the responsible role according to `WORKFLOW.md`.

Detailed refresh behavior is defined in `ACCURATE-INTEGRATION.md`.

## Final State

* A document is final only when Finance sets it to closed.
* Closed documents MUST be read-only for Warehouse, SPV, Finance, and Purchasing.
* Admin MAY override closed documents according to `WORKFLOW.md`.

## UI Principle

* The application MUST be mobile-first.
* The application MUST be clean, simple, and easy for older users.
* The application MUST avoid clutter.
* The application MUST use clear labels and clear action buttons.

Detailed layout, component behavior, messages, modal rules, and responsive behavior are defined in `UI-LAYOUT.md`.

## Out of Scope

The application MUST NOT implement:

* Manual PO/PR creation.
* Write-back to Accurate.
* External authentication providers.
* Draft Warehouse submission.
* Multi-Warehouse conflict handling.
* Department-based data restriction.
* Purchasing approval.
* Finance editing Warehouse item checks.
* SPV closing documents.
* Warehouse approving documents.
* Automatic background refresh from Accurate.
