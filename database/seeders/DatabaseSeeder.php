<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Creates the default admin account. Supervisor accounts
     * are created by the admin from within the panel.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@depot.com'],
            [
                'name'                => 'System Admin',
                'role'                => 'admin',
                'is_active'           => true,
                'must_change_password' => false,
                'password'            => 'admin1234',
            ]
        );
    }
}
