<?php

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Models\Domain;
use App\Models\DomainDocument;
use App\Models\User;
use Illuminate\Database\Seeder;

class DomainDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $domains = Domain::take(10)->get();
        
        foreach ($domains as $domain) {
            // Create 1-3 documents per domain
            DomainDocument::factory()
                ->count(fake()->numberBetween(1, 3))
                ->create([
                    'domain_id' => $domain->id,
                    'uploaded_by' => $domain->client_id,
                ]);
                
            // Create one verified document
            if (fake()->boolean()) {
                $verifier = User::whereIn('role', ['super_admin', 'partner'])->first();
                if ($verifier) {
                    DomainDocument::factory()
                        ->verified()
                        ->create([
                            'domain_id' => $domain->id,
                            'uploaded_by' => $domain->client_id,
                            'verified_by' => $verifier->id,
                        ]);
                }
            }
        }
    }
}
