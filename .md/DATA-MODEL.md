# DATA-MODEL.md

## Purpose

This file defines database tables, columns, relationships, enums, indexes, and file metadata.

Workflow rules are defined in `WORKFLOW.md`.

Role permissions are defined in `ROLE-PERMISSION.md`.

Accurate mapping is defined in `ACCURATE-INTEGRATION.md`.

## Database

* Database MUST be PostgreSQL.
* All primary keys MUST use UUID.
* All tables MUST have `created_at` and `updated_at`.
* Tables MUST NOT use undocumented columns.
* Tables MUST NOT use undocumented status values.
* Foreign keys MUST be enforced where defined.

## Enum Values

### User Roles

```text
admin
warehouse
spv
finance
purchasing
```

### Document Types

```text
po
pr
```

### Document Statuses

```text
warehouse_submitted
spv_approved
spv_rejected
finance_rejected
finance_closed
```

### Item Match Statuses

```text
sesuai
tidak_sesuai
```

### Decision Types

```text
warehouse_submit
warehouse_resubmit
spv_approve
spv_reject
finance_close
finance_reject
admin_override
accurate_refresh
system_status_change
```

## Table: `users`

### Purpose

Stores application users.

### Columns

| Column       | Type         | Required | Notes                    |
| ------------ | ------------ | -------: | ------------------------ |
| `id`         | uuid         |      Yes | Primary key              |
| `username`   | varchar(100) |      Yes | Unique                   |
| `password`   | varchar(255) |      Yes | Hashed password          |
| `role`       | varchar(30)  |      Yes | Must use User Roles enum |
| `created_at` | timestamp    |      Yes | Laravel timestamp        |
| `updated_at` | timestamp    |      Yes | Laravel timestamp        |
| `deleted_at` | timestamp    |       No | Soft delete              |

### Rules

* `username` MUST be unique.
* `password` MUST store hashed password only.
* User delete MUST use soft delete to preserve audit history.
* A user MUST have exactly one role.

### Indexes

* Unique index on `username`.
* Index on `role`.

## Table: `documents`

### Purpose

Stores PO/PR document header data copied from Accurate.

### Columns

| Column                   | Type         | Required | Notes                                                |
| ------------------------ | ------------ | -------: | ---------------------------------------------------- |
| `id`                     | uuid         |      Yes | Primary key                                          |
| `accurate_id`            | varchar(100) |      Yes | Accurate document identifier                         |
| `document_number`        | varchar(100) |      Yes | PO/PR number                                         |
| `document_type`          | varchar(10)  |      Yes | `po` or `pr`                                         |
| `status`                 | varchar(50)  |       No | Null before Warehouse submit; must use Document Status enum after submit |
| `tujuan_pembelian`       | text         |       No | From Accurate                                        |
| `dikirim_ke`             | text         |       No | From Accurate                                        |
| `department`             | varchar(255) |       No | From Accurate                                        |
| `dibuat_oleh`            | varchar(255) |       No | From Accurate                                        |
| `diminta_oleh`           | varchar(255) |       No | From Accurate                                        |
| `accurate_synced_at`     | timestamp    |      Yes | Last successful Accurate copy/refresh time           |
| `warehouse_submitted_at` | timestamp    |       No | Last Warehouse submit time                           |
| `warehouse_submitted_by` | uuid         |       No | FK to `users.id`                                     |
| `spv_processed_at`       | timestamp    |       No | Last SPV approve/reject time                         |
| `spv_processed_by`       | uuid         |       No | FK to `users.id`                                     |
| `finance_processed_at`   | timestamp    |       No | Last Finance close/reject time                       |
| `finance_processed_by`   | uuid         |       No | FK to `users.id`                                     |
| `admin_overridden_at`    | timestamp    |       No | Last Admin override time                             |
| `admin_overridden_by`    | uuid         |       No | FK to `users.id`                                     |
| `created_at`             | timestamp    |      Yes | Laravel timestamp                                    |
| `updated_at`             | timestamp    |      Yes | Laravel timestamp                                    |

### Rules

* `document_number` MUST be unique.
* `status` MUST be null before Warehouse submit.
* After Warehouse submit, `status` MUST be one of the Document Statuses enum.
* `status` MUST NOT be draft.
* `accurate_id` and `document_type` identify the source document from Accurate.
* Header fields MUST follow latest Admin refresh from Accurate.
* Closed documents still remain in this table.

### Indexes

* Unique index on `document_number`.
* Index on `document_type`.
* Index on `status`.
* Index on `accurate_id`.

## Table: `document_items`

### Purpose

Stores PO/PR item rows copied from Accurate and Warehouse item check result.

### Columns

| Column             | Type          | Required | Notes                                     |
| ------------------ | ------------- | -------: | ----------------------------------------- |
| `id`               | uuid          |      Yes | Primary key                               |
| `document_id`      | uuid          |      Yes | FK to `documents.id`                      |
| `accurate_item_id` | varchar(100)  |      Yes | Accurate item row identifier              |
| `nama_barang`      | text          |      Yes | From Accurate                             |
| `keterangan`       | text          |       No | From Accurate                             |
| `quantity`         | decimal(18,4) |      Yes | From Accurate                             |
| `satuan`           | varchar(100)  |      Yes | From Accurate                             |
| `match_status`     | varchar(30)   |       No | `sesuai` or `tidak_sesuai`                |
| `warehouse_reason` | text          |       No | Required if `match_status = tidak_sesuai` |
| `created_at`       | timestamp     |      Yes | Laravel timestamp                         |
| `updated_at`       | timestamp     |      Yes | Laravel timestamp                         |

### Rules

* Item belongs to exactly one document.
* `match_status` is empty before Warehouse checks the item.
* Before Warehouse can submit, every item MUST have `match_status`.
* If `match_status = tidak_sesuai`, `warehouse_reason` MUST be filled.
* If Accurate refresh changes item data, affected item check data MUST be reset according to `WORKFLOW.md`.

### Indexes

* Index on `document_id`.
* Index on `accurate_item_id`.
* Index on `match_status`.

## Table: `item_photos`

### Purpose

Stores metadata for item photos uploaded by Warehouse to Cloudflare R2.

### Columns

| Column             | Type         | Required | Notes                      |
| ------------------ | ------------ | -------: | -------------------------- |
| `id`               | uuid         |      Yes | Primary key                |
| `document_item_id` | uuid         |      Yes | FK to `document_items.id`  |
| `uploaded_by`      | uuid         |      Yes | FK to `users.id`           |
| `disk`             | varchar(50)  |      Yes | Must be `r2`               |
| `path`             | text         |      Yes | R2 object path             |
| `original_name`    | varchar(255) |      Yes | Original uploaded filename |
| `mime_type`        | varchar(100) |      Yes | Uploaded file MIME type    |
| `size_bytes`       | bigint       |      Yes | Uploaded file size         |
| `created_at`       | timestamp    |      Yes | Laravel timestamp          |
| `updated_at`       | timestamp    |      Yes | Laravel timestamp          |

### Rules

* Every item MUST have at least one photo before Warehouse submit.
* More than one photo per item is allowed.
* Deleted photos MUST be deleted from Cloudflare R2.
* Replaced photos MUST delete old R2 object and create new `item_photos` record.
* Photo URL MUST be generated from R2 path, not stored as editable user input.

### Indexes

* Index on `document_item_id`.
* Index on `uploaded_by`.

## Table: `document_decisions`

### Purpose

Stores workflow decisions made by Warehouse, SPV, Finance, Admin, and system.

### Columns

| Column          | Type        | Required | Notes                                     |
| --------------- | ----------- | -------: | ----------------------------------------- |
| `id`            | uuid        |      Yes | Primary key                               |
| `document_id`   | uuid        |      Yes | FK to `documents.id`                      |
| `decision_type` | varchar(50) |      Yes | Must use Decision Types enum              |
| `from_status`   | varchar(50) |       No | Previous status                           |
| `to_status`     | varchar(50) |      Yes | New status                                |
| `reason`        | text        |       No | Required for rejection and Admin override |
| `actor_id`      | uuid        |       No | FK to `users.id`; null for system         |
| `actor_role`    | varchar(30) |      Yes | Role value or `system`                    |
| `created_at`    | timestamp   |      Yes | Decision time                             |
| `updated_at`    | timestamp   |      Yes | Laravel timestamp                         |

### Rules

* Every workflow transition MUST create one `document_decisions` record.
* `actor_id` MUST be filled for user actions.
* `actor_id` MUST be null only for system-generated actions.
* `reason` MUST be filled for:

  * `spv_reject`
  * `finance_reject`
  * `admin_override`
  * `system_status_change`
* `reason` MAY be empty for:

  * `warehouse_submit`
  * `warehouse_resubmit`
  * `spv_approve`
  * `finance_close`
  * `accurate_refresh` when no status change happens

### Indexes

* Index on `document_id`.
* Index on `decision_type`.
* Index on `actor_id`.
* Index on `created_at`.

## Table: `decision_item_reasons`

### Purpose

Stores optional item-level reasons from SPV or Finance rejection.

### Columns

| Column                 | Type      | Required | Notes                         |
| ---------------------- | --------- | -------: | ----------------------------- |
| `id`                   | uuid      |      Yes | Primary key                   |
| `document_decision_id` | uuid      |      Yes | FK to `document_decisions.id` |
| `document_item_id`     | uuid      |      Yes | FK to `document_items.id`     |
| `reason`               | text      |      Yes | Item-level reason             |
| `created_at`           | timestamp |      Yes | Laravel timestamp             |
| `updated_at`           | timestamp |      Yes | Laravel timestamp             |

### Rules

* Item-level rejection reason is optional.
* If created, `reason` MUST NOT be empty.
* This table MUST only be used for SPV or Finance rejection decisions.

### Indexes

* Index on `document_decision_id`.
* Index on `document_item_id`.

## Table: `activity_logs`

### Purpose

Stores audit logs for important actions.

Audit event rules are defined in `ROLE-PERMISSION.md`.

### Columns

| Column            | Type         | Required | Notes                             |
| ----------------- | ------------ | -------: | --------------------------------- |
| `id`              | uuid         |      Yes | Primary key                       |
| `actor_id`        | uuid         |       No | FK to `users.id`; null for system |
| `actor_role`      | varchar(30)  |      Yes | Role value or `system`            |
| `action`          | varchar(100) |      Yes | Action name                       |
| `document_id`     | uuid         |       No | FK to `documents.id` when related |
| `previous_status` | varchar(50)  |       No | Previous workflow status          |
| `new_status`      | varchar(50)  |       No | New workflow status               |
| `payload`         | jsonb        |      Yes | JSON payload                      |
| `created_at`      | timestamp    |      Yes | Log time                          |
| `updated_at`      | timestamp    |      Yes | Laravel timestamp                 |

### Rules

* `payload` MUST be valid JSON.
* System actions MUST use `actor_role = system`.
* User actions MUST have `actor_id`.
* Logs MUST NOT be edited after creation.
* Logs MUST NOT be deleted by normal application flow.

### Indexes

* Index on `actor_id`.
* Index on `actor_role`.
* Index on `action`.
* Index on `document_id`.
* Index on `created_at`.
* GIN index on `payload`.

## Relationships

### User

* User has many `documents` through actor columns.
* User has many `item_photos`.
* User has many `document_decisions`.
* User has many `activity_logs`.

### Document

* Document has many `document_items`.
* Document has many `document_decisions`.
* Document has many `activity_logs`.

### Document Item

* Document item belongs to one document.
* Document item has many `item_photos`.
* Document item has many `decision_item_reasons`.

### Document Decision

* Document decision belongs to one document.
* Document decision may belong to one actor.
* Document decision has many `decision_item_reasons`.

### Item Photo

* Item photo belongs to one document item.
* Item photo belongs to one uploading user.

## Delete Rules

### Users

* User delete MUST be soft delete.
* Existing documents, decisions, photos, and logs MUST remain.

### Documents

* Documents MUST NOT be deleted by normal application flow.

### Document Items

* Document items MAY be replaced only by Admin Accurate refresh.
* If Admin Accurate refresh removes an item, related item photos MUST be deleted from R2 and database.
* Related item check data MUST be removed with the deleted item.

### Item Photos

* Deleting an item photo MUST delete the R2 object.
* Deleting an item photo MUST delete the `item_photos` row.

### Activity Logs

* Activity logs MUST NOT be deleted by normal application flow.

## Required Constraints

### `documents`

* `document_number` unique.
* `document_type` must be `po` or `pr`.
* `status` must be null or one of valid Document Statuses.

### `document_items`

* `quantity` must be greater than or equal to `0`.
* `match_status` must be null, `sesuai`, or `tidak_sesuai`.
* `warehouse_reason` required when `match_status = tidak_sesuai`.

### `item_photos`

* `disk` must be `r2`.
* `path` must not be empty.
* `size_bytes` must be greater than `0`.

### `document_decisions`

* `decision_type` must be one of valid Decision Types.
* `actor_role` must be one of User Roles or `system`.

### `activity_logs`

* `actor_role` must be one of User Roles or `system`.
* `payload` must be jsonb.

## Forbidden Data Model Behavior

The application MUST NOT:

* Store plain-text passwords.
* Store undocumented roles.
* Store undocumented statuses.
* Store undocumented decision types.
* Store manual PO/PR data not sourced from Accurate.
* Store editable public photo URL as source of truth.
* Keep database photo records after R2 object deletion.
* Delete activity logs through normal application flow.
* Hard delete users.
* Use draft status.
* Use department-based ownership fields.
* Use multi-warehouse locking fields.
