<div class="space-y-4">
    <x-card class="space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-base font-semibold text-[var(--color-text-main)]">Edit User</div>
                <div class="mt-1 text-sm text-[var(--color-text-muted)]">Ubah username, password, atau role.</div>
            </div>
            <button
                type="button"
                wire:click="confirmDelete"
                class="inline-flex h-11 items-center rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-danger)] hover:bg-[var(--color-surface)]"
            >
                Delete
            </button>
        </div>

        <form wire:submit="save" class="space-y-4">
            <div>
                <label for="username" class="block text-sm font-medium text-[var(--color-text-main)]">Username</label>
                <input
                    id="username"
                    type="text"
                    wire:model="username"
                    class="mt-1 h-11 w-full rounded-xl border border-[var(--color-border)] bg-white px-3 outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
                />
                @error('username')
                    <div class="mt-1 text-sm text-[var(--color-danger)]">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="newPassword" class="block text-sm font-medium text-[var(--color-text-main)]">Password (opsional)</label>
                <input
                    id="newPassword"
                    type="password"
                    wire:model="newPassword"
                    class="mt-1 h-11 w-full rounded-xl border border-[var(--color-border)] bg-white px-3 outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
                />
                @error('newPassword')
                    <div class="mt-1 text-sm text-[var(--color-danger)]">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-[var(--color-text-main)]">Role</label>
                <select
                    id="role"
                    wire:model="role"
                    class="mt-1 h-11 w-full rounded-xl border border-[var(--color-border)] bg-white px-3 outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
                >
                    <option value="admin">admin</option>
                    <option value="warehouse">warehouse</option>
                    <option value="spv">spv</option>
                    <option value="finance">finance</option>
                    <option value="purchasing">purchasing</option>
                </select>
                @error('role')
                    <div class="mt-1 text-sm text-[var(--color-danger)]">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-2">
                <a
                    href="{{ route('admin.users.index') }}"
                    class="inline-flex h-11 items-center rounded-xl border border-[var(--color-border)] bg-white px-3 text-sm font-semibold text-[var(--color-text-main)] hover:bg-[var(--color-surface)]"
                >
                    Kembali
                </a>
                <button
                    type="submit"
                    class="inline-flex h-11 items-center rounded-xl bg-[var(--color-navy)] px-3 text-sm font-semibold text-white hover:bg-[var(--color-navy-soft)]"
                >
                    Simpan
                </button>
            </div>
        </form>
    </x-card>

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
                wire:click="deleteUser"
                class="inline-flex h-11 items-center rounded-xl bg-[var(--color-danger)] px-3 text-sm font-semibold text-white hover:bg-[color:var(--color-danger)]/90"
            >
                Hapus
            </button>
        </div>
    </x-modal>
</div>
