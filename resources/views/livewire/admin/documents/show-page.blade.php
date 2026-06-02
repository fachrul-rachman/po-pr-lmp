<div class="space-y-4">
    <x-card class="space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-base font-semibold text-[var(--color-text-main)]">Detail Dokumen</div>
                <div class="mt-1 text-sm text-[var(--color-text-muted)]">Admin dapat refresh Accurate atau override status.</div>
            </div>
            <x-status-badge :status="$document->status" />
        </div>

        <div class="grid gap-2 text-sm">
            <div><span class="font-semibold">Nomor:</span> {{ $document->document_number }}</div>
            <div><span class="font-semibold">Tipe:</span> {{ strtoupper($document->document_type) }}</div>
            <div><span class="font-semibold">Accurate Synced:</span> {{ $document->accurate_synced_at?->format('Y-m-d H:i') ?? '-' }}</div>
        </div>

        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                wire:click="openRefresh"
                class="inline-flex h-11 items-center gap-2 rounded-xl bg-[var(--color-navy)] px-3 text-sm font-semibold text-white hover:bg-[var(--color-navy-soft)]"
            >
                <x-icons.refresh class="h-5 w-5" />
                Refresh Accurate
            </button>
            <button
                type="button"
                wire:click="openOverride"
                class="inline-flex h-11 items-center gap-2 rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-navy)] hover:bg-[var(--color-surface)]"
            >
                <x-icons.settings class="h-5 w-5" />
                Override Status
            </button>
        </div>
    </x-card>

    @if ($noticeSuccess)
        <x-alert-message type="success" title="Sukses">
            {{ $noticeSuccess }}
        </x-alert-message>
    @endif
    @if ($noticeInfo)
        <x-alert-message type="info" title="Info">
            {{ $noticeInfo }}
        </x-alert-message>
    @endif

    @error('refresh')
        <x-alert-message type="danger" title="Refresh gagal">
            {{ $message }}
        </x-alert-message>
    @enderror
    @error('override')
        <x-alert-message type="danger" title="Override gagal">
            {{ $message }}
        </x-alert-message>
    @enderror

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

    <x-card class="space-y-3">
        <div class="text-base font-semibold text-[var(--color-text-main)]">Log Terkait (Terbaru)</div>
        @if ($recentLogs->count() === 0)
            <div class="text-sm text-[var(--color-text-muted)]">Belum ada log terkait dokumen ini.</div>
        @else
            <div class="space-y-2 md:hidden">
                @foreach ($recentLogs as $l)
                    <div class="rounded-xl border border-[var(--color-border)] bg-white p-3 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-semibold text-[var(--color-text-main)]">{{ $l->action }}</div>
                                <div class="mt-1 text-[var(--color-text-muted)]">
                                    {{ $l->created_at?->format('Y-m-d H:i') ?? '-' }}
                                    <span class="mx-1">·</span>
                                    <x-role-label :role="$l->actor_role" />
                                    @if ($l->actor)
                                        ({{ $l->actor->username }})
                                    @endif
                                </div>
                            </div>
                            <div class="text-right text-[var(--color-text-muted)]">
                                <div><span class="font-semibold">Dari:</span> <x-status-label :status="$l->previous_status" /></div>
                                <div><span class="font-semibold">Ke:</span> <x-status-label :status="$l->new_status" /></div>
                            </div>
                        </div>
                        @if (is_array($l->payload) && count($l->payload) > 0)
                            <details class="mt-2">
                                <summary class="cursor-pointer text-[var(--color-navy)] font-semibold">Payload</summary>
                                <pre class="mt-2 overflow-auto rounded-xl bg-[var(--color-surface)] p-3 text-xs text-[var(--color-text-main)]">{{ json_encode($l->payload, JSON_PRETTY_PRINT) }}</pre>
                            </details>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="hidden md:block overflow-hidden rounded-2xl border border-[var(--color-border)] bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[var(--color-surface)] text-[var(--color-text-muted)]">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Waktu</th>
                            <th class="px-4 py-3 font-semibold">Role</th>
                            <th class="px-4 py-3 font-semibold">Action</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Payload</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--color-border)]">
                        @foreach ($recentLogs as $l)
                            <tr>
                                <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ $l->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="px-4 py-3 text-[var(--color-text-muted)]">
                                    <x-role-label :role="$l->actor_role" />
                                    @if ($l->actor)
                                        ({{ $l->actor->username }})
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-semibold text-[var(--color-text-main)]">{{ $l->action }}</td>
                                <td class="px-4 py-3 text-[var(--color-text-muted)]">
                                    <x-status-label :status="$l->previous_status" /> → <x-status-label :status="$l->new_status" />
                                </td>
                                <td class="px-4 py-3">
                                    @if (is_array($l->payload) && count($l->payload) > 0)
                                        <details>
                                            <summary class="cursor-pointer text-[var(--color-navy)] font-semibold">Lihat</summary>
                                            <pre class="mt-2 max-w-[44rem] overflow-auto rounded-xl bg-[var(--color-surface)] p-3 text-xs text-[var(--color-text-main)]">{{ json_encode($l->payload, JSON_PRETTY_PRINT) }}</pre>
                                        </details>
                                    @else
                                        <span class="text-[var(--color-text-muted)]">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <x-modal :show="$showRefreshModal" title="Refresh dari Accurate?">
        <div class="space-y-4">
            <div class="text-sm text-[var(--color-text-muted)]">
                Data header dan item akan dibandingkan dengan Accurate. Jika ada perubahan, status bisa ikut berubah sesuai rule.
            </div>
            @error('refresh')
                <div class="text-sm text-[var(--color-danger)]">{{ $message }}</div>
            @enderror
            <div class="flex gap-2 justify-end">
                <button
                    type="button"
                    wire:click="$set('showRefreshModal', false)"
                    class="inline-flex h-11 items-center rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-text-main)] hover:bg-[var(--color-surface)]"
                >
                    Batal
                </button>
                <button
                    type="button"
                    wire:click="refreshFromAccurate"
                    wire:loading.attr="disabled"
                    class="inline-flex h-11 items-center rounded-xl bg-[var(--color-navy)] px-3 text-sm font-semibold text-white hover:bg-[var(--color-navy-soft)]"
                >
                    Ya, Refresh
                </button>
            </div>
        </div>
    </x-modal>

    <x-modal :show="$showOverrideModal" title="Override Status?">
        <div class="space-y-4">
            <div class="text-sm text-[var(--color-text-muted)]">
                Override hanya mengubah status dokumen. Tidak mengubah checklist item dan tidak menghapus foto.
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-[var(--color-text-main)]">Target Status</label>
                <select
                    wire:model="overrideStatus"
                    class="h-11 w-full rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
                >
                    <option value="">Pilih status</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}"><x-status-label :status="$s" /></option>
                    @endforeach
                </select>
                @error('overrideStatus')
                    <div class="text-sm text-[var(--color-danger)]">{{ $message }}</div>
                @enderror
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-[var(--color-text-main)]">Alasan Override (wajib)</label>
                <textarea
                    wire:model="overrideReason"
                    rows="3"
                    class="w-full rounded-xl border border-[var(--color-border)] bg-white p-3 text-sm outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
                    placeholder="Tulis alasan override..."
                ></textarea>
                @error('overrideReason')
                    <div class="text-sm text-[var(--color-danger)]">{{ $message }}</div>
                @enderror
                @error('override')
                    <div class="text-sm text-[var(--color-danger)]">{{ $message }}</div>
                @enderror
            </div>
            <div class="flex gap-2 justify-end">
                <button
                    type="button"
                    wire:click="$set('showOverrideModal', false)"
                    class="inline-flex h-11 items-center rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-text-main)] hover:bg-[var(--color-surface)]"
                >
                    Batal
                </button>
                <button
                    type="button"
                    wire:click="override"
                    wire:loading.attr="disabled"
                    class="inline-flex h-11 items-center rounded-xl bg-[var(--color-danger)] px-3 text-sm font-semibold text-white hover:bg-[color:var(--color-danger)]/90"
                >
                    Ya, Override
                </button>
            </div>
        </div>
    </x-modal>
</div>
