@props([
    'text' => 'Memuat...',
])

<x-card class="flex items-center gap-3">
    <x-icons.spinner class="h-5 w-5 animate-spin text-[var(--color-navy)]" />
    <div class="text-base font-medium text-[var(--color-text-main)]">{{ $text }}</div>
</x-card>

