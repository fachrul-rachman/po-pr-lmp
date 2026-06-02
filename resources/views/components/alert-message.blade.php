@props([
    'type' => 'info', // info|success|warning|danger
    'title' => null,
])

@php
    $styles = match ($type) {
        'success' => ['bg' => 'bg-[color:var(--color-success)]/10', 'text' => 'text-[var(--color-success)]', 'icon' => 'check-circle'],
        'warning' => ['bg' => 'bg-[color:var(--color-warning)]/10', 'text' => 'text-[var(--color-warning)]', 'icon' => 'alert'],
        'danger' => ['bg' => 'bg-[color:var(--color-danger)]/10', 'text' => 'text-[var(--color-danger)]', 'icon' => 'x-circle'],
        default => ['bg' => 'bg-[var(--color-blue-light)]', 'text' => 'text-[var(--color-navy)]', 'icon' => 'info'],
    };
@endphp

<div {{ $attributes->class("rounded-2xl border border-[var(--color-border)] {$styles['bg']} p-4") }}>
    <div class="flex items-start gap-3">
        <x-dynamic-component :component="'icons.'.$styles['icon']" class="mt-0.5 h-5 w-5 {{ $styles['text'] }}" />
        <div class="min-w-0">
            @if ($title)
                <div class="text-base font-semibold {{ $styles['text'] }}">{{ $title }}</div>
            @endif
            <div class="mt-0.5 text-sm text-[var(--color-text-main)]">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>

