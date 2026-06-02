<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div class="text-lg font-semibold text-[var(--color-navy)]">Non Close</div>
        <x-count-chip :count="$count" />
    </div>

    @if ($documents->count() === 0)
        <x-empty-state
            icon="x-circle"
            title="Tidak ada data non close"
            description="Dokumen yang dikembalikan Finance akan muncul di sini."
        />
    @else
        <div class="space-y-3">
            @foreach ($documents as $doc)
                <a href="{{ route('spv.documents.show', $doc) }}" class="block">
                    <x-card class="hover:bg-[var(--color-surface)]">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate text-base font-semibold text-[var(--color-text-main)]">
                                    {{ $doc->document_number }}
                                </div>
                                <div class="mt-1 text-sm text-[var(--color-text-muted)]">
                                    {{ strtoupper($doc->document_type) }}
                                </div>
                            </div>
                            <x-status-badge :status="$doc->status" />
                        </div>
                    </x-card>
                </a>
            @endforeach
        </div>

        <div class="px-1">
            {{ $documents->links() }}
        </div>
    @endif
</div>

