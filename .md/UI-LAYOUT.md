# UI-LAYOUT.md

## Purpose

This file defines visual layout, responsive behavior, UI states, modal behavior, icon usage, and message behavior.

Routes are defined in `UI-ROUTE.md`.

Role permission is defined in `ROLE-PERMISSION.md`.

Workflow behavior is defined in `WORKFLOW.md`.

## Design Goal

The UI MUST be:

* Clean.
* Simple.
* Neat.
* Mobile-first.
* Easy for older users.
* Good looking without visual clutter.
* Clear enough to use without training.

## Color System

The application MUST use white and navy blue as the main color theme.

### Required Colors

Use these fixed color tokens:

| Token        | Hex       | Usage                               |
| ------------ | --------- | ----------------------------------- |
| `white`      | `#FFFFFF` | Main background                     |
| `navy`       | `#0B1F3A` | Header, primary button, active menu |
| `navy-soft`  | `#12345A` | Button hover, secondary emphasis    |
| `blue-light` | `#EAF2FF` | Soft active background              |
| `border`     | `#E5E7EB` | Card and input border               |
| `text-main`  | `#111827` | Main text                           |
| `text-muted` | `#6B7280` | Secondary text                      |
| `success`    | `#15803D` | Success state                       |
| `warning`    | `#B45309` | Warning state                       |
| `danger`     | `#B91C1C` | Error and destructive state         |
| `surface`    | `#F8FAFC` | Page background                     |

### Color Rules

* Main page background MUST be `surface`.
* Main content cards MUST be `white`.
* Primary buttons MUST use `navy`.
* Active navigation MUST use `navy` or `blue-light`.
* Text MUST use high contrast.
* The UI MUST NOT use bright decorative gradients.
* The UI MUST NOT use more than the defined colors without approval.

## Typography

* Default font MUST use system sans-serif.
* Body text minimum size MUST be `16px`.
* Important labels minimum size MUST be `16px`.
* Page title minimum size MUST be `22px` on mobile.
* Page title minimum size MUST be `26px` on desktop.
* Small helper text minimum size MUST be `14px`.
* Text MUST NOT be too thin.
* Use `font-medium` or `font-semibold` for labels and important values.

## Icon Rule

The UI SHOULD use many icons to make pages easier to scan.

Icons MUST follow these rules:

* Every main menu item MUST have an icon.
* Every primary action button SHOULD have an icon.
* Every status badge SHOULD have an icon.
* Empty state MUST have an icon.
* Success, error, warning, and loading states MUST have icons.
* Icons MUST NOT replace text labels.
* Every icon button MUST have visible text unless it is a small repeated action with `aria-label`.
* Icons MUST be simple line icons.
* Icons MUST use navy, muted gray, success, warning, or danger color.
* Icons MUST NOT use external icon packages without approval.
* Use local inline SVG icon components.

## Layout System

* Mobile layout MUST be implemented first.
* Desktop layout MUST enhance the mobile layout.
* No page may require horizontal scrolling on mobile.
* Content width on desktop MUST be centered.
* Maximum desktop content width MUST be `1280px`.
* Page padding on mobile MUST be `16px`.
* Page padding on desktop MUST be `24px`.
* Card border radius MUST be `16px`.
* Button border radius MUST be `12px`.
* Input border radius MUST be `12px`.

## Touch Target Rule

For older users:

* Buttons MUST be at least `44px` tall.
* Inputs MUST be at least `44px` tall.
* Clickable cards MUST have enough spacing.
* Small icon-only controls MUST be avoided.
* Form actions MUST have clear text.
* Destructive buttons MUST be visually separated from primary buttons.

## App Shell

### Mobile

Mobile app shell MUST use:

* Top header.
* Bottom navigation.
* Single-column content.
* Sticky main action area only when useful for long forms.

### Desktop

Desktop app shell MUST use:

* Left sidebar navigation.
* Top header.
* Main content area.
* Maximum content width `1280px`.

### Header

Header MUST show:

* Page title.
* Current user role.
* Logout button.

Header MUST NOT show workflow actions.

## Navigation

### Mobile Navigation

* Use bottom navigation.
* Show only current role menus.
* Use icon + short label.
* Active menu MUST be clearly highlighted.
* Bottom navigation MUST stay visible.

### Desktop Navigation

* Use left sidebar.
* Show icon + full label.
* Active menu MUST be clearly highlighted.
* Sidebar width MUST be between `240px` and `280px`.

Role menu list is defined in `UI-ROUTE.md`.

## Card Layout

Document cards MUST be simple.

Each document card MUST show only:

* Document number.
* Item summary.
* Status badge.

Card data rule is defined in `UI-ROUTE.md`.

### Mobile Card

Mobile card layout:

```text
[Status Badge]
Document Number
Item Summary
```

### Desktop Card

Desktop card layout:

```text
Document Number | Item Summary | Status Badge
```

### Card Rules

* Card MUST be clickable when it opens detail.
* Card MUST have white background.
* Card MUST have border.
* Card MUST have enough spacing.
* Card MUST NOT show full item detail.
* Card MUST NOT show long workflow history.

## Status Badge

Status badge MUST use icon + label.

### Status Labels

| Status                | Label            |
| --------------------- | ---------------- |
| `warehouse_submitted` | Menunggu SPV     |
| `spv_approved`        | Menunggu Finance |
| `spv_rejected`        | Non Valid        |
| `finance_rejected`    | Non Close        |
| `finance_closed`      | Closed           |

### Status Badge Color

| Status                | Style        |
| --------------------- | ------------ |
| `warehouse_submitted` | Navy soft    |
| `spv_approved`        | Blue light   |
| `spv_rejected`        | Danger soft  |
| `finance_rejected`    | Warning soft |
| `finance_closed`      | Success soft |

## Count Chip

Count chip MUST be used only on pages defined in `UI-ROUTE.md`.

Count chip rules:

* Must be placed beside menu/page title.
* Must use rounded pill shape.
* Must show number only.
* Must be easy to read.
* Must not be hidden on mobile.

## Forms

Form layout MUST be simple.

### Form Rules

* One column on mobile.
* Two columns allowed on desktop only for short fields.
* Labels MUST be above inputs.
* Required fields MUST show required marker.
* Error message MUST appear below the field.
* Primary action MUST be at bottom.
* Cancel/back action MUST be secondary.

### Input Barang Form

Warehouse input page MUST show:

1. Search section.
2. Document header summary after document found.
3. Item checklist sections.
4. Submit button at bottom.

The page MUST NOT show item checklist before document is found.

## Item Checklist Layout

Each item MUST be shown as its own card.

Each item card MUST show:

* Item name.
* Quantity and unit.
* Keterangan.
* Match toggle.
* Reason field when needed.
* Photo upload area.
* Uploaded photo preview list.

### Match Toggle

Match toggle MUST use two large buttons:

* `Sesuai`
* `Tidak Sesuai`

Rules:

* Selected state MUST be visually clear.
* `Tidak Sesuai` MUST show reason textarea.
* `Sesuai` MUST hide reason textarea.
* Toggle MUST NOT use tiny switch component.

### Photo Upload

Photo upload area MUST show:

* Upload button.
* Photo preview thumbnails.
* Delete button for each photo when editable.
* Clear error if photo is missing.

Photo storage behavior is defined in `DATA-MODEL.md`.

## Detail Page Layout

Detail page MUST use sections in this order:

1. Document summary.
2. Item list.
3. Warehouse check result.
4. SPV decision section when available.
5. Finance decision section when available.
6. Workflow history.
7. Page actions.

Role visibility is defined in `ROLE-PERMISSION.md`.

## Page Actions

* Primary action MUST be visually dominant.
* Secondary action MUST be less dominant.
* Destructive action MUST use danger color.
* Actions MUST be placed at bottom of form/detail.
* On mobile, main action buttons MUST be full width.
* On desktop, main action buttons MAY align right.

## Modal Rules

Modals MUST be used for confirmation only.

Modals MUST appear for:

* Delete user.
* Refresh from Accurate.
* Admin status override.
* SPV approve.
* SPV reject.
* Finance close.
* Finance reject.
* Delete photo.

### Modal Layout

Modal MUST show:

* Icon.
* Clear title.
* Short explanation.
* Confirm button.
* Cancel button.

### Modal Rules

* Modal title MUST be direct.
* Modal text MUST be short.
* Cancel button MUST always be visible.
* Destructive confirm button MUST use danger color.
* Modal MUST NOT contain long document detail.
* Modal MUST NOT be used for normal page navigation.

## Message Rules

### Success Message

Success message MUST be green and short.

Use these messages:

| Event                      | Message                                           |
| -------------------------- | ------------------------------------------------- |
| Warehouse submit           | `Data berhasil dikirim ke SPV.`                   |
| Warehouse resubmit         | `Data berhasil dikirim ulang ke SPV.`             |
| SPV approve                | `Data berhasil disetujui dan dikirim ke Finance.` |
| SPV reject                 | `Data berhasil dikembalikan ke Warehouse.`        |
| Finance close              | `Data berhasil di-close.`                         |
| Finance reject             | `Data berhasil dikembalikan ke SPV.`              |
| Admin override             | `Status berhasil diubah.`                         |
| Accurate refresh no change | `Data Accurate sudah terbaru.`                    |
| Accurate refresh changed   | `Data berhasil diperbarui dari Accurate.`         |
| User create                | `User berhasil dibuat.`                           |
| User update                | `User berhasil diperbarui.`                       |
| User delete                | `User berhasil dihapus.`                          |
| Photo upload               | `Foto berhasil diupload.`                         |
| Photo delete               | `Foto berhasil dihapus.`                          |

### Error Message

Error message MUST be red and clear.

Use these messages:

| Case                   | Message                                                               |
| ---------------------- | --------------------------------------------------------------------- |
| Login failed           | `Username atau password salah.`                                       |
| PO/PR not found        | `Nomor PO/PR tidak ditemukan di Accurate.`                            |
| Required field missing | `Lengkapi data yang wajib diisi.`                                     |
| Missing item photo     | `Setiap item wajib memiliki minimal 1 foto.`                          |
| Missing item check     | `Setiap item wajib dipilih Sesuai atau Tidak Sesuai.`                 |
| Missing reason         | `Alasan wajib diisi.`                                                 |
| Unauthorized access    | `Anda tidak memiliki akses ke halaman ini.`                           |
| Workflow invalid       | `Aksi tidak bisa dilakukan pada status saat ini.`                     |
| Accurate error         | `Gagal mengambil data dari Accurate.`                                 |
| Upload failed          | `Upload foto gagal. Silakan coba lagi.`                               |
| Delete failed          | `Data gagal dihapus. Silakan coba lagi.`                              |
| Offline                | `Koneksi internet terputus. Silakan coba lagi saat koneksi tersedia.` |

### Warning Message

Warning message MUST be amber and short.

Use warning for:

* Accurate refresh will replace changed data.
* Admin override will move workflow status.
* Delete photo will remove photo permanently.
* Delete user will disable user access.

### Loading State

Loading state MUST show:

* Spinner icon.
* Short text.
* Disabled action button.

Use these loading texts:

| Action           | Text                  |
| ---------------- | --------------------- |
| Search Accurate  | `Mencari data...`     |
| Submit           | `Mengirim data...`    |
| Upload photo     | `Mengupload foto...`  |
| Refresh Accurate | `Memperbarui data...` |
| Save user        | `Menyimpan user...`   |

## Empty State

Every list page MUST have empty state.

Empty state MUST show:

* Icon.
* Short title.
* Short description.
* No fake data.

### Empty State Text

| Page                 | Title                      | Description                                           |
| -------------------- | -------------------------- | ----------------------------------------------------- |
| Warehouse Riwayat    | `Belum ada riwayat`        | `Data yang sudah dikirim akan muncul di sini.`        |
| Warehouse Non Valid  | `Tidak ada data non valid` | `Data yang dikembalikan SPV akan muncul di sini.`     |
| SPV Request          | `Tidak ada request`        | `Data dari Warehouse akan muncul di sini.`            |
| SPV Non Valid        | `Tidak ada data non valid` | `Data yang Anda reject akan muncul di sini.`          |
| SPV Non Close        | `Tidak ada data non close` | `Data yang dikembalikan Finance akan muncul di sini.` |
| Finance Request      | `Tidak ada request`        | `Data yang sudah disetujui SPV akan muncul di sini.`  |
| Finance Riwayat      | `Belum ada riwayat`        | `Data yang sudah diproses akan muncul di sini.`       |
| Purchasing Dashboard | `Belum ada dokumen`        | `Data PO/PR akan muncul di sini.`                     |
| Admin Documents      | `Belum ada dokumen`        | `Data PO/PR akan muncul di sini.`                     |
| Admin Logs           | `Belum ada log`            | `Aktivitas sistem akan muncul di sini.`               |

## Login Page Layout

Login page MUST be simple.

### Mobile

Login page MUST show:

* App name.
* Short description.
* Username field.
* Password field.
* Login button.

### Desktop

Login form MUST be centered.

### Login Rules

* No registration link.
* No forgot password link.
* No social login.
* No decorative complex illustration.

## Admin Layout

Admin pages MUST prioritize clarity over density.

### Admin Documents

* Use searchable card list on mobile.
* Use table on desktop.
* Detail action opens document detail page.
* Refresh and override only appear on detail page.

### Admin Users

* Use card list on mobile.
* Use table on desktop.
* Create/edit form must be simple.
* Delete must use confirmation modal.

### Admin Logs

* Use stacked log cards on mobile.
* Use table on desktop.
* JSON payload must be collapsible.
* JSON payload must not be shown expanded by default.

## Warehouse Layout

Warehouse pages MUST be optimized for phone use.

### Input Barang

* Search box must be at top.
* After document found, show document summary card.
* Items must appear as separate cards.
* Submit button must be full width on mobile.
* Do not show too many columns.

### Riwayat

* Use document cards.
* Show count chip beside title.
* Detail page read-only unless workflow allows edit.

### Non Valid

* Use document cards.
* Show count chip beside title.
* Edit action must be clear.

## SPV Layout

SPV pages MUST be review-focused.

### Request

* Use document cards.
* Show count chip beside title.
* Detail page must clearly show Warehouse submit time.

### Detail

* Approve and Reject buttons must be separated.
* Reject button must not be accidentally tapped.
* Reject form must show reason field.
* Item-level reason fields appear only when rejecting.

## Finance Layout

Finance pages MUST be closure-focused.

### Request

* Use document cards.
* Show count chip beside title.

### Detail

* Close and Reject buttons must be separated.
* Close button must require confirmation modal.
* Reject button must require reason.

## Purchasing Layout

Purchasing pages MUST be read-only.

### Dashboard

* Use document cards on mobile.
* Use table on desktop.
* Provide search and status filter.
* No mutation buttons.

### Detail

* Show full document process.
* No edit button.
* No workflow action button.

## Desktop Table Rule

Tables MAY be used only on desktop for:

* Admin documents.
* Admin users.
* Admin logs.
* Purchasing dashboard.

Tables MUST NOT be required on mobile.

Mobile MUST use cards.

## Responsive Breakpoints

Use Tailwind default breakpoints.

Required behavior:

* `< md`: mobile card layout.
* `md` and above: enhanced spacing.
* `lg` and above: sidebar layout and desktop tables where allowed.

## Accessibility Rules

* Every input MUST have label.
* Every button MUST have clear text or `aria-label`.
* Text contrast MUST be readable.
* Focus state MUST be visible.
* Error messages MUST be connected to fields visually.
* Do not rely on color only to communicate status.
* Status must use text + icon + color.

## Forbidden UI Behavior

The UI MUST NOT:

* Use cluttered dashboards.
* Use tiny buttons.
* Use icon-only main actions.
* Hide important status in color only.
* Use complex animations.
* Use decorative elements that reduce readability.
* Show role actions that the user cannot perform.
* Show workflow actions in page header.
* Use horizontal scroll on mobile.
* Use desktop table as the only mobile layout.
* Show fake success when server action fails.
* Show cached PO/PR detail as source of truth.
* Add external icon or UI packages without approval.
