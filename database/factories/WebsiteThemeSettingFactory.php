<?php

namespace Database\Factories;

use App\Models\Theme;
use App\Models\Website;
use App\Models\WebsiteThemeSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebsiteThemeSetting>
 */
class WebsiteThemeSettingFactory extends Factory
{
    protected $model = WebsiteThemeSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'website_id' => Website::factory(),
            'theme_id' => Theme::factory(),
            'setting_key' => fake()->slug(2),
            'setting_value' => fake()->word(),
        ];
    }
}
