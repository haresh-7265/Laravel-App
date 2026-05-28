<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Seed the admins table with a default admin account.
     */
    public function run(): void
    {
        // Seed 4 admin users
        for ($i = 1; $i <= 4; $i++) {
            $admin = Admin::create([
                'name' => "Admin {$i}",
                'email' => "admin{$i}@example.com",
                'password' => 'password',
            ]);

            $admin->assignRole('admin');
        }
    }
}
