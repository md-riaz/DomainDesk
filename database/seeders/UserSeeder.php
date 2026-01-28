<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@domaindesk.com',
            'password' => Hash::make('password'),
            'role' => Role::SuperAdmin,
            'partner_id' => null,
            'email_verified_at' => now(),
        ]);

        // Create Test Partner
        $partner = User::create([
            'name' => 'Test Partner',
            'email' => 'partner@example.com',
            'password' => Hash::make('password'),
            'role' => Role::Partner,
            'partner_id' => null,
            'email_verified_at' => now(),
        ]);

        // Create Test Client for the Partner
        User::create([
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'password' => Hash::make('password'),
            'role' => Role::Client,
            'partner_id' => $partner->id,
            'email_verified_at' => now(),
        ]);
    }
}
