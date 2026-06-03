<div class="space-y-4">
    <x-card class="space-y-4">
        <div class="text-base font-semibold text-[var(--color-text-main)]">Cari Dokumen</div>

        <div class="grid gap-3 md:grid-cols-3">
            <div class="md:col-span-2">
                <label for="term" class="block text-sm font-medium text-[var(--color-text-main)]">Nomor PO/PR</label>
                <input
                    id="term"
                    type="text"
                    wire:model="term"
                    placeholder="Ketik minimal 4 karakter..."
                    class="mt-1 h-11 w-full rounded-xl border border-[var(--color-border)] bg-white px-3 outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
                />
                @error('term')
                    <div class="mt-1 text-sm text-[var(--color-danger)]">{{ $message }}</div>
                @enderror
            </div>
            <div>
                <label for="type" class="block text-sm font-medium text-[var(--color-text-main)]">Tipe</label>
                <select
                    id="type"
                    wire:model="type"
                    class="mt-1 h-11 w-full rounded-xl border border-[var(--color-border)] bg-white px-3 outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
                >
                    <option value="">PR + PO</option>
                    <option value="pr">PR</option>
                    <option value="po">PO</option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-end">
            <button
                type="button"
                wire:click="search"
                wire:loading.attr="disabled"
                wire:target="search"
                class="inline-flex h-11 items-center gap-2 rounded-xl bg-[var(--color-navy)] px-4 text-sm font-semibold text-white hover:bg-[var(--color-navy-soft)] disabled:cursor-not-allowed disabled:opacity-60"
            >
                <x-icons.search class="h-5 w-5" />
                <span wire:loading.remove wire:target="search">Cari</span>
                <span wire:loading wire:target="search">Mencari...</span>
            </button>
        </div>
    </x-card>

    @if (count($results) > 0)
        <x-card class="space-y-3">
            <div class="text-base font-semibold text-[var(--color-text-main)]">Hasil Pencarian</div>
            <div class="space-y-2">
                @foreach ($results as $r)
                    <button
                        type="button"
                        wire:click="choose('{{ $r['document_type'] }}', '{{ $r['accurate_id'] }}')"
                        class="w-full rounded-2xl border border-[var(--color-border)] bg-white p-4 text-left hover:bg-[var(--color-surface)]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate text-base font-semibold text-[var(--color-text-main)]">
                                    {{ $r['document_number'] }}
                                </div>
                                <div class="mt-1 text-sm text-[var(--color-text-muted)]">
                                    {{ strtoupper($r['document_type']) }} · {{ $r['trans_date'] ?? '-' }}
                                </div>
                            </div>
                            <div class="shrink-0 text-sm font-semibold text-[var(--color-navy)]">Pilih</div>
                        </div>
                    </button>
                @endforeach
            </div>
        </x-card>
    @endif

    @if ($document)
        <x-card class="space-y-3">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-base font-semibold text-[var(--color-text-main)]">Dokumen</div>
                    @if ($document->status === null)
                        <div class="mt-1 text-sm text-[var(--color-text-muted)]">Cek semua item dan pilih foto. Foto akan diupload saat submit.</div>
                    @else
                        <div class="mt-1 text-sm text-[var(--color-text-muted)]">Dokumen ini sudah disubmit dan sedang dalam proses.</div>
                    @endif
                </div>
                <x-status-badge :status="$document->status" />
            </div>

            <div class="grid gap-2 text-sm">
                <div><span class="font-semibold">Nomor:</span> {{ $document->document_number }}</div>
                <div><span class="font-semibold">Tipe:</span> {{ strtoupper($document->document_type) }}</div>
                @if ($document->tujuan_pembelian)
                    <div><span class="font-semibold">Tujuan:</span> {{ $document->tujuan_pembelian }}</div>
                @endif
                @if ($document->dikirim_ke)
                    <div><span class="font-semibold">Dikirim ke:</span> {{ $document->dikirim_ke }}</div>
                @endif
            </div>
        </x-card>

        @if ($document->status !== null)
            <x-alert-message type="info" title="Dokumen sudah ada di sistem">
                Untuk edit (selama belum di-approve SPV), buka halaman detail dokumen.
            </x-alert-message>

            <div class="grid gap-2 sm:grid-cols-2">
                <a
                    href="{{ route('warehouse.documents.show', $document) }}"
                    class="inline-flex h-11 items-center justify-center rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-text-main)] hover:bg-[var(--color-surface)]"
                >
                    Lihat Detail
                </a>
                @if ($document->isEditableByWarehouse())
                    <a
                        href="{{ route('warehouse.documents.edit', $document) }}"
                        class="inline-flex h-11 items-center justify-center rounded-xl bg-[var(--color-navy)] px-3 text-sm font-semibold text-white hover:bg-[var(--color-navy-soft)]"
                    >
                        Edit
                    </a>
                @endif
            </div>
        @else
            <div class="space-y-3">
            @foreach ($items as $item)
                <x-card wire:key="warehouse-input-item-{{ $item->id }}" class="space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-base font-semibold text-[var(--color-text-main)]">{{ $item->nama_barang }}</div>
                            <div class="mt-1 text-sm text-[var(--color-text-muted)]">
                                Qty: {{ $item->quantity }} {{ $item->satuan }}
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button
                            type="button"
                            wire:click="setMatch('{{ $item->id }}','sesuai')"
                            class="flex-1 h-12 rounded-2xl border border-[var(--color-border)] px-3 text-base font-semibold
                                {{ ($match[$item->id] ?? '') === 'sesuai' ? 'bg-[var(--color-blue-light)] text-[var(--color-navy)]' : 'bg-white text-[var(--color-text-main)] hover:bg-[var(--color-surface)]' }}"
                        >
                            Sesuai
                        </button>
                        <button
                            type="button"
                            wire:click="setMatch('{{ $item->id }}','tidak_sesuai')"
                            class="flex-1 h-12 rounded-2xl border border-[var(--color-border)] px-3 text-base font-semibold
                                {{ ($match[$item->id] ?? '') === 'tidak_sesuai' ? 'bg-[color:var(--color-warning)]/10 text-[var(--color-warning)]' : 'bg-white text-[var(--color-text-main)] hover:bg-[var(--color-surface)]' }}"
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
                        <div class="text-sm text-[var(--color-text-muted)]">Bisa pakai kamera atau pilih dari galeri. Bisa lebih dari 1 foto.</div>

                        @php($staged = $uploads[$item->id] ?? [])
                        @php($staged = is_array($staged) ? $staged : [$staged])
                        @php($stagedCount = count(array_filter($staged)))

                        <div class="text-sm text-[var(--color-text-muted)]">
                            Dipilih: <span class="font-semibold text-[var(--color-text-main)]">{{ $stagedCount }}</span>
                        </div>

                        @if ($item->photos->count() === 0 && $stagedCount === 0)
                            <div class="text-sm text-[var(--color-text-muted)]">Belum ada foto. Pilih foto lalu submit.</div>
                        @endif

                        @if ($item->photos->count() > 0)
                            <div class="space-y-2">
                                @foreach ($item->photos as $p)
                                    <div wire:key="warehouse-input-photo-{{ $p->id }}" class="rounded-xl border border-[var(--color-border)] bg-white p-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="truncate text-sm font-semibold text-[var(--color-text-main)]">{{ $p->original_name }}</div>
                                                <div class="mt-0.5 text-xs text-[var(--color-text-muted)]">{{ $p->mime_type }} · {{ $p->size_bytes }} bytes</div>
                                            </div>
                                            <div class="shrink-0 flex gap-2">
                                                <button
                                                    type="button"
                                                    wire:click="deletePhoto('{{ $p->id }}')"
                                                    class="inline-flex h-11 items-center rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-danger)] hover:bg-[var(--color-surface)]"
                                                >
                                                    Delete
                                                </button>
                                            </div>
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
                                <div class="text-sm font-semibold text-[var(--color-text-main)]">Foto dipilih (akan diupload saat submit)</div>
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
            <x-alert-message type="danger" title="Tidak bisa submit">
                {{ $message }}
            </x-alert-message>
        @enderror

        <button
            type="button"
            wire:click="submit"
            wire:loading.attr="disabled"
            wire:target="uploads,submit"
            class="w-full h-12 rounded-xl bg-[var(--color-navy)] text-base font-semibold text-white hover:bg-[var(--color-navy-soft)] disabled:cursor-not-allowed disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="uploads,submit">Submit ke SPV</span>
            <span wire:loading wire:target="uploads">Menyiapkan foto...</span>
            <span wire:loading wire:target="submit">Submit...</span>
        </button>
        @endif
    @endif
</div>
