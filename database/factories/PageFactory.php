<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'website_id' => Website::factory(),
            'title' => rtrim($title, '.'),
            'slug' => Str::slug($title),
            'page_type' => fake()->randomElement(['standard', 'landing', 'blog', 'contact', 'about']),
            'is_homepage' => false,
            'status' => 1,
        ];
    }
}
