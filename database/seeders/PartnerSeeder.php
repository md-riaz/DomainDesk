<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Partner 1: TechDomains - Active with complete branding
        $partner1 = \App\Models\Partner::create([
            'name' => 'TechDomains Inc',
            'email' => 'admin@techdomains.com',
            'slug' => 'techdomains',
            'status' => 'active',
            'is_active' => true,
        ]);

        $partner1->branding()->create([
            'logo_path' => 'logos/techdomains-logo.png',
            'favicon_path' => 'favicons/techdomains-favicon.ico',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
            'login_background_path' => 'backgrounds/techdomains-bg.jpg',
            'email_sender_name' => 'TechDomains Support',
            'email_sender_email' => 'support@techdomains.com',
            'support_email' => 'help@techdomains.com',
            'support_phone' => '+1-555-TECH-001',
        ]);

        $partner1->domains()->create([
            'domain' => 'techdomains.com',
            'is_primary' => true,
            'is_verified' => true,
            'dns_status' => 'verified',
            'ssl_status' => 'issued',
            'verified_at' => now()->subDays(30),
            'ssl_issued_at' => now()->subDays(25),
        ]);

        $partner1->domains()->create([
            'domain' => 'tech-domains.net',
            'is_primary' => false,
            'is_verified' => true,
            'dns_status' => 'verified',
            'ssl_status' => 'issued',
            'verified_at' => now()->subDays(15),
            'ssl_issued_at' => now()->subDays(14),
        ]);

        // Partner 2: DomainPro - Active with minimal branding
        $partner2 = \App\Models\Partner::create([
            'name' => 'DomainPro Solutions',
            'email' => 'contact@domainpro.io',
            'slug' => 'domainpro',
            'status' => 'active',
            'is_active' => true,
        ]);

        $partner2->branding()->create([
            'primary_color' => '#10B981',
            'secondary_color' => '#059669',
            'email_sender_name' => 'DomainPro Team',
            'email_sender_email' => 'noreply@domainpro.io',
            'support_email' => 'support@domainpro.io',
            'support_phone' => '+1-555-DOMAIN-PRO',
        ]);

        $partner2->domains()->create([
            'domain' => 'domainpro.io',
            'is_primary' => true,
            'is_verified' => true,
            'dns_status' => 'verified',
            'ssl_status' => 'issued',
            'verified_at' => now()->subDays(45),
            'ssl_issued_at' => now()->subDays(44),
        ]);

        // Partner 3: StartupDomains - Pending verification
        $partner3 = \App\Models\Partner::create([
            'name' => 'StartupDomains',
            'email' => 'hello@startupdomains.co',
            'slug' => 'startupdomains',
            'status' => 'pending',
            'is_active' => false,
        ]);

        $partner3->branding()->create([
            'primary_color' => '#F59E0B',
            'secondary_color' => '#D97706',
            'email_sender_name' => 'StartupDomains',
            'email_sender_email' => 'info@startupdomains.co',
            'support_email' => 'support@startupdomains.co',
        ]);

        $partner3->domains()->create([
            'domain' => 'startupdomains.co',
            'is_primary' => true,
            'is_verified' => false,
            'dns_status' => 'pending',
            'ssl_status' => 'pending',
        ]);

        $this->command->info('✓ Created 3 partners with branding and domains');
    }
}
