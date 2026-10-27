<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageSection>
 */
class PageSectionFactory extends Factory
{
    protected $model = PageSection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page_id' => Page::factory(),
            'section_name' => fake()->words(2, true),
            'section_type' => fake()->randomElement(['hero', 'features', 'text_block', 'cta', 'faq']),
            'content' => [
                'heading' => fake()->sentence(4),
                'body' => fake()->paragraph(),
            ],
            'sort_order' => fake()->numberBetween(0, 10),
            'status' => 1,
        ];
    }
}
