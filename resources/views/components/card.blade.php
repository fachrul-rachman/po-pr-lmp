@props([
    'as' => 'div',
])

<{{ $as }}
    {{ $attributes->class([
        'rounded-2xl border border-[var(--color-border)] bg-[var(--color-white)] p-4 md:p-5',
    ]) }}
>
    {{ $slot }}
</{{ $as }}>

