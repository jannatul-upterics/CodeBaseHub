<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->word().'.jpg';

        return [
            'website_id' => Website::factory(),
            'file_name' => $filename,
            'file_path' => 'media/'.$filename,
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_size' => fake()->numberBetween(1024, 2048000),
            'alt_text' => fake()->sentence(3),
            'status' => 1,
        ];
    }
}
