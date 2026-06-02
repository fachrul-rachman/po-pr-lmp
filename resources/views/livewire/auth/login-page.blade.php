<div class="min-h-screen flex items-center justify-center p-4 bg-[var(--color-surface)]">
    <div class="w-full max-w-sm rounded-2xl border border-[var(--color-border)] bg-[var(--color-white)] p-6 shadow-sm">
        <h1 class="text-xl font-semibold text-[var(--color-text-main)]">PO PR Validation</h1>
        <p class="mt-1 text-sm text-[var(--color-text-muted)]">Login menggunakan username dan password.</p>

        <form wire:submit="login" class="mt-6 space-y-4">
            <div>
                <label for="username" class="block text-sm font-medium text-[var(--color-text-main)]">Username</label>
                <input
                    id="username"
                    type="text"
                    wire:model="username"
                    autocomplete="username"
                    class="mt-1 h-11 w-full rounded-xl border border-[var(--color-border)] bg-white px-3 outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
                />
                @error('username')
                    <p class="mt-1 text-sm text-[var(--color-danger)]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-[var(--color-text-main)]">Password</label>
                <input
                    id="password"
                    type="password"
                    wire:model="password"
                    autocomplete="current-password"
                    class="mt-1 h-11 w-full rounded-xl border border-[var(--color-border)] bg-white px-3 outline-none focus:border-[var(--color-navy)] focus:ring-2 focus:ring-[var(--color-blue-light)]"
                />
                @error('password')
                    <p class="mt-1 text-sm text-[var(--color-danger)]">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="h-11 w-full rounded-xl bg-[var(--color-navy)] text-sm font-semibold text-white hover:bg-[var(--color-navy-soft)] focus:outline-none focus:ring-2 focus:ring-[var(--color-blue-light)]"
            >
                Login
            </button>
        </form>
    </div>
</div>
