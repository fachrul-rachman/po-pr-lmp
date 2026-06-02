# AGENTS.md

## Project

Build a Laravel Livewire application for PO/PR receiving, validation, and closure workflow.

## Fixed Stack

* Laravel 13
* Livewire v4
* PostgreSQL
* Tailwind CSS
* PWA
* Cloudflare R2 for uploaded photos
* VPS deployment target
* Custom username-password authentication
* Pest testing through Laravel test runner

## Mandatory Command

Before marking any task complete, run:

```bash
php artisan test
```

A task is not complete if this command fails.

## Package Rule

* MUST NOT add new Composer packages without explicit approval.
* MUST NOT add new NPM packages without explicit approval.
* MUST use Laravel, Livewire, Tailwind, and native framework features first.

## Documentation Source of Truth

Read these files before implementation:

1. `PRODUCT-SPEC.md`
2. `WORKFLOW.md`
3. `ROLE-PERMISSION.md`
4. `DATA-MODEL.md`
5. `ACCURATE-INTEGRATION.md`
6. `UI-ROUTE.md`
7. `PWA-REQUIREMENTS.md`
8. `UI-LAYOUT.md`
9. `TASK.md`

## Anti-Assumption Rule

* MUST NOT invent requirements.
* MUST NOT implement behavior that is not written in the MD files.
* MUST NOT fill missing business rules using common practice.
* MUST stop and ask for clarification when a requirement is missing, unclear, or conflicting.
* MUST only implement fixed requirements.

## Duplication Rule

* MUST NOT duplicate business rules across files.
* MUST follow the file responsible for the topic.
* Product scope belongs in `PRODUCT-SPEC.md`.
* Workflow status and transitions belong in `WORKFLOW.md`.
* Role access belongs in `ROLE-PERMISSION.md`.
* Database structure belongs in `DATA-MODEL.md`.
* Accurate integration belongs in `ACCURATE-INTEGRATION.md`.
* Routes and Livewire components belong in `UI-ROUTE.md`.
* PWA behavior belongs in `PWA-REQUIREMENTS.md`.
* Visual layout and UX behavior belong in `UI-LAYOUT.md`.
* Build order belongs in `TASK.md`.

## Implementation Rules

* MUST implement mobile-first UI.
* MUST use Livewire components for interactive pages.
* MUST keep UI clean, simple, and readable for older users.
* MUST keep forms short and clear.
* MUST use large touch-friendly buttons.
* MUST use clear labels.
* MUST avoid hidden behavior.
* MUST show clear success, error, warning, loading, and empty states as defined in `UI-LAYOUT.md`.

## Authentication Rules

* MUST use custom authentication.
* MUST authenticate by username and password.
* MUST NOT use Breeze.
* MUST NOT use Jetstream.
* MUST NOT use external authentication providers.

## Database Rules

* MUST use PostgreSQL-compatible migrations.
* MUST use explicit enum/string values defined in the MD files.
* MUST define indexes where required by `DATA-MODEL.md`.
* MUST NOT create undocumented tables.
* MUST NOT create undocumented columns.

## File Storage Rules

* MUST store uploaded item photos in Cloudflare R2.
* MUST delete files from R2 when the app deletes/replaces photos.
* MUST follow file rules in `DATA-MODEL.md` and related workflow rules in `WORKFLOW.md`.

## Accurate Integration Rules

* MUST treat Accurate as read-only source.
* MUST NOT send update/write/delete requests to Accurate.
* MUST follow field mapping and refresh rules in `ACCURATE-INTEGRATION.md`.

## Audit Rule

* MUST log important actions according to `ROLE-PERMISSION.md` and workflow events in `WORKFLOW.md`.
* MUST store actor, role, action, timestamp, and JSON payload.

## Testing Rules

* MUST write or update tests for workflow, permission, validation, and critical UI actions.
* MUST use Pest-compatible tests.
* MUST run `php artisan test` before completion.
* MUST not mark work complete with failing tests.

## Completion Rule

A task is complete only when:

* The implementation matches the relevant MD files.
* No undocumented behavior was added.
* No unapproved package was added.
* `php artisan test` passes.
* The final response states what was changed and whether tests passed.
