<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PartnerBranding>
 */
class PartnerBrandingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'partner_id' => \App\Models\Partner::factory(),
            'logo_path' => fake()->boolean(50) ? 'logos/'.fake()->uuid().'.png' : null,
            'favicon_path' => fake()->boolean(50) ? 'favicons/'.fake()->uuid().'.ico' : null,
            'primary_color' => fake()->hexColor(),
            'secondary_color' => fake()->hexColor(),
            'login_background_path' => fake()->boolean(30) ? 'backgrounds/'.fake()->uuid().'.jpg' : null,
            'email_sender_name' => fake()->company().' Support',
            'email_sender_email' => 'support@'.fake()->domainName(),
            'support_email' => 'help@'.fake()->domainName(),
            'support_phone' => fake()->phoneNumber(),
        ];
    }
}
