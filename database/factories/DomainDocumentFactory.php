<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DomainDocument>
 */
class DomainDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [DocumentType::IdentityProof, DocumentType::AddressProof, DocumentType::AuthorizationLetter, DocumentType::Other];
        
        return [
            'domain_id' => Domain::factory(),
            'document_type' => fake()->randomElement($types),
            'file_path' => 'documents/' . fake()->uuid() . '.pdf',
            'original_filename' => fake()->word() . '.pdf',
            'file_size' => fake()->numberBetween(10000, 5000000),
            'uploaded_by' => User::factory(),
            'verified_by' => null,
            'verified_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified_by' => User::factory(),
            'verified_at' => now(),
            'notes' => 'Document verified',
        ]);
    }
}
