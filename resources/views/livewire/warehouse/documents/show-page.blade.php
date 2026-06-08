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

    @if (($spvRejectReason ?? null) || (is_array($spvItemReasons ?? null) && count($spvItemReasons) > 0))
        <x-card class="space-y-2">
            <div class="text-base font-semibold text-[var(--color-text-main)]">Catatan SPV</div>
            @if ($spvRejectReason)
                <div class="text-sm text-[var(--color-text-main)]">
                    <span class="font-semibold">Alasan Reject:</span> {{ $spvRejectReason }}
                </div>
            @endif
            @if (count($spvItemReasons) > 0)
                <div class="space-y-1 text-sm">
                    <div class="font-semibold text-[var(--color-text-main)]">Catatan per item:</div>
                    <div class="space-y-1 text-[var(--color-text-muted)]">
                        @foreach ($spvItemReasons as $r)
                            <div>
                                <span class="font-semibold text-[var(--color-text-main)]">{{ $r['item_name'] ?: 'Item' }}:</span>
                                {{ $r['reason'] }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </x-card>
    @endif

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

                @if ($item->photos->count() > 0)
                    <div class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-4">
                        @foreach ($item->photos as $p)
                            <button
                                type="button"
                                wire:click="previewPhoto('{{ $p->id }}')"
                                class="group relative overflow-hidden rounded-xl border border-[var(--color-border)] bg-white"
                                title="Klik untuk lihat besar"
                            >
                                <img
                                    src="{{ route('item-photos.show', $p) }}"
                                    alt="{{ $p->original_name }}"
                                    class="h-20 w-full object-cover"
                                    loading="lazy"
                                />
                                <div class="absolute inset-0 hidden bg-black/10 group-hover:block"></div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </x-card>
        @endforeach
    </div>

    <x-modal :show="$showPhotoModal" title="Foto">
        @if ($previewPhotoId)
            <div class="space-y-3">
                <img
                    src="{{ route('item-photos.show', $previewPhotoId) }}"
                    alt="Preview"
                    class="w-full rounded-2xl border border-[var(--color-border)] bg-white object-contain"
                />
                <div class="flex justify-end">
                    <button
                        type="button"
                        wire:click="$set('showPhotoModal', false)"
                        class="inline-flex h-11 items-center rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-text-main)] hover:bg-[var(--color-surface)]"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        @endif
    </x-modal>
</div>
