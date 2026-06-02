<div class="space-y-4">
    <x-card class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <div class="text-base font-semibold text-[var(--color-text-main)]">Users</div>
                <div class="mt-1 text-sm text-[var(--color-text-muted)]">Kelola user aplikasi.</div>
            </div>

            <a
                href="{{ route('admin.users.create') }}"
                class="inline-flex h-11 items-center gap-2 rounded-xl bg-[var(--color-navy)] px-3 text-sm font-semibold text-white hover:bg-[var(--color-navy-soft)]"
            >
                <x-icons.users class="h-5 w-5" />
                Create
            </a>
        </div>

        <div>
            <label for="search" class="block text-sm font-medium text-[var(--color-text-main)]">Search</label>
            <input
                id="search"
                type="text"
                wire:model.live="search"
                placeholder="Cari username..."
                class="mt-1 h-11 w-full rounded-xl border border-[var(--color-border)] bg-white px-3 outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
            />
        </div>
    </x-card>

    @if ($users->count() === 0)
        <x-empty-state
            icon="users"
            title="Belum ada user"
            description="User yang dibuat Admin akan muncul di sini."
        />
    @else
        <div class="space-y-3 md:hidden">
            @foreach ($users as $u)
                <x-card class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="truncate text-base font-semibold text-[var(--color-text-main)]">{{ $u->username }}</div>
                        <div class="mt-0.5 text-sm text-[var(--color-text-muted)]"><x-role-label :role="$u->role" /></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a
                            href="{{ route('admin.users.edit', $u) }}"
                            class="inline-flex h-11 items-center rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-navy)] hover:bg-[var(--color-surface)]"
                        >
                            Edit
                        </a>
                        <button
                            type="button"
                            wire:click="confirmDelete('{{ $u->id }}')"
                            class="inline-flex h-11 items-center rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-danger)] hover:bg-[var(--color-surface)]"
                        >
                            Delete
                        </button>
                    </div>
                </x-card>
            @endforeach
        </div>

        <x-card class="hidden md:block p-0 overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-[var(--color-surface)] text-[var(--color-text-muted)]">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Username</th>
                        <th class="px-4 py-3 font-semibold">Role</th>
                        <th class="px-4 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-border)] bg-white">
                    @foreach ($users as $u)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-[var(--color-text-main)]">{{ $u->username }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-muted)]"><x-role-label :role="$u->role" /></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a
                                        href="{{ route('admin.users.edit', $u) }}"
                                        class="inline-flex h-11 items-center rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-navy)] hover:bg-[var(--color-surface)]"
                                    >
                                        Edit
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="confirmDelete('{{ $u->id }}')"
                                        class="inline-flex h-11 items-center rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-danger)] hover:bg-[var(--color-surface)]"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-card>

        <div class="px-1">
            {{ $users->links() }}
        </div>
    @endif

    <x-modal :show="$showDeleteModal" title="Hapus user?">
        <div class="text-sm text-[var(--color-text-main)]">
            User yang dihapus akan tidak bisa login lagi.
        </div>
        <div class="mt-4 flex items-center justify-end gap-2">
            <button
                type="button"
                wire:click="cancelDelete"
                class="inline-flex h-11 items-center rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-text-main)] hover:bg-[var(--color-surface)]"
            >
                Batal
            </button>
            <button
                type="button"
                wire:click="deleteSelected"
                class="inline-flex h-11 items-center rounded-xl bg-[var(--color-danger)] px-3 text-sm font-semibold text-white hover:bg-[color:var(--color-danger)]/90"
            >
                Hapus
            </button>
        </div>
    </x-modal>
</div>
