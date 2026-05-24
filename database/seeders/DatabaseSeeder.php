<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Creates the default admin account. Supervisor and staff accounts
     * are created by the admin from within the panel.
     */
    public function run(): void
    {
        // Create the system admin (idempotent – skip if already exists)
        User::firstOrCreate(
            ['email' => 'admin@depot.com'],
            [
                'name'     => 'System Admin',
                'role'     => 'admin',
                'password' => Hash::make('admin1234'),
            ]
        );
    }
}
