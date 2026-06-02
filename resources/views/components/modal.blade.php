@props([
    'show' => false,
    'title' => null,
])

@if ($show)
    <div class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative w-full max-w-lg rounded-2xl border border-[var(--color-border)] bg-[var(--color-white)] p-4 shadow-xl sm:p-5">
            @if ($title)
                <div class="text-lg font-semibold text-[var(--color-text-main)]">{{ $title }}</div>
            @endif
            <div class="mt-3 text-base text-[var(--color-text-main)]">
                {{ $slot }}
            </div>
        </div>
    </div>
@endif

