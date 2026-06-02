<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div class="text-lg font-semibold text-[var(--color-navy)]">Non Valid</div>
        <x-count-chip :count="$count" />
    </div>

    @if ($documents->count() === 0)
        <x-empty-state
            icon="alert"
            title="Tidak ada data non valid"
            description="Data yang dikembalikan SPV akan muncul di sini."
        />
    @else
        <div class="space-y-3">
            @foreach ($documents as $doc)
                <x-card class="flex items-start justify-between gap-3">
                    <a href="{{ route('warehouse.documents.show', $doc) }}" class="min-w-0 flex-1">
                        <div class="truncate text-base font-semibold text-[var(--color-text-main)]">
                            {{ $doc->document_number }}
                        </div>
                        <div class="mt-1 text-sm text-[var(--color-text-muted)]">{{ strtoupper($doc->document_type) }}</div>
                    </a>
                    <div class="shrink-0 flex flex-col items-end gap-2">
                        <x-status-badge :status="$doc->status" />
                        <a
                            href="{{ route('warehouse.documents.edit', $doc) }}"
                            class="inline-flex h-11 items-center rounded-xl bg-[var(--color-navy)] px-3 text-sm font-semibold text-white hover:bg-[var(--color-navy-soft)]"
                        >
                            Edit
                        </a>
                    </div>
                </x-card>
            @endforeach
        </div>

        <div class="px-1">
            {{ $documents->links() }}
        </div>
    @endif
</div>
