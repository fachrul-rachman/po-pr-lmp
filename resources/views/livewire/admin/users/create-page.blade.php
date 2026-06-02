<x-card class="space-y-4">
    <div>
        <div class="text-base font-semibold text-[var(--color-text-main)]">Create User</div>
        <div class="mt-1 text-sm text-[var(--color-text-muted)]">Buat user baru dengan role.</div>
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
            <label for="password" class="block text-sm font-medium text-[var(--color-text-main)]">Password</label>
            <input
                id="password"
                type="password"
                wire:model="password"
                class="mt-1 h-11 w-full rounded-xl border border-[var(--color-border)] bg-white px-3 outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
            />
            @error('password')
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
                Batal
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
