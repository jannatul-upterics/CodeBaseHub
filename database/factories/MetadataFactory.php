<?php

namespace Database\Factories;

use App\Models\Metadata;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Metadata>
 */
class MetadataFactory extends Factory
{
    protected $model = Metadata::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'website_id' => Website::factory(),
            'page_id' => null,
            'meta_title' => fake()->sentence(4),
            'meta_description' => fake()->paragraph(2),
            'canonical_url' => fake()->url(),
            'robots' => 'index, follow',
            'og_image' => fake()->imageUrl(1200, 630),
        ];
    }
}
