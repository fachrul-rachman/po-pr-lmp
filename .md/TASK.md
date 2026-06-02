# TASK.md

## Purpose

This file defines the required implementation order.

Codex MUST follow this order.

Codex MUST NOT skip phases.

Codex MUST NOT build later UI before the required foundation exists.

## Global Task Rules

* MUST read `AGENTS.md` first.
* MUST follow all MD files.
* MUST implement in phase order.
* MUST complete one phase before starting the next phase.
* MUST run `php artisan test` before marking each phase complete.
* MUST NOT add packages without approval.
* MUST NOT implement undocumented behavior.
* MUST stop if requirements conflict.

## Phase 1 — Project Foundation

### Goal

Prepare Laravel application foundation.

### Build

* Configure Laravel 13.
* Configure PostgreSQL connection.
* Configure Livewire v4.
* Configure Tailwind CSS.
* Configure Pest through `php artisan test`.
* Configure Cloudflare R2 disk placeholder in filesystem config.
* Configure base app layout files.
* Configure environment variable names needed by documented features.

### Must Not Build Yet

* No dashboard pages.
* No workflow pages.
* No Accurate API calls.
* No role-specific UI.
* No PO/PR forms.

### Test

* `php artisan test` must pass.

## Phase 2 — Database, Models, and Enums

### Goal

Build database foundation from `DATA-MODEL.md`.

### Build

* Create migrations from `DATA-MODEL.md`.
* Create Eloquent models.
* Create model relationships.
* Create enum-like constants for:

  * roles
  * document types
  * document statuses
  * item match statuses
  * decision types
* Add database constraints where supported.
* Add indexes defined in `DATA-MODEL.md`.

### Must Not Build Yet

* No UI pages.
* No workflow action buttons.
* No Accurate integration.
* No photo upload UI.

### Test

* Test migrations run successfully.
* Test model relationships.
* Test enum constants reject undocumented values where applicable.
* Run `php artisan test`.

## Phase 3 — Custom Authentication and Role Guard

### Goal

Build login, logout, role redirect, and route protection.

### Build

* Custom username-password login.
* Password hashing.
* Logout.
* Auth middleware.
* Role middleware.
* `/login`.
* `/dashboard` role redirect.
* Server-side role access protection.

### Must Follow

* Auth rules from `AGENTS.md`.
* Role rules from `ROLE-PERMISSION.md`.
* Route rules from `UI-ROUTE.md`.

### Must Not Build Yet

* No role dashboard content beyond redirect target protection.
* No PO/PR workflow.
* No Admin CRUD.

### Test

* Login success.
* Login failure.
* Logout.
* Role redirect.
* User cannot access another role route.
* Run `php artisan test`.

## Phase 4 — Shared UI Shell

### Goal

Build shared mobile-first app shell.

### Build

* Mobile top header.
* Mobile bottom navigation.
* Desktop sidebar.
* Desktop top header.
* Role-based menu rendering.
* Shared card component.
* Shared status badge component.
* Shared count chip component.
* Shared modal component.
* Shared alert/message component.
* Shared empty state component.
* Shared loading state component.
* Local inline SVG icon components.

### Must Follow

* `UI-LAYOUT.md`.
* Route/menu definitions from `UI-ROUTE.md`.

### Must Not Build Yet

* No workflow actions.
* No Accurate API calls.
* No document forms.
* No dashboard data logic.

### Test

* Each role sees only its own navigation.
* App shell renders on mobile and desktop breakpoints.
* Unauthorized menu items are not rendered.
* Run `php artisan test`.

## Phase 5 — Activity Logging Foundation

### Goal

Build reusable audit logging service.

### Build

* Activity log service.
* Log creation method.
* JSON payload storage.
* System actor support.
* Log query helpers for Admin page later.

### Must Follow

* `ROLE-PERMISSION.md`.
* `DATA-MODEL.md`.

### Must Not Build Yet

* No Admin log UI.
* No workflow decisions.
* No Accurate refresh.

### Test

* User action log.
* System action log.
* Payload stored as JSON.
* Logs are not editable through normal model flow.
* Run `php artisan test`.

## Phase 6 — Cloudflare R2 Photo Storage Service

### Goal

Build backend photo storage behavior before Warehouse form.

### Build

* R2 disk usage.
* Photo upload service.
* Photo delete service.
* Photo replace behavior.
* Photo metadata persistence.
* Storage deletion when photo row is deleted through app service.

### Must Follow

* `DATA-MODEL.md`.
* `WORKFLOW.md`.

### Must Not Build Yet

* No Warehouse form UI.
* No item checklist UI.
* No photo upload page.

### Test

* Upload stores metadata.
* Delete removes database row.
* Delete calls storage delete.
* Replace deletes old file and stores new metadata.
* Run `php artisan test`.

## Phase 7 — Accurate Integration Service

### Goal

Build PO/PR read-only integration layer.

### Build

* `AccurateService`.
* `AccuratePurchaseOrderClient`.
* `AccuratePurchaseRequisitionClient`.
* `AccurateDocumentMapper`.
* `AccurateRefreshService`.
* PO/PR number search flow.
* PO/PR detail fetch flow.
* Local save from Accurate detail.
* Refresh comparison logic.
* Mapping failure handling.
* Integration error handling.

### Must Follow

* `ACCURATE-INTEGRATION.md`.
* `DATA-MODEL.md`.
* `WORKFLOW.md`.

### Must Not Build Yet

* No direct Accurate call from Livewire.
* No Admin refresh UI.
* No Warehouse input UI.

### Test

* Search PO and PR, merge results, user chooses (no auto-pick).
* Not found creates no document.
* Detail fetch creates document and items.
* Required mapping failure creates no document.
* Refresh no change keeps data.
* Refresh with change updates Accurate fields.
* Refresh with item change resets affected item checks.
* Refresh with item change deletes affected photos.
* Run `php artisan test`.

## Phase 8 — Admin User Management

### Goal

Build Admin user CRUD.

### Build

* Admin user list.
* Create user.
* Edit user.
* Delete user with soft delete.
* Confirmation modal for delete.
* Activity logs for create, update, delete.

### Must Follow

* `ROLE-PERMISSION.md`.
* `UI-ROUTE.md`.
* `UI-LAYOUT.md`.

### Must Not Build Yet

* No Admin document override.
* No Accurate refresh UI.
* No workflow pages.

### Test

* Admin can create user.
* Admin can update user.
* Admin can delete user.
* Deleted user is soft deleted.
* Non-Admin cannot access user management.
* User actions are logged.
* Run `php artisan test`.

## Phase 9 — Warehouse Input and Submission

### Goal

Build Warehouse PO/PR search, item check, photo upload, and submit flow.

### Build

* `/warehouse/input`.
* Accurate PO/PR search from Warehouse page through service layer.
* Document summary after found.
* Item checklist cards.
* Match toggle per item.
* Conditional reason field.
* Photo upload per item.
* Photo delete before submit.
* Submit to SPV.
* Warehouse Riwayat.
* Warehouse document detail.
* Warehouse Non Valid list.
* Warehouse edit page for `spv_rejected`.
* Warehouse resubmit.

### Must Follow

* `WORKFLOW.md`.
* `ROLE-PERMISSION.md`.
* `UI-ROUTE.md`.
* `UI-LAYOUT.md`.

### Must Not Build

* No SPV approve/reject yet.
* No Finance close/reject yet.
* No Admin override yet.

### Test

* Warehouse can search valid PO/PR.
* Warehouse cannot submit not found PO/PR.
* Warehouse cannot submit without checking every item.
* Warehouse cannot submit without at least one photo per item.
* Warehouse cannot submit `tidak_sesuai` item without reason.
* Successful submit sets `warehouse_submitted`.
* Submitted document appears in Warehouse Riwayat.
* Warehouse cannot submit same document again while not returned.
* Warehouse can edit and resubmit only when status is `spv_rejected`.
* Run `php artisan test`.

## Phase 10 — SPV Workflow

### Goal

Build SPV request, approval, rejection, history, non-valid, and non-close flow.

### Build

* `/spv/request`.
* `/spv/history`.
* `/spv/non-valid`.
* `/spv/non-close`.
* `/spv/documents/{document}`.
* SPV approve from `warehouse_submitted`.
* SPV reject from `warehouse_submitted`.
* SPV approve again from `finance_rejected`.
* SPV reject to Warehouse from `finance_rejected`.
* Document-level rejection reason.
* Optional item-level rejection reasons.
* Confirmation modals.

### Must Follow

* `WORKFLOW.md`.
* `ROLE-PERMISSION.md`.
* `UI-ROUTE.md`.
* `UI-LAYOUT.md`.

### Must Not Build

* No Finance close/reject yet.
* No Admin override yet.
* No Purchasing dashboard yet.

### Test

* SPV Request shows `warehouse_submitted`.
* SPV can approve valid document.
* SPV cannot approve if any item is `tidak_sesuai`.
* SPV reject requires document-level reason.
* SPV reject sets `spv_rejected`.
* `spv_rejected` appears in Warehouse Non Valid.
* `spv_rejected` appears in SPV Non Valid.
* SPV can process `finance_rejected`.
* Non-SPV cannot run SPV actions.
* Run `php artisan test`.

## Phase 11 — Finance Workflow

### Goal

Build Finance request, close, reject, and history flow.

### Build

* `/finance/request`.
* `/finance/history`.
* `/finance/documents/{document}`.
* Finance close from `spv_approved`.
* Finance reject from `spv_approved`.
* Document-level rejection reason.
* Optional item-level rejection reasons.
* Confirmation modals.

### Must Follow

* `WORKFLOW.md`.
* `ROLE-PERMISSION.md`.
* `UI-ROUTE.md`.
* `UI-LAYOUT.md`.

### Must Not Build

* No Admin override yet.
* No Purchasing dashboard yet.

### Test

* Finance Request shows `spv_approved`.
* Finance can close `spv_approved`.
* Finance cannot close if any item is `tidak_sesuai`.
* Finance reject requires document-level reason.
* Finance reject sets `finance_rejected`.
* `finance_rejected` appears in SPV Non Close.
* `finance_closed` is read-only for Warehouse, SPV, Finance, and Purchasing.
* Non-Finance cannot run Finance actions.
* Run `php artisan test`.

## Phase 12 — Admin Documents, Refresh, Override, and Logs

### Goal

Build Admin document control after workflow exists.

### Build

* Admin document list.
* Admin document detail.
* Admin Accurate refresh action.
* Admin status override action.
* Override reason field.
* Confirmation modal for refresh.
* Confirmation modal for override.
* Admin logs page.
* Log filters.
* Collapsible JSON payload view.

### Must Follow

* `WORKFLOW.md`.
* `ROLE-PERMISSION.md`.
* `ACCURATE-INTEGRATION.md`.
* `UI-ROUTE.md`.
* `UI-LAYOUT.md`.

### Test

* Admin can view all documents.
* Admin can refresh from Accurate.
* Refresh without change keeps status.
* Refresh with change applies status impact from `WORKFLOW.md`.
* Refresh with changed items deletes affected photos.
* Admin can override to any valid status.
* Override requires reason.
* Override moves document to correct menu by new status.
* Non-Admin cannot refresh.
* Non-Admin cannot override.
* Admin can view logs.
* Non-Admin cannot view logs.
* Run `php artisan test`.

## Phase 13 — Purchasing Read-Only Dashboard

### Goal

Build Purchasing dashboard and detail page.

### Build

* `/purchasing/dashboard`.
* `/purchasing/documents/{document}`.
* Search documents.
* Filter by status.
* Read-only document detail.
* Workflow decision history display.

### Must Follow

* `ROLE-PERMISSION.md`.
* `UI-ROUTE.md`.
* `UI-LAYOUT.md`.

### Test

* Purchasing can view all documents.
* Purchasing can view full detail.
* Purchasing cannot mutate anything.
* Purchasing sees no action buttons.
* Non-Purchasing cannot access Purchasing routes unless their own role allows equivalent data elsewhere.
* Run `php artisan test`.

## Phase 14 — PWA

### Goal

Add installable PWA behavior after core app works online.

### Build

* Web app manifest.
* 192x192 icon.
* 512x512 icon.
* Service worker.
* Static asset cache.
* Offline fallback page.
* Service worker cache versioning.

### Must Follow

* `PWA-REQUIREMENTS.md`.
* `UI-LAYOUT.md`.

### Must Not Build

* No offline workflow submission.
* No offline photo queue.
* No push notification.
* No background sync.
* No cached PO/PR detail.

### Test

* Manifest exists.
* Service worker registers.
* Offline fallback works.
* Authenticated Livewire responses are not cached.
* PO/PR details are not cached.
* Workflow actions fail clearly when offline.
* Run `php artisan test`.

## Phase 15 — Final UI Polish and Consistency Pass

### Goal

Make the finished app consistent, clean, and usable for older users.

### Build

* Review all pages against `UI-LAYOUT.md`.
* Ensure white/navy theme consistency.
* Ensure mobile-first layout.
* Ensure desktop layout is neat.
* Ensure all main actions have text and icons.
* Ensure empty states exist.
* Ensure loading states exist.
* Ensure success/error/warning messages match `UI-LAYOUT.md`.
* Ensure destructive actions use confirmation modal.
* Ensure no unauthorized action button is visible.

### Must Not Build

* No new business rule.
* No new status.
* No new role.
* No new route outside `UI-ROUTE.md`.

### Test

* Test core happy path:

  * Warehouse submit.
  * SPV approve.
  * Finance close.
* Test reject path:

  * Warehouse submit.
  * SPV reject.
  * Warehouse resubmit.
  * SPV approve.
  * Finance reject.
  * SPV reject to Warehouse.
* Test Admin path:

  * Admin refresh Accurate.
  * Admin override status.
* Test Purchasing read-only path.
* Run `php artisan test`.

## Final Completion Checklist

The project is complete only when:

* All phases are complete.
* All routes in `UI-ROUTE.md` exist.
* All role permissions in `ROLE-PERMISSION.md` are enforced server-side.
* All workflow transitions in `WORKFLOW.md` are enforced.
* All data structures in `DATA-MODEL.md` exist.
* Accurate integration follows `ACCURATE-INTEGRATION.md`.
* PWA follows `PWA-REQUIREMENTS.md`.
* UI follows `UI-LAYOUT.md`.
* No undocumented package is added.
* No undocumented status is added.
* No undocumented role is added.
* No manual PO/PR creation exists.
* No Accurate write action exists.
* `php artisan test` passes.
