<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class LoginPage extends Component
{
    public string $username = '';
    public string $password = '';

    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirectRoute('dashboard', navigate: true);
        }
    }

    public function login(): void
    {
        $validated = $this->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['username' => $validated['username'], 'password' => $validated['password']])) {
            $this->addError('username', 'Username atau password salah.');
            return;
        }

        session()->regenerate();

        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login-page');
    }
}

