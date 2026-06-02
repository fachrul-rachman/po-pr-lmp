# UI-ROUTE.md

## Purpose

This file defines application routes, Livewire page components, route ownership, and page-level actions.

Visual layout rules are defined in `UI-LAYOUT.md`.

Role permissions are defined in `ROLE-PERMISSION.md`.

Workflow behavior is defined in `WORKFLOW.md`.

## Route Rules

* All protected routes MUST require login.
* All protected routes MUST enforce role permission on server side.
* Route access MUST NOT rely only on hidden navigation.
* Each role MUST have its own route prefix.
* Users MUST be redirected to their role dashboard after login.
* Users MUST NOT access routes outside their role.
* Livewire components MUST NOT bypass workflow rules.
* Livewire components MUST NOT call Accurate directly.
* Accurate calls MUST go through service layer defined in `ACCURATE-INTEGRATION.md`.

## Public Routes

| Method | Route    | Name    | Livewire Component | Purpose         |
| ------ | -------- | ------- | ------------------ | --------------- |
| GET    | `/login` | `login` | `Auth.LoginPage`   | Show login form |

## Authenticated Common Routes

| Method | Route        | Name        | Livewire Component / Handler | Purpose                         |
| ------ | ------------ | ----------- | ---------------------------- | ------------------------------- |
| GET    | `/dashboard` | `dashboard` | `DashboardRedirect`          | Redirect user to role dashboard |
| POST   | `/logout`    | `logout`    | Controller action            | Logout current user             |

## Dashboard Redirect Rule

`/dashboard` MUST redirect by role:

| Role         | Redirect Target         |
| ------------ | ----------------------- |
| `admin`      | `/admin/documents`      |
| `warehouse`  | `/warehouse/input`      |
| `spv`        | `/spv/request`          |
| `finance`    | `/finance/request`      |
| `purchasing` | `/purchasing/dashboard` |

## Admin Routes

| Method | Route                         | Name                    | Livewire Component          | Purpose                  |
| ------ | ----------------------------- | ----------------------- | --------------------------- | ------------------------ |
| GET    | `/admin/documents`            | `admin.documents.index` | `Admin.Documents.IndexPage` | List all PO/PR documents |
| GET    | `/admin/documents/{document}` | `admin.documents.show`  | `Admin.Documents.ShowPage`  | View document detail     |
| GET    | `/admin/users`                | `admin.users.index`     | `Admin.Users.IndexPage`     | List users               |
| GET    | `/admin/users/create`         | `admin.users.create`    | `Admin.Users.CreatePage`    | Create user form         |
| GET    | `/admin/users/{user}/edit`    | `admin.users.edit`      | `Admin.Users.EditPage`      | Edit user form           |
| GET    | `/admin/logs`                 | `admin.logs.index`      | `Admin.Logs.IndexPage`      | View activity logs       |

## Admin Page Actions

### `Admin.Documents.IndexPage`

Allowed actions:

* Search documents.
* Filter documents by status.
* Open document detail.

Must not:

* Edit document inline.
* Refresh Accurate inline.
* Override status inline.

### `Admin.Documents.ShowPage`

Allowed actions:

* View full document detail.
* Refresh document from Accurate.
* Override document status.
* View workflow decisions.
* View related activity logs.

Must require confirmation modal for:

* Refresh document from Accurate.
* Override document status.

Must require reason input for:

* Override document status.

### `Admin.Users.IndexPage`

Allowed actions:

* Search users.
* Open create user page.
* Open edit user page.
* Delete user.

Must require confirmation modal for:

* Delete user.

### `Admin.Users.CreatePage`

Allowed actions:

* Create user with username, password, and role.

### `Admin.Users.EditPage`

Allowed actions:

* Update username.
* Update password.
* Update role.
* Delete user.

Must require confirmation modal for:

* Delete user.

### `Admin.Logs.IndexPage`

Allowed actions:

* Search logs.
* Filter logs by actor role.
* Filter logs by action.
* Filter logs by document number.
* View JSON payload.

Must not:

* Edit logs.
* Delete logs.

## Warehouse Routes

| Method | Route                                  | Name                       | Livewire Component             | Purpose                                       |
| ------ | -------------------------------------- | -------------------------- | ------------------------------ | --------------------------------------------- |
| GET    | `/warehouse/input`                     | `warehouse.input`          | `Warehouse.InputPage`          | Search PO/PR and fill item checks             |
| GET    | `/warehouse/history`                   | `warehouse.history`        | `Warehouse.HistoryPage`        | View Warehouse Riwayat                        |
| GET    | `/warehouse/non-valid`                 | `warehouse.non-valid`      | `Warehouse.NonValidPage`       | View rejected documents returned to Warehouse |
| GET    | `/warehouse/documents/{document}`      | `warehouse.documents.show` | `Warehouse.Documents.ShowPage` | View document detail                          |
| GET    | `/warehouse/documents/{document}/edit` | `warehouse.documents.edit` | `Warehouse.Documents.EditPage` | Edit document returned to Warehouse           |

## Warehouse Page Actions

### `Warehouse.InputPage`

Allowed actions:

* Input PO/PR number.
* Search PO/PR from Accurate through service layer.
* Display document header.
* Display all document items.
* Fill item check for every item.
* Upload one or more photos per item.
* Delete uploaded photos before submit.
* Submit document to SPV.

Must validate before submit:

* Every item has match status.
* Every item has at least one photo.
* Every item marked `tidak_sesuai` has reason.

Must not:

* Create manual PO/PR.
* Save draft.
* Submit same document twice unless workflow returned it to Warehouse.
* Submit document not found in Accurate.

### `Warehouse.HistoryPage`

Allowed actions:

* View submitted documents.
* Open document detail.
* Open edit page only if workflow allows Warehouse edit.

Must show count chip.

### `Warehouse.NonValidPage`

Allowed actions:

* View documents with status `spv_rejected`.
* Open document detail.
* Open edit page.

Must show count chip.

### `Warehouse.Documents.ShowPage`

Allowed actions:

* View document detail.
* View item checks.
* View item photos.
* Open edit page only if workflow allows Warehouse edit.

Must not:

* Edit inline.

### `Warehouse.Documents.EditPage`

Allowed actions:

* Edit item match status.
* Edit item reason.
* Upload item photos.
* Delete item photos.
* Replace item photos.
* Resubmit document to SPV.

Must validate before resubmit:

* Every item has match status.
* Every item has at least one photo.
* Every item marked `tidak_sesuai` has reason.

## SPV Routes

| Method | Route                       | Name                 | Livewire Component       | Purpose                                    |
| ------ | --------------------------- | -------------------- | ------------------------ | ------------------------------------------ |
| GET    | `/spv/request`              | `spv.request`        | `Spv.RequestPage`        | View Warehouse submissions waiting for SPV |
| GET    | `/spv/history`              | `spv.history`        | `Spv.HistoryPage`        | View SPV Riwayat                           |
| GET    | `/spv/non-valid`            | `spv.non-valid`      | `Spv.NonValidPage`       | View documents rejected by SPV             |
| GET    | `/spv/non-close`            | `spv.non-close`      | `Spv.NonClosePage`       | View documents rejected by Finance         |
| GET    | `/spv/documents/{document}` | `spv.documents.show` | `Spv.Documents.ShowPage` | View and process document                  |

## SPV Page Actions

### `Spv.RequestPage`

Allowed actions:

* View documents with status `warehouse_submitted`.
* Open document detail.

Must show count chip.

### `Spv.HistoryPage`

Allowed actions:

* View documents processed by SPV.
* Open document detail.

### `Spv.NonValidPage`

Allowed actions:

* View documents with status `spv_rejected`.
* Open document detail.

Must show count chip.

### `Spv.NonClosePage`

Allowed actions:

* View documents with status `finance_rejected`.
* Open document detail.

Must show count chip.

### `Spv.Documents.ShowPage`

Allowed actions when status is `warehouse_submitted`:

* View document detail.
* View Warehouse item checks.
* View item photos.
* Approve document.
* Reject document.

Allowed actions when status is `finance_rejected`:

* View document detail.
* View Finance rejection reason.
* Approve document again to Finance.
* Reject document to Warehouse.

Must require confirmation modal for:

* Approve document.
* Reject document.

Must require document-level reason for:

* Reject document.

May allow item-level reasons for:

* Reject document.

Must not:

* Edit Warehouse item checks.
* Edit Warehouse photos.
* Approve if any item is `tidak_sesuai`.
* Approve if any item has no photo.

## Finance Routes

| Method | Route                           | Name                     | Livewire Component           | Purpose                                         |
| ------ | ------------------------------- | ------------------------ | ---------------------------- | ----------------------------------------------- |
| GET    | `/finance/request`              | `finance.request`        | `Finance.RequestPage`        | View SPV-approved documents waiting for Finance |
| GET    | `/finance/history`              | `finance.history`        | `Finance.HistoryPage`        | View Finance Riwayat                            |
| GET    | `/finance/documents/{document}` | `finance.documents.show` | `Finance.Documents.ShowPage` | View and process document                       |

## Finance Page Actions

### `Finance.RequestPage`

Allowed actions:

* View documents with status `spv_approved`.
* Open document detail.

Must show count chip.

### `Finance.HistoryPage`

Allowed actions:

* View documents processed by Finance.
* Open document detail.

### `Finance.Documents.ShowPage`

Allowed actions:

* View document detail.
* View Warehouse item checks.
* View item photos.
* View SPV decision data.
* Close document.
* Reject document.

Must require confirmation modal for:

* Close document.
* Reject document.

Must require document-level reason for:

* Reject document.

May allow item-level reasons for:

* Reject document.

Must not:

* Edit Warehouse item checks.
* Edit Warehouse photos.
* Edit SPV decision data.
* Close if any item is `tidak_sesuai`.
* Close if document status is not `spv_approved`.

## Purchasing Routes

| Method | Route                              | Name                        | Livewire Component              | Purpose                   |
| ------ | ---------------------------------- | --------------------------- | ------------------------------- | ------------------------- |
| GET    | `/purchasing/dashboard`            | `purchasing.dashboard`      | `Purchasing.DashboardPage`      | View all PO/PR documents  |
| GET    | `/purchasing/documents/{document}` | `purchasing.documents.show` | `Purchasing.Documents.ShowPage` | View full document detail |

## Purchasing Page Actions

### `Purchasing.DashboardPage`

Allowed actions:

* View all documents.
* Search documents.
* Filter documents by status.
* Open document detail.

Must not:

* Create data.
* Edit data.
* Delete data.
* Approve data.
* Reject data.
* Close data.
* Refresh Accurate.
* Override status.

### `Purchasing.Documents.ShowPage`

Allowed actions:

* View full document detail.
* View item checks.
* View photos.
* View workflow decisions.

Must be read-only.

## Route Middleware

### Public

Routes:

* `/login`

Middleware:

```text
guest
```

### Protected

All routes except `/login` MUST use:

```text
auth
```

### Role Middleware

Each role prefix MUST use role middleware:

| Prefix          | Required Role |
| --------------- | ------------- |
| `/admin/*`      | `admin`       |
| `/warehouse/*`  | `warehouse`   |
| `/spv/*`        | `spv`         |
| `/finance/*`    | `finance`     |
| `/purchasing/*` | `purchasing`  |

## Navigation Rules

### Admin Navigation

Admin navigation MUST include:

* Documents
* Users
* Logs

### Warehouse Navigation

Warehouse navigation MUST include:

* Input Barang
* Riwayat
* Non Valid

### SPV Navigation

SPV navigation MUST include:

* Request
* Riwayat
* Non Valid
* Non Close

### Finance Navigation

Finance navigation MUST include:

* Request
* Riwayat

### Purchasing Navigation

Purchasing navigation MUST include:

* Dashboard

## Card Data Rule

Document cards MUST only show:

* Document number
* Document status
* Item summary

Item summary rule:

* If document has one item, show that item name.
* If document has more than one item, show first item name plus total item count.

Card layout is defined in `UI-LAYOUT.md`.

## Detail Page Data Rule

Document detail pages MAY show full data based on role permission.

Detail pages MUST include:

* Document header
* All document items
* Item match status
* Item reasons
* Item photos
* Workflow decision history relevant to the role
* Current status

Role visibility rules are defined in `ROLE-PERMISSION.md`.

## Count Chip Rule

Count chips MUST appear on:

* Warehouse Riwayat
* Warehouse Non Valid
* SPV Request
* SPV Non Valid
* SPV Non Close
* Finance Request

Count chip visual style is defined in `UI-LAYOUT.md`.

## Modal Routing Rule

Modals MUST be page-level Livewire state, not separate routes.

Modal behavior is defined in `UI-LAYOUT.md`.

## Forbidden Route Behavior

The application MUST NOT:

* Put business rules only in route files.
* Put permission checks only in Blade views.
* Allow direct URL access to another role page.
* Create hidden routes for undocumented actions.
* Create API routes for Livewire actions unless explicitly required.
* Call Accurate directly from Livewire components.
* Add routes for manual PO/PR creation.
* Add draft routes.
* Add Purchasing mutation routes.
* Add non-Admin Accurate refresh routes.
* Add non-Admin status override routes.
