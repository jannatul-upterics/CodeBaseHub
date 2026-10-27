<?php

namespace Database\Factories;

use App\Models\Domain;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Domain>
 */
class DomainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'website_id' => Website::factory(),
            'domain_name' => fake()->unique()->domainName(),
            'verified_at' => fake()->boolean(70) ? now() : null,
            'is_primary' => false,
            'ssl_enabled' => fake()->boolean(80),
            'status' => 1,
        ];
    }
}
