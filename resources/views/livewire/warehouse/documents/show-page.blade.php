<div class="space-y-4">
    <x-card class="space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-base font-semibold text-[var(--color-text-main)]">Detail Dokumen</div>
                <div class="mt-1 text-sm text-[var(--color-text-muted)]">Dokumen yang sudah diproses Warehouse.</div>
            </div>
            <x-status-badge :status="$document->status" />
        </div>

        <div class="grid gap-2 text-sm">
            <div><span class="font-semibold">Nomor:</span> {{ $document->document_number }}</div>
            <div><span class="font-semibold">Tipe:</span> {{ strtoupper($document->document_type) }}</div>
        </div>

        @if ($canEdit)
            <a
                href="{{ route('warehouse.documents.edit', $document) }}"
                class="inline-flex h-11 items-center justify-center rounded-xl bg-[var(--color-navy)] px-3 text-sm font-semibold text-white hover:bg-[var(--color-navy-soft)]"
            >
                Edit
            </a>
        @endif
    </x-card>

    <div class="space-y-3">
        @foreach ($document->items as $item)
            <x-card class="space-y-2">
                <div class="text-base font-semibold text-[var(--color-text-main)]">{{ $item->nama_barang }}</div>
                <div class="text-sm text-[var(--color-text-muted)]">Qty: {{ $item->quantity }} {{ $item->satuan }}</div>
                <div class="text-sm">
                    <span class="font-semibold">Cek:</span>
                    <span class="text-[var(--color-text-main)]"><x-match-status-label :status="$item->match_status" /></span>
                </div>
                @if ($item->match_status === 'tidak_sesuai')
                    <div class="text-sm">
                        <span class="font-semibold">Alasan:</span>
                        <span class="text-[var(--color-text-main)]">{{ $item->warehouse_reason }}</span>
                    </div>
                @endif
                <div class="text-sm">
                    <span class="font-semibold">Foto:</span>
                    <span class="text-[var(--color-text-main)]">{{ $item->photos->count() }}</span>
                </div>
            </x-card>
        @endforeach
    </div>
</div>
