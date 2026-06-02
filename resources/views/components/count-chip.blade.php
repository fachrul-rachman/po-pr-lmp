@props([
    'count' => 0,
])

<span {{ $attributes->class('inline-flex items-center rounded-full bg-[var(--color-blue-light)] px-2.5 py-1 text-sm font-semibold text-[var(--color-navy)]') }}>
    {{ $count }}
</span>

