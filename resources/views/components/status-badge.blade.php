@props([
    'status' => null,
])

@php
    $label = match ((string) $status) {
        'warehouse_submitted' => 'Menunggu SPV',
        'spv_approved' => 'Menunggu Finance',
        'spv_rejected' => 'Non Valid',
        'finance_rejected' => 'Non Close',
        'finance_closed' => 'Closed',
        default => $status ? ucfirst(str_replace('_', ' ', (string) $status)) : '-',
    };

    $style = match ($status) {
        'finance_closed' => ['bg' => 'bg-[color:var(--color-success)]/10', 'text' => 'text-[var(--color-success)]', 'icon' => 'check-circle'],
        'finance_rejected' => ['bg' => 'bg-[color:var(--color-warning)]/10', 'text' => 'text-[var(--color-warning)]', 'icon' => 'x-circle'],
        'spv_rejected' => ['bg' => 'bg-[color:var(--color-danger)]/10', 'text' => 'text-[var(--color-danger)]', 'icon' => 'alert'],
        'spv_approved' => ['bg' => 'bg-[var(--color-blue-light)]', 'text' => 'text-[var(--color-navy)]', 'icon' => 'inbox'],
        'warehouse_submitted' => ['bg' => 'bg-[color:var(--color-navy)]/10', 'text' => 'text-[var(--color-navy)]', 'icon' => 'inbox'],
        default => ['bg' => 'bg-[var(--color-surface)]', 'text' => 'text-[var(--color-text-muted)]', 'icon' => 'info'],
    };
@endphp

<span {{ $attributes->class("inline-flex items-center gap-2 rounded-full border border-[var(--color-border)] {$style['bg']} px-3 py-1 text-sm font-semibold {$style['text']}") }}>
    <x-dynamic-component :component="'icons.'.$style['icon']" class="h-4 w-4" />
    <span>{{ $label }}</span>
</span>
