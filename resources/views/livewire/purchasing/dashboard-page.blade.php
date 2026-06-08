<div class="space-y-4">
    <x-card class="space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-base font-semibold text-[var(--color-text-main)]">Dashboard</div>
                <div class="mt-1 text-sm text-[var(--color-text-muted)]">Read-only daftar semua dokumen.</div>
            </div>
            <x-count-chip :count="$count" />
        </div>

        <div class="grid gap-3 md:grid-cols-2">
            <div>
                <label for="search" class="block text-sm font-medium text-[var(--color-text-main)]">Search</label>
                <input
                    id="search"
                    type="text"
                    wire:model.live="search"
                    placeholder="Cari nomor dokumen..."
                    class="mt-1 h-11 w-full rounded-xl border border-[var(--color-border)] bg-white px-3 outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
                />
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-[var(--color-text-main)]">Filter status</label>
                <select
                    id="status"
                    wire:model.live="status"
                    class="mt-1 h-11 w-full rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
                >
                    <option value="">Semua status</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}"><x-status-label :status="$s" /></option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-card>

    @if ($documents->count() === 0)
        <x-empty-state
            icon="dashboard"
            title="Belum ada dokumen"
            description="Data PO/PR akan muncul di sini."
        />
    @else
        <div class="space-y-3 md:hidden">
            @foreach ($documents as $doc)
                <a href="{{ route('purchasing.documents.show', $doc) }}" class="block">
                    <x-card class="hover:bg-[var(--color-surface)]">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate text-base font-semibold text-[var(--color-text-main)]">
                                    {{ $doc->document_number }}
                                </div>
                                <div class="mt-1 text-sm text-[var(--color-text-muted)]">
                                    {{ strtoupper($doc->document_type) }} - {{ $doc->accurate_trans_date ?? '-' }}
                                </div>
                                <div class="mt-1 text-sm text-[var(--color-text-muted)]">
                                    Pembuat: {{ $doc->dibuat_oleh ?? '-' }}
                                </div>
                            </div>
                            <x-status-badge :status="$doc->status" />
                        </div>
                    </x-card>
                </a>
            @endforeach
        </div>

        <x-card class="hidden lg:block p-0 overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-[var(--color-surface)] text-[var(--color-text-muted)]">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Nomor</th>
                        <th class="px-4 py-3 font-semibold">Tipe</th>
                        <th class="px-4 py-3 font-semibold">Tanggal</th>
                        <th class="px-4 py-3 font-semibold">Pembuat</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-border)] bg-white">
                    @foreach ($documents as $doc)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-[var(--color-text-main)]">{{ $doc->document_number }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ strtoupper($doc->document_type) }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ $doc->accurate_trans_date ?? '-' }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ $doc->dibuat_oleh ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <x-status-badge :status="$doc->status" />
                            </td>
                            <td class="px-4 py-3">
                                <a
                                    href="{{ route('purchasing.documents.show', $doc) }}"
                                    class="inline-flex h-11 items-center rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-navy)] hover:bg-[var(--color-surface)]"
                                >
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-card>

        <div class="px-1">
            {{ $documents->links() }}
        </div>
    @endif
</div>
