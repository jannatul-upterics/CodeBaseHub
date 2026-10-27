<?php

namespace Database\Factories;

use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Website>
 */
class WebsiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $providers = ['cloud', 'aws', 'forge', 'digitalocean', 'vercel'];

        return [
            'customer_id' => null,
            'website_name' => fake()->company().' Portal',
            'domain' => fake()->unique()->domainName(),
            'description' => fake()->paragraph(),
            'notes' => fake()->sentence(),
            'status' => 1,
            'provider' => fake()->randomElement($providers),
        ];
    }
}
