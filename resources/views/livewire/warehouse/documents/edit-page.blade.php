<div class="space-y-4">
    <x-card class="space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-base font-semibold text-[var(--color-text-main)]">Edit Dokumen</div>
                @if ($document->status === 'spv_rejected')
                    <div class="mt-1 text-sm text-[var(--color-text-muted)]">Perbaiki item lalu kirim ulang ke SPV.</div>
                @else
                    <div class="mt-1 text-sm text-[var(--color-text-muted)]">Perbaiki data sebelum SPV approve.</div>
                @endif
            </div>
            <x-status-badge :status="$document->status" />
        </div>

        <div class="grid gap-2 text-sm">
            <div><span class="font-semibold">Nomor:</span> {{ $document->document_number }}</div>
            <div><span class="font-semibold">Tipe:</span> {{ strtoupper($document->document_type) }}</div>
        </div>
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
        @foreach ($items as $item)
            <x-card wire:key="warehouse-edit-item-{{ $item->id }}" class="space-y-3">
                <div class="min-w-0">
                    <div class="text-base font-semibold text-[var(--color-text-main)]">{{ $item->nama_barang }}</div>
                    <div class="mt-1 text-sm text-[var(--color-text-muted)]">Qty: {{ $item->quantity }} {{ $item->satuan }}</div>
                </div>

                <div class="flex gap-2">
                    <button
                        type="button"
                        wire:click="setMatch('{{ $item->id }}','sesuai')"
                        class="flex-1 h-12 rounded-2xl border border-[var(--color-border)] px-3 text-base font-semibold
                            {{ ($match[$item->id] ?? '') === 'sesuai' ? 'bg-[var(--color-navy)] text-white border-[var(--color-navy)]' : 'bg-white text-[var(--color-text-main)] hover:bg-[var(--color-surface)]' }}"
                    >
                        Sesuai
                    </button>
                    <button
                        type="button"
                        wire:click="setMatch('{{ $item->id }}','tidak_sesuai')"
                        class="flex-1 h-12 rounded-2xl border border-[var(--color-border)] px-3 text-base font-semibold
                            {{ ($match[$item->id] ?? '') === 'tidak_sesuai' ? 'bg-[var(--color-warning)] text-white border-[var(--color-warning)]' : 'bg-white text-[var(--color-text-main)] hover:bg-[var(--color-surface)]' }}"
                    >
                        Tidak Sesuai
                    </button>
                </div>
                <div class="text-sm text-[var(--color-text-muted)]">Pilih "Tidak Sesuai" lalu isi alasan.</div>

                @if (($match[$item->id] ?? '') === 'tidak_sesuai')
                    <div>
                        <label class="block text-sm font-medium text-[var(--color-text-main)]">Alasan</label>
                        <textarea
                            wire:model="reasons.{{ $item->id }}"
                            wire:blur="setReason('{{ $item->id }}', $event.target.value)"
                            rows="3"
                            class="mt-1 w-full rounded-xl border border-[var(--color-border)] bg-white p-3 text-sm outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
                        ></textarea>
                        @error('reason_'.$item->id)
                            <div class="mt-1 text-sm text-[var(--color-danger)]">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-[var(--color-text-main)]">Foto</div>
                    <div class="text-sm text-[var(--color-text-muted)]">Bisa pakai kamera atau pilih dari galeri. Bisa lebih dari 1 foto. Foto akan diupload saat simpan/kirim ulang.</div>

                    @php($staged = $uploads[$item->id] ?? [])
                    @php($staged = is_array($staged) ? $staged : [$staged])
                    @php($stagedCount = count(array_filter($staged)))

                    <div class="text-sm text-[var(--color-text-muted)]">
                        Dipilih: <span class="font-semibold text-[var(--color-text-main)]">{{ $stagedCount }}</span>
                    </div>

                    @if ($item->photos->count() === 0 && $stagedCount === 0)
                        <div class="text-sm text-[var(--color-text-muted)]">Belum ada foto. Pilih foto lalu simpan.</div>
                    @endif

                    @if ($item->photos->count() > 0)
                        <div class="space-y-2">
                            @foreach ($item->photos as $p)
                                <div wire:key="warehouse-edit-photo-{{ $p->id }}" class="rounded-xl border border-[var(--color-border)] bg-white p-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-[var(--color-text-main)]">{{ $p->original_name }}</div>
                                            <div class="mt-0.5 text-xs text-[var(--color-text-muted)]">{{ $p->mime_type }} · {{ $p->size_bytes }} bytes</div>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="deletePhoto('{{ $p->id }}')"
                                            class="inline-flex h-11 items-center rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-danger)] hover:bg-[var(--color-surface)]"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <input
                        type="file"
                        accept="image/*"
                        multiple
                        wire:model="uploads.{{ $item->id }}"
                        data-livewire-upload-input="uploads.{{ $item->id }}"
                        class="block w-full text-sm"
                    />
                    @error('upload_'.$item->id)
                        <div class="mt-1 text-sm text-[var(--color-danger)]">{{ $message }}</div>
                    @enderror
                    <div class="mt-1 text-sm text-[var(--color-danger)]" data-livewire-upload-error="uploads.{{ $item->id }}"></div>

                    @if ($stagedCount > 0)
                        <div class="space-y-2">
                            <div class="text-sm font-semibold text-[var(--color-text-main)]">Foto dipilih (akan diupload saat simpan/kirim ulang)</div>
                            @foreach ($staged as $idx => $f)
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-[var(--color-border)] bg-white p-3">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-[var(--color-text-main)]">{{ method_exists($f, 'getClientOriginalName') ? $f->getClientOriginalName() : 'photo' }}</div>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="removeStagedUpload('{{ $item->id }}', {{ (int) $idx }})"
                                        class="inline-flex h-11 items-center rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-danger)] hover:bg-[var(--color-surface)]"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div wire:loading wire:target="uploads.{{ $item->id }}" class="text-sm font-semibold text-[var(--color-text-muted)]">
                        Memproses foto...
                    </div>
                    <div
                        class="text-sm font-semibold text-[var(--color-text-muted)]"
                        data-livewire-upload-progress="uploads.{{ $item->id }}"
                    ></div>
                </div>
            </x-card>
        @endforeach
    </div>

    @error('submit')
        <x-alert-message type="danger" title="Tidak bisa menyimpan">
            {{ $message }}
        </x-alert-message>
    @enderror

    <button
        type="button"
        wire:click="saveChanges"
        wire:loading.attr="disabled"
        wire:target="uploads,saveChanges"
        class="w-full h-12 rounded-xl bg-[var(--color-navy)] text-base font-semibold text-white hover:bg-[var(--color-navy-soft)] disabled:cursor-not-allowed disabled:opacity-60"
    >
        @if ($document->status === 'spv_rejected')
            <span wire:loading.remove wire:target="uploads,saveChanges">Kirim Ulang ke SPV</span>
        @else
            <span wire:loading.remove wire:target="uploads,saveChanges">Simpan Perubahan</span>
        @endif
        <span wire:loading wire:target="uploads">Menyiapkan foto...</span>
        <span wire:loading wire:target="saveChanges">Menyimpan...</span>
    </button>
</div>
