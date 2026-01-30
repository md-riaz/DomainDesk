<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Partner;
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

        // Get the first partner from PartnerSeeder (TechDomains Inc)
        $partner1 = Partner::where('email', 'admin@techdomains.com')->first();
        
        if ($partner1) {
            // Create Partner User for TechDomains
            $partnerUser1 = User::create([
                'name' => 'Test Partner',
                'email' => 'admin@techdomains.com',
                'password' => Hash::make('password'),
                'role' => Role::Partner,
                'partner_id' => $partner1->id,
                'email_verified_at' => now(),
            ]);

            // Create Test Client for TechDomains Partner
            User::create([
                'name' => 'Test Client',
                'email' => 'client@example.com',
                'password' => Hash::make('password'),
                'role' => Role::Client,
                'partner_id' => $partner1->id,
                'email_verified_at' => now(),
            ]);
        }

        // Get the second partner (DomainPro Solutions)
        $partner2 = Partner::where('email', 'contact@domainpro.io')->first();
        
        if ($partner2) {
            // Create Partner User for DomainPro
            User::create([
                'name' => 'DomainPro Admin',
                'email' => 'contact@domainpro.io',
                'password' => Hash::make('password'),
                'role' => Role::Partner,
                'partner_id' => $partner2->id,
                'email_verified_at' => now(),
            ]);
        }

        // Create additional test partner user (not linked to any partner record)
        User::create([
            'name' => 'Standalone Partner',
            'email' => 'partner@example.com',
            'password' => Hash::make('password'),
            'role' => Role::Partner,
            'partner_id' => null,
            'email_verified_at' => now(),
        ]);
    }
}
