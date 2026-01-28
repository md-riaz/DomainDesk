<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Domain;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some audit logs for existing models
        $partners = Partner::take(3)->get();
        
        foreach ($partners as $partner) {
            $users = User::where('partner_id', $partner->id)->take(2)->get();
            $domains = Domain::where('partner_id', $partner->id)->take(5)->get();
            
            foreach ($domains as $domain) {
                AuditLog::factory()
                    ->count(3)
                    ->create([
                        'partner_id' => $partner->id,
                        'user_id' => $users->random()->id ?? null,
                        'auditable_type' => Domain::class,
                        'auditable_id' => $domain->id,
                    ]);
            }
        }
    }
}
