<div class="space-y-4">
    <x-card class="space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-base font-semibold text-[var(--color-text-main)]">Detail Dokumen</div>
                <div class="mt-1 text-sm text-[var(--color-text-muted)]">Read-only view untuk Purchasing.</div>
            </div>
            <x-status-badge :status="$document->status" />
        </div>

        <div class="grid gap-2 text-sm">
            <div><span class="font-semibold">Nomor:</span> {{ $document->document_number }}</div>
            <div><span class="font-semibold">Tipe:</span> {{ strtoupper($document->document_type) }}</div>
            <div><span class="font-semibold">Accurate Synced:</span> {{ $document->accurate_synced_at?->format('Y-m-d H:i') ?? '-' }}</div>
        </div>
    </x-card>

    <x-card class="space-y-2">
        <div class="text-base font-semibold text-[var(--color-text-main)]">Proses</div>
        <div class="grid gap-2 text-sm">
            <div>
                <span class="font-semibold">Warehouse submit:</span>
                {{ $document->warehouse_submitted_at?->format('Y-m-d H:i') ?? '-' }}
                @if ($document->warehouseSubmittedBy)
                    ({{ $document->warehouseSubmittedBy->username }})
                @endif
            </div>
            <div>
                <span class="font-semibold">SPV proses:</span>
                {{ $document->spv_processed_at?->format('Y-m-d H:i') ?? '-' }}
                @if ($document->spvProcessedBy)
                    ({{ $document->spvProcessedBy->username }})
                @endif
            </div>
            <div>
                <span class="font-semibold">Finance proses:</span>
                {{ $document->finance_processed_at?->format('Y-m-d H:i') ?? '-' }}
                @if ($document->financeProcessedBy)
                    ({{ $document->financeProcessedBy->username }})
                @endif
            </div>
            <div>
                <span class="font-semibold">Admin override:</span>
                {{ $document->admin_overridden_at?->format('Y-m-d H:i') ?? '-' }}
            </div>
        </div>
    </x-card>

    <x-card class="space-y-2">
        <div class="text-base font-semibold text-[var(--color-text-main)]">Data Accurate</div>
        <div class="grid gap-2 text-sm">
            <div><span class="font-semibold">Tujuan Pembelian:</span> {{ $document->tujuan_pembelian ?? '-' }}</div>
            <div><span class="font-semibold">Dikirim Ke:</span> {{ $document->dikirim_ke ?? '-' }}</div>
            <div><span class="font-semibold">Department:</span> {{ $document->department ?? '-' }}</div>
            <div><span class="font-semibold">Dibuat Oleh:</span> {{ $document->dibuat_oleh ?? '-' }}</div>
            <div><span class="font-semibold">Diminta Oleh:</span> {{ $document->diminta_oleh ?? '-' }}</div>
        </div>
    </x-card>

    <div class="space-y-3">
        @foreach ($items as $item)
            <x-card class="space-y-2">
                <div class="text-base font-semibold text-[var(--color-text-main)]">{{ $item->nama_barang }}</div>
                <div class="text-sm text-[var(--color-text-muted)]">Qty: {{ $item->quantity }} {{ $item->satuan }}</div>
                <div class="text-sm">
                    <span class="font-semibold">Cek Warehouse:</span>
                    <span class="text-[var(--color-text-main)]"><x-match-status-label :status="$item->match_status" /></span>
                </div>
                @if ($item->match_status === 'tidak_sesuai')
                    <div class="text-sm">
                        <span class="font-semibold">Alasan Warehouse:</span>
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
                            <a
                                href="{{ route('item-photos.show', $p) }}"
                                class="group relative overflow-hidden rounded-xl border border-[var(--color-border)] bg-white"
                                title="Klik untuk lihat besar"
                                target="_blank"
                                rel="noreferrer"
                            >
                                <img
                                    src="{{ route('item-photos.show', $p) }}"
                                    alt="{{ $p->original_name }}"
                                    class="h-20 w-full object-cover"
                                    loading="lazy"
                                />
                                <div class="absolute inset-0 hidden bg-black/10 group-hover:block"></div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-card>
        @endforeach
    </div>

    <x-card class="space-y-3">
        <div class="text-base font-semibold text-[var(--color-text-main)]">Riwayat Keputusan</div>
        @if (count($decisions) === 0)
            <div class="text-sm text-[var(--color-text-muted)]">Belum ada keputusan workflow.</div>
        @else
            <div class="space-y-2">
                @foreach ($decisions as $d)
                    <div class="rounded-xl border border-[var(--color-border)] bg-white p-3 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-semibold text-[var(--color-text-main)]">
                                    <x-decision-label :type="$d->decision_type" />
                                </div>
                                <div class="mt-1 text-[var(--color-text-muted)]">
                                    {{ $d->created_at?->format('Y-m-d H:i') ?? '-' }}
                                    <span class="mx-1">·</span>
                                    <x-role-label :role="$d->actor_role" />
                                    @if ($d->actor)
                                        ({{ $d->actor->username }})
                                    @endif
                                </div>
                            </div>
                            <div class="text-right text-[var(--color-text-muted)]">
                                <div><span class="font-semibold">Dari:</span> <x-status-label :status="$d->from_status" /></div>
                                <div><span class="font-semibold">Ke:</span> <x-status-label :status="$d->to_status" /></div>
                            </div>
                        </div>
                        @if ($d->reason)
                            <div class="mt-2 text-[var(--color-text-main)]">
                                <span class="font-semibold">Reason:</span> {{ $d->reason }}
                            </div>
                        @endif
                        @if ($d->itemReasons && $d->itemReasons->count() > 0)
                            <div class="mt-2 text-[var(--color-text-muted)]">
                                Item reasons: {{ $d->itemReasons->count() }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</div>
