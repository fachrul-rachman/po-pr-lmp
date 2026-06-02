<div class="space-y-4">
    <x-card class="space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-base font-semibold text-[var(--color-text-main)]">Logs</div>
                <div class="mt-1 text-sm text-[var(--color-text-muted)]">Audit aktivitas user dan sistem.</div>
            </div>
            <x-count-chip :count="$count" />
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <div>
                <label for="search" class="block text-sm font-medium text-[var(--color-text-main)]">Cari dokumen</label>
                <input
                    id="search"
                    type="text"
                    wire:model.live="search"
                    placeholder="Nomor dokumen..."
                    class="mt-1 h-11 w-full rounded-xl border border-[var(--color-border)] bg-white px-3 outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
                />
            </div>
            <div>
                <label for="role" class="block text-sm font-medium text-[var(--color-text-main)]">Role</label>
                <select
                    id="role"
                    wire:model.live="actorRole"
                    class="mt-1 h-11 w-full rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
                >
                    <option value="">Semua</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r }}"><x-role-label :role="$r" /></option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="action" class="block text-sm font-medium text-[var(--color-text-main)]">Action</label>
                <input
                    id="action"
                    type="text"
                    wire:model.live="action"
                    placeholder="Contoh: warehouse_submit, finance_close..."
                    class="mt-1 h-11 w-full rounded-xl border border-[var(--color-border)] bg-white px-3 outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
                />
            </div>
        </div>
    </x-card>

    @if ($logs->count() === 0)
        <x-empty-state
            icon="logs"
            title="Belum ada log"
            description="Aktivitas sistem akan muncul di sini."
        />
    @else
        <div class="space-y-3 md:hidden">
            @foreach ($logs as $l)
                <x-card class="space-y-2">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate text-base font-semibold text-[var(--color-text-main)]">{{ $l->action }}</div>
                            <div class="mt-1 text-sm text-[var(--color-text-muted)]">
                                {{ $l->created_at?->format('Y-m-d H:i') ?? '-' }}
                                <span class="mx-1">·</span>
                                <x-role-label :role="$l->actor_role" />
                                @if ($l->actor)
                                    ({{ $l->actor->username }})
                                @endif
                            </div>
                        </div>
                        <div class="text-right text-sm text-[var(--color-text-muted)]">
                            <div><x-status-label :status="$l->previous_status" /> → <x-status-label :status="$l->new_status" /></div>
                        </div>
                    </div>

                    <div class="text-sm text-[var(--color-text-muted)]">
                        <span class="font-semibold">Doc:</span>
                        {{ $l->document?->document_number ?? '-' }}
                    </div>

                    @if (is_array($l->payload) && count($l->payload) > 0)
                        <details>
                            <summary class="cursor-pointer text-[var(--color-navy)] font-semibold">Payload</summary>
                            <pre class="mt-2 overflow-auto rounded-xl bg-[var(--color-surface)] p-3 text-xs text-[var(--color-text-main)]">{{ json_encode($l->payload, JSON_PRETTY_PRINT) }}</pre>
                        </details>
                    @endif
                </x-card>
            @endforeach
        </div>

        <x-card class="hidden md:block p-0 overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-[var(--color-surface)] text-[var(--color-text-muted)]">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Waktu</th>
                        <th class="px-4 py-3 font-semibold">Doc</th>
                        <th class="px-4 py-3 font-semibold">Role</th>
                        <th class="px-4 py-3 font-semibold">Action</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Payload</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-border)] bg-white">
                    @foreach ($logs as $l)
                        <tr>
                            <td class="px-4 py-3 text-[var(--color-text-muted)]">{{ $l->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 font-semibold text-[var(--color-text-main)]">{{ $l->document?->document_number ?? '-' }}</td>
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
        </x-card>

        <div class="px-1">
            {{ $logs->links() }}
        </div>
    @endif
</div>
