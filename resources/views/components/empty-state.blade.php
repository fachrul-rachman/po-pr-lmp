@props([
    'title',
    'description',
    'icon' => 'documents',
])

<x-card class="flex flex-col items-center justify-center py-10 text-center">
    <x-dynamic-component :component="'icons.'.$icon" class="h-10 w-10 text-[var(--color-navy)]" />
    <div class="mt-4 text-lg font-semibold text-[var(--color-text-main)]">{{ $title }}</div>
    <div class="mt-1 max-w-md text-sm text-[var(--color-text-muted)]">{{ $description }}</div>
</x-card>

