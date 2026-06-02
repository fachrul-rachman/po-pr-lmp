<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff">
    <link rel="manifest" href="/manifest.webmanifest">
    <title>{{ $title ?? config('app.name', 'App') }}</title>
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[var(--color-surface)] text-[var(--color-text-main)]">
    @php
        $user = auth()->user();
        $role = $user?->role;

        $roleLabel = match ($role) {
            'admin' => 'Admin',
            'warehouse' => 'Warehouse',
            'spv' => 'SPV',
            'finance' => 'Finance',
            'purchasing' => 'Purchasing',
            default => 'Unknown',
        };

        $menu = match ($role) {
            'admin' => [
                ['label' => 'Documents', 'route' => 'admin.documents.index', 'icon' => 'documents'],
                ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'users'],
                ['label' => 'Logs', 'route' => 'admin.logs.index', 'icon' => 'logs'],
            ],
            'warehouse' => [
                ['label' => 'Input Barang', 'route' => 'warehouse.input', 'icon' => 'search'],
                ['label' => 'Riwayat', 'route' => 'warehouse.history', 'icon' => 'history'],
                ['label' => 'Non Valid', 'route' => 'warehouse.non-valid', 'icon' => 'alert'],
            ],
            'spv' => [
                ['label' => 'Request', 'route' => 'spv.request', 'icon' => 'inbox'],
                ['label' => 'Riwayat', 'route' => 'spv.history', 'icon' => 'history'],
                ['label' => 'Non Valid', 'route' => 'spv.non-valid', 'icon' => 'alert'],
                ['label' => 'Non Close', 'route' => 'spv.non-close', 'icon' => 'x-circle'],
            ],
            'finance' => [
                ['label' => 'Request', 'route' => 'finance.request', 'icon' => 'inbox'],
                ['label' => 'Riwayat', 'route' => 'finance.history', 'icon' => 'history'],
            ],
            'purchasing' => [
                ['label' => 'Dashboard', 'route' => 'purchasing.dashboard', 'icon' => 'dashboard'],
            ],
            default => [],
        };

        $gridColsClass = match (count($menu)) {
            1 => 'grid-cols-1',
            2 => 'grid-cols-2',
            3 => 'grid-cols-3',
            4 => 'grid-cols-4',
            default => 'grid-cols-5',
        };
    @endphp

    <div
        id="offline-banner"
        class="hidden border-b border-[var(--color-border)] bg-[var(--color-danger)] px-4 py-3 text-sm font-semibold text-white"
        role="status"
        aria-live="polite"
    >
        Koneksi internet terputus. Silakan coba lagi saat koneksi tersedia.
    </div>

    <div class="min-h-screen lg:flex">
        <aside class="hidden lg:block lg:w-64 lg:shrink-0">
            <div class="h-full border-r border-[var(--color-border)] bg-[var(--color-white)]">
                <div class="px-6 py-5">
                    <div class="text-lg font-semibold text-[var(--color-navy)]">PO PR Validation</div>
                    <div class="mt-1 text-sm text-[var(--color-text-muted)]">{{ $roleLabel }}</div>
                </div>

                <nav class="px-3">
                    <ul class="space-y-1">
                        @foreach ($menu as $item)
                            @php
                                $isActive = request()->routeIs($item['route']);
                            @endphp
                            <li>
                                <a
                                    href="{{ route($item['route']) }}"
                                    class="flex items-center gap-3 rounded-xl px-3 py-3 text-base font-medium
                                        {{ $isActive ? 'bg-[var(--color-blue-light)] text-[var(--color-navy)]' : 'text-[var(--color-text-main)] hover:bg-[var(--color-surface)]' }}"
                                >
                                    <x-dynamic-component :component="'icons.'.$item['icon']" class="h-5 w-5" />
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </div>
        </aside>

        <div class="flex min-h-screen flex-1 flex-col">
            <header class="sticky top-0 z-10 border-b border-[var(--color-border)] bg-[var(--color-white)]">
                <div class="mx-auto flex h-16 max-w-[1280px] items-center justify-between px-4 md:px-6 lg:px-8">
                    <div class="min-w-0">
                        <div class="truncate text-lg font-semibold text-[var(--color-navy)] md:text-xl">
                            {{ $pageTitle ?? '' }}
                        </div>
                        <div class="text-sm text-[var(--color-text-muted)]">{{ $roleLabel }}</div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex h-11 items-center gap-2 rounded-xl border border-[var(--color-border)] bg-[var(--color-white)] px-3 text-sm font-semibold text-[var(--color-navy)] hover:bg-[var(--color-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--color-blue-light)]"
                        >
                            <x-icons.logout class="h-5 w-5" />
                            Logout
                        </button>
                    </form>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1280px] flex-1 px-4 py-5 md:px-6 md:py-6 lg:px-8">
                {{ $slot }}
            </main>

            <nav class="sticky bottom-0 z-10 border-t border-[var(--color-border)] bg-[var(--color-white)] lg:hidden">
                <ul class="mx-auto grid max-w-[1280px] px-2 {{ $gridColsClass }}">
                    @foreach ($menu as $item)
                        @php
                            $isActive = request()->routeIs($item['route']);
                        @endphp
                        <li class="py-2">
                            <a
                                href="{{ route($item['route']) }}"
                                class="flex flex-col items-center justify-center gap-1 rounded-xl px-2 py-2 text-xs font-semibold
                                    {{ $isActive ? 'bg-[var(--color-blue-light)] text-[var(--color-navy)]' : 'text-[var(--color-text-muted)] hover:bg-[var(--color-surface)]' }}"
                            >
                                <x-dynamic-component :component="'icons.'.$item['icon']" class="h-5 w-5" />
                                <span class="leading-none">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </div>

    <div
        id="global-toast"
        class="pointer-events-none fixed inset-x-0 top-3 z-50 hidden px-3"
        aria-live="polite"
        aria-atomic="true"
    >
        <div class="mx-auto max-w-[720px]">
            <div class="pointer-events-auto rounded-2xl border border-[var(--color-border)] bg-white p-4 shadow-lg">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div id="global-toast-title" class="text-sm font-semibold text-[var(--color-text-main)]">Info</div>
                        <div id="global-toast-message" class="mt-1 text-sm text-[var(--color-text-muted)]">...</div>
                    </div>
                    <button
                        type="button"
                        id="global-toast-close"
                        class="inline-flex h-10 items-center rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-navy)] hover:bg-[var(--color-surface)]"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    @livewireScripts
    <script>
        (function () {
            function setOfflineBannerVisible(isOffline) {
                var el = document.getElementById('offline-banner');
                if (!el) return;
                el.classList.toggle('hidden', !isOffline);
            }

            setOfflineBannerVisible(!navigator.onLine);
            window.addEventListener('offline', function () { setOfflineBannerVisible(true); });
            window.addEventListener('online', function () { setOfflineBannerVisible(false); });

            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function () {
                    navigator.serviceWorker.register('/sw.js').catch(function () {});
                });
            }
        })();
    </script>
    <script>
        (function () {
            const toast = document.getElementById('global-toast');
            const titleEl = document.getElementById('global-toast-title');
            const msgEl = document.getElementById('global-toast-message');
            const closeBtn = document.getElementById('global-toast-close');

            if (!toast || !titleEl || !msgEl || !closeBtn) return;

            let timer = null;

            function showToast(title, message) {
                titleEl.textContent = title || 'Info';
                msgEl.textContent = message || '';
                toast.classList.remove('hidden');

                if (timer) clearTimeout(timer);
                timer = setTimeout(() => toast.classList.add('hidden'), 7000);
            }

            closeBtn.addEventListener('click', () => {
                if (timer) clearTimeout(timer);
                toast.classList.add('hidden');
            });

            function extractStatus(detail) {
                if (!detail) return null;
                if (typeof detail.status === 'number') return detail.status;
                if (detail.xhr && typeof detail.xhr.status === 'number') return detail.xhr.status;
                if (detail.response && typeof detail.response.status === 'number') return detail.response.status;
                return null;
            }

            function setInlineUploadError(prop, message) {
                document.querySelectorAll(`[data-livewire-upload-error=\"${prop}\"]`).forEach((el) => {
                    el.textContent = message || '';
                });
            }

            function clearInlineUploadError(prop) {
                setInlineUploadError(prop, '');
            }

            function clearUploadInput(prop) {
                document.querySelectorAll(`[data-livewire-upload-input=\"${prop}\"]`).forEach((el) => {
                    try { el.value = ''; } catch (e) {}
                });
            }

            function resetWireUploadProp(componentId, prop) {
                try {
                    // Livewire global API (v4): best-effort.
                    const cmp = window.Livewire && typeof window.Livewire.find === 'function' ? window.Livewire.find(componentId) : null;
                    if (cmp && cmp.$wire && typeof cmp.$wire.set === 'function') {
                        cmp.$wire.set(prop, []);
                    }
                } catch (e) {}
            }

            function handleUploadError(event) {
                const componentId = event && event.detail && event.detail.id ? event.detail.id : null;
                const prop = event && event.detail && event.detail.property ? event.detail.property : null;
                if (!prop) return;

                // Livewire's `livewire-upload-error` event does not include the HTTP status.
                setInlineUploadError(prop, 'File terlalu besar. Kecilkan ukuran foto atau pilih 1 per 1.');
                clearUploadInput(prop);
                if (componentId) resetWireUploadProp(componentId, prop);
            }

            function handleUploadProgress(event) {
                const prop = event && event.detail && event.detail.property ? event.detail.property : null;
                const progress = event && event.detail && typeof event.detail.progress === 'number' ? event.detail.progress : null;
                if (!prop || progress === null) return;

                document.querySelectorAll(`[data-livewire-upload-progress=\"${prop}\"]`).forEach((el) => {
                    el.textContent = progress >= 100 ? '' : `Progress: ${progress}%`;
                });
            }

            function clearUploadProgress(event) {
                const prop = event && event.detail && event.detail.property ? event.detail.property : null;
                if (!prop) return;

                document.querySelectorAll(`[data-livewire-upload-progress=\"${prop}\"]`).forEach((el) => {
                    el.textContent = '';
                });
            }

            // Track last upload property to attach errors when Livewire crashes on JSON.parse (HTML response).
            const lastUploadPropByComponent = new Map();

            function handleUploadStart(event) {
                const componentId = event && event.detail && event.detail.id ? event.detail.id : null;
                const prop = event && event.detail && event.detail.property ? event.detail.property : null;
                if (!componentId || !prop) return;

                lastUploadPropByComponent.set(componentId, prop);
                clearInlineUploadError(prop);
            }

            // Livewire upload events (support multiple naming variants).
            window.addEventListener('livewire-upload-error', handleUploadError);
            window.addEventListener('livewire:upload-error', handleUploadError);
            document.addEventListener('livewire-upload-error', handleUploadError);
            document.addEventListener('livewire:upload-error', handleUploadError);

            window.addEventListener('livewire-upload-start', handleUploadStart);
            document.addEventListener('livewire-upload-start', handleUploadStart);
            window.addEventListener('livewire-upload-progress', handleUploadProgress);
            document.addEventListener('livewire-upload-progress', handleUploadProgress);
            window.addEventListener('livewire-upload-finish', clearUploadProgress);
            window.addEventListener('livewire-upload-cancel', clearUploadProgress);
            window.addEventListener('livewire-upload-error', clearUploadProgress);

            // Catch Livewire temporary upload response issues (HTML/302 -> JSON.parse crash).
            window.addEventListener('error', (e) => {
                const msg = (e && e.message) ? String(e.message) : '';
                if (!msg.includes('Unexpected token') || !msg.includes('<')) return;

                // Common case: Livewire upload got an HTML response (redirect/error page) and crashed on JSON.parse.
                // Best-effort: attach the error to the last known upload input for the page.
                for (const [componentId, prop] of lastUploadPropByComponent.entries()) {
                    setInlineUploadError(prop, 'File terlalu besar. Kecilkan ukuran foto atau pilih 1 per 1.');
                    clearUploadInput(prop);
                    resetWireUploadProp(componentId, prop);
                }
            });
        })();
    </script>
</body>
</html>
