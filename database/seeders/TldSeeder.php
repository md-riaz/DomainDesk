<?php

namespace Database\Seeders;

use App\Models\Registrar;
use App\Models\Tld;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $namecheap = Registrar::where('slug', 'namecheap')->first();
        $godaddy = Registrar::where('slug', 'godaddy')->first();

        if (!$namecheap || !$godaddy) {
            $this->command->error('Registrars not found. Please run RegistrarSeeder first.');
            return;
        }

        // Popular TLDs for NameCheap
        $tlds = [
            ['extension' => 'com', 'registrar_id' => $namecheap->id],
            ['extension' => 'net', 'registrar_id' => $namecheap->id],
            ['extension' => 'org', 'registrar_id' => $namecheap->id],
            ['extension' => 'info', 'registrar_id' => $namecheap->id],
            ['extension' => 'biz', 'registrar_id' => $namecheap->id],
            ['extension' => 'io', 'registrar_id' => $namecheap->id],
            ['extension' => 'co', 'registrar_id' => $namecheap->id],
            ['extension' => 'dev', 'registrar_id' => $namecheap->id],
            ['extension' => 'app', 'registrar_id' => $namecheap->id],
            ['extension' => 'xyz', 'registrar_id' => $namecheap->id],
        ];

        // Add some TLDs for GoDaddy
        $tlds[] = ['extension' => 'com', 'registrar_id' => $godaddy->id];
        $tlds[] = ['extension' => 'net', 'registrar_id' => $godaddy->id];
        $tlds[] = ['extension' => 'org', 'registrar_id' => $godaddy->id];

        foreach ($tlds as $tld) {
            Tld::create([
                'registrar_id' => $tld['registrar_id'],
                'extension' => $tld['extension'],
                'min_years' => 1,
                'max_years' => 10,
                'supports_dns' => true,
                'supports_whois_privacy' => true,
                'is_active' => true,
            ]);
        }

        $this->command->info('Created ' . count($tlds) . ' TLDs');
    }
}
