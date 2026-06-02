<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Enums\UserRoles;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Minimal seed users for local/manual testing across all roles.
        $users = [
            ['username' => 'admin', 'role' => UserRoles::ADMIN],
            ['username' => 'warehouse', 'role' => UserRoles::WAREHOUSE],
            ['username' => 'spv', 'role' => UserRoles::SPV],
            ['username' => 'finance', 'role' => UserRoles::FINANCE],
            ['username' => 'purchasing', 'role' => UserRoles::PURCHASING],
        ];

        foreach ($users as $u) {
            User::firstOrCreate(
                ['username' => $u['username']],
                [
                    'password' => Hash::make('password'),
                    'role' => $u['role'],
                ],
            );
        }
    }
}
