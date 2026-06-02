<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <link rel="manifest" href="/manifest.webmanifest">
    <title>{{ config('app.name', 'App') }}</title>
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-slate-900">
    <div
        id="offline-banner"
        class="hidden border-b border-[var(--color-border)] bg-[var(--color-danger)] px-4 py-3 text-sm font-semibold text-white"
        role="status"
        aria-live="polite"
    >
        Koneksi internet terputus. Silakan coba lagi saat koneksi tersedia.
    </div>
    {{ $slot }}
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
</body>
</html>
