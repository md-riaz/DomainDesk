<?php

namespace Database\Seeders;

use App\Models\Registrar;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RegistrarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Registrar::create([
            'name' => 'NameCheap',
            'slug' => 'namecheap',
            'api_class' => 'App\Services\Registrars\NameCheapRegistrar',
            'credentials' => [
                'api_user' => 'demo_api_user',
                'api_key' => 'demo_api_key_12345',
                'username' => 'demo_username',
                'sandbox' => true,
            ],
            'is_active' => true,
            'is_default' => true,
        ]);

        Registrar::create([
            'name' => 'GoDaddy',
            'slug' => 'godaddy',
            'api_class' => 'App\Services\Registrars\GoDaddyRegistrar',
            'credentials' => [
                'api_key' => 'demo_godaddy_key',
                'api_secret' => 'demo_godaddy_secret',
                'sandbox' => true,
            ],
            'is_active' => true,
            'is_default' => false,
        ]);

        Registrar::create([
            'name' => 'ResellerClub',
            'slug' => 'resellerclub',
            'api_class' => 'App\Services\Registrars\ResellerClubRegistrar',
            'credentials' => [
                'reseller_id' => '123456',
                'api_key' => 'demo_rc_api_key',
                'sandbox' => true,
            ],
            'is_active' => false,
            'is_default' => false,
        ]);
    }
}
