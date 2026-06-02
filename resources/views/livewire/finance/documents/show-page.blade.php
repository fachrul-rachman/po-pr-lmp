<div class="space-y-4">
    <x-card class="space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-base font-semibold text-[var(--color-text-main)]">Detail Dokumen</div>
                <div class="mt-1 text-sm text-[var(--color-text-muted)]">Proses penutupan atau reject oleh Finance.</div>
            </div>
            <x-status-badge :status="$document->status" />
        </div>

        <div class="grid gap-2 text-sm">
            <div><span class="font-semibold">Nomor:</span> {{ $document->document_number }}</div>
            <div><span class="font-semibold">Tipe:</span> {{ strtoupper($document->document_type) }}</div>
        </div>
    </x-card>

    @error('close')
        <x-alert-message type="danger" title="Tidak bisa close">
            {{ $message }}
        </x-alert-message>
    @enderror
    @error('reject')
        <x-alert-message type="danger" title="Tidak bisa reject">
            {{ $message }}
        </x-alert-message>
    @enderror

    <x-card class="space-y-2">
        <div class="text-base font-semibold text-[var(--color-text-main)]">Info SPV</div>
        <div class="grid gap-2 text-sm">
            <div>
                <span class="font-semibold">Diproses oleh:</span>
                {{ $document->spvProcessedBy?->username ?? '-' }}
            </div>
            <div>
                <span class="font-semibold">Waktu:</span>
                {{ $document->spv_processed_at?->format('Y-m-d H:i') ?? '-' }}
            </div>
        </div>
    </x-card>

    @if ($document->finance_processed_at)
        <x-card class="space-y-2">
            <div class="text-base font-semibold text-[var(--color-text-main)]">Info Finance</div>
            <div class="grid gap-2 text-sm">
                <div>
                    <span class="font-semibold">Diproses oleh:</span>
                    {{ $document->financeProcessedBy?->username ?? '-' }}
                </div>
                <div>
                    <span class="font-semibold">Waktu:</span>
                    {{ $document->finance_processed_at?->format('Y-m-d H:i') ?? '-' }}
                </div>
            </div>
        </x-card>
    @endif

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

                @if ($canReject)
                    <div class="mt-2">
                        <label class="block text-sm font-medium text-[var(--color-text-main)]">Catatan Finance (opsional)</label>
                        <textarea
                            wire:model="itemReasons.{{ $item->id }}"
                            rows="2"
                            class="mt-1 w-full rounded-xl border border-[var(--color-border)] bg-white p-3 text-sm outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
                            placeholder="Contoh: item perlu dicek ulang / bukti kurang jelas / dll"
                        ></textarea>
                    </div>
                @endif
            </x-card>
        @endforeach
    </div>

    @if ($canReject || $canClose)
        <div class="grid gap-2 md:grid-cols-2">
            <button
                type="button"
                wire:click="openReject"
                @disabled(! $canReject)
                class="h-12 rounded-xl border border-[var(--color-border)] bg-white text-base font-semibold text-[var(--color-danger)] hover:bg-[var(--color-surface)] disabled:cursor-not-allowed disabled:opacity-60"
            >
                Reject
            </button>
            <button
                type="button"
                wire:click="openClose"
                @disabled(! $canClose)
                class="h-12 rounded-xl bg-[var(--color-navy)] text-base font-semibold text-white hover:bg-[var(--color-navy-soft)] disabled:cursor-not-allowed disabled:opacity-60"
            >
                Close
            </button>
        </div>
    @endif

    <x-modal :show="$showCloseModal" title="Close Dokumen?">
        <div class="space-y-4">
            <div class="text-sm text-[var(--color-text-muted)]">
                Dokumen akan ditandai <span class="font-semibold text-[var(--color-text-main)]">Closed</span> dan menjadi read-only.
            </div>
            <div class="flex gap-2 justify-end">
                <button
                    type="button"
                    wire:click="$set('showCloseModal', false)"
                    class="inline-flex h-11 items-center rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-text-main)] hover:bg-[var(--color-surface)]"
                >
                    Batal
                </button>
                <button
                    type="button"
                    wire:click="close"
                    class="inline-flex h-11 items-center rounded-xl bg-[var(--color-navy)] px-3 text-sm font-semibold text-white hover:bg-[var(--color-navy-soft)]"
                >
                    Ya, Close
                </button>
            </div>
        </div>
    </x-modal>

    <x-modal :show="$showRejectModal" title="Reject Dokumen?">
        <div class="space-y-4">
            <div class="text-sm text-[var(--color-text-muted)]">
                Dokumen akan dikembalikan ke SPV.
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-[var(--color-text-main)]">Alasan Reject (wajib)</label>
                <textarea
                    wire:model="rejectReason"
                    rows="3"
                    class="w-full rounded-xl border border-[var(--color-border)] bg-white p-3 text-sm outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
                    placeholder="Tulis alasan dokumen dikembalikan..."
                ></textarea>
                @error('rejectReason')
                    <div class="text-sm text-[var(--color-danger)]">{{ $message }}</div>
                @enderror
                @error('reject')
                    <div class="text-sm text-[var(--color-danger)]">{{ $message }}</div>
                @enderror
            </div>
            <div class="flex gap-2 justify-end">
                <button
                    type="button"
                    wire:click="$set('showRejectModal', false)"
                    class="inline-flex h-11 items-center rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-text-main)] hover:bg-[var(--color-surface)]"
                >
                    Batal
                </button>
                <button
                    type="button"
                    wire:click="reject"
                    wire:loading.attr="disabled"
                    class="inline-flex h-11 items-center rounded-xl bg-[var(--color-danger)] px-3 text-sm font-semibold text-white hover:bg-[color:var(--color-danger)]/90"
                >
                    Ya, Reject
                </button>
            </div>
        </div>
    </x-modal>

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
