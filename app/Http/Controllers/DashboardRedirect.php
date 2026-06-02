<?php

namespace App\Http\Controllers;

use App\Support\Enums\UserRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DashboardRedirect
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        return match ($user->role) {
            UserRoles::ADMIN => redirect('/admin/documents'),
            UserRoles::WAREHOUSE => redirect('/warehouse/input'),
            UserRoles::SPV => redirect('/spv/request'),
            UserRoles::FINANCE => redirect('/finance/request'),
            UserRoles::PURCHASING => redirect('/purchasing/dashboard'),
            default => abort(403),
        };
    }
}

