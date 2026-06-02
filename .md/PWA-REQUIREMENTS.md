# PWA-REQUIREMENTS.md

## Purpose

This file defines PWA requirements for the application.

UI layout rules are defined in `UI-LAYOUT.md`.

Routes are defined in `UI-ROUTE.md`.

Workflow rules are defined in `WORKFLOW.md`.

## PWA Goal

The application MUST be installable and mobile-first.

The application MUST prioritize simple usage on mobile devices for older users.

## PWA Scope

The PWA MUST support:

* Web app manifest.
* App icon configuration.
* Mobile viewport configuration.
* Installable app behavior.
* Service worker registration.
* Static asset caching.
* Safe offline fallback page.

The PWA MUST NOT implement:

* Offline PO/PR submission.
* Offline photo upload.
* Offline SPV approval.
* Offline SPV rejection.
* Offline Finance close.
* Offline Finance rejection.
* Offline Admin override.
* Offline Accurate refresh.
* Push notification.
* Background sync.
* Local queue for workflow actions.

## Manifest Requirements

The application MUST have a valid web app manifest.

Manifest MUST define:

* `name`
* `short_name`
* `start_url`
* `display`
* `background_color`
* `theme_color`
* `icons`

## Manifest Values

Use these values:

```json
{
  "name": "PO PR Validation",
  "short_name": "PO PR",
  "start_url": "/dashboard",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#ffffff"
}
```

## Icon Requirements

The application MUST provide PWA icons in these sizes:

* 192x192
* 512x512

Icons MUST be placed in public asset path.

The manifest MUST reference the icons.

## Viewport Requirement

The application layout MUST include mobile viewport meta tag:

```html
<meta name="viewport" content="width=device-width, initial-scale=1">
```

## Mobile-First Rule

* Every page MUST work properly on mobile width first.
* Desktop layout MAY enhance spacing and grid behavior.
* Mobile users MUST NOT need horizontal scrolling.
* Main actions MUST be easy to tap.
* Forms MUST be readable on mobile.

Detailed visual rules are defined in `UI-LAYOUT.md`.

## Service Worker Rule

The application MUST register one service worker.

The service worker MAY cache:

* CSS assets.
* JavaScript assets.
* App icons.
* Offline fallback page.

The service worker MUST NOT cache:

* Authenticated Livewire responses.
* PO/PR detail data.
* Uploaded photos.
* Accurate API responses.
* Workflow action responses.
* Admin log data.

## Offline Behavior

When the user is offline:

* The application MAY show cached static assets.
* The application MUST show a clear offline fallback when a network request cannot be completed.
* The application MUST NOT allow workflow mutation while offline.
* The application MUST NOT pretend an action succeeded while offline.

Offline message text MUST be:

```text
Koneksi internet terputus. Silakan coba lagi saat koneksi tersedia.
```

## Online Requirement

These actions MUST require active internet connection:

* Login.
* Logout.
* Search PO/PR.
* Submit Warehouse check.
* Upload photo.
* Delete photo.
* Replace photo.
* SPV approve.
* SPV reject.
* Finance close.
* Finance reject.
* Admin user management.
* Admin status override.
* Admin Accurate refresh.
* View activity logs.

## Livewire Rule

* Livewire requests MUST always use online server interaction.
* Livewire mutation actions MUST NOT be stored offline.
* Livewire mutation actions MUST show error if the request fails.

## Accurate Rule

* Accurate integration MUST only run online.
* Accurate search MUST NOT use cached result as source of truth.
* Accurate refresh MUST NOT run offline.

Accurate behavior is defined in `ACCURATE-INTEGRATION.md`.

## Photo Upload Rule

* Photo upload MUST require online connection.
* Photo upload MUST go to Cloudflare R2 through backend flow.
* Photo upload MUST NOT be queued offline.
* Failed upload MUST show error.
* Successful upload MUST show success state.

Photo data model is defined in `DATA-MODEL.md`.

## Authentication Rule

* Login page MUST be accessible from browser.
* Protected pages MUST still require valid authenticated session.
* PWA install mode MUST NOT bypass authentication.
* If session expires, user MUST be redirected to login.

## Install Behavior

The app SHOULD be installable from supported browsers.

The app MUST work when opened in standalone display mode.

Standalone mode MUST preserve:

* Login flow.
* Role dashboard redirect.
* Navigation.
* Livewire actions.
* File upload.
* Logout.

## Cache Version Rule

* Service worker cache name MUST include version string.
* Updating service worker cache version MUST remove old static caches.
* The application MUST NOT keep stale static assets after deployment.

## Fallback Page

The application MUST provide a simple offline fallback page.

Fallback page MUST contain:

* Clear offline message.
* No workflow action button.
* No fake document data.
* No cached PO/PR detail.

## Forbidden PWA Behavior

The application MUST NOT:

* Implement offline workflow submission.
* Implement offline approval.
* Implement offline closure.
* Implement offline Accurate refresh.
* Implement offline photo queue.
* Cache sensitive PO/PR details in service worker.
* Cache Admin logs in service worker.
* Cache uploaded photos in service worker.
* Add push notifications.
* Add background sync.
* Add unapproved PWA package.
