<?php

namespace Database\Factories;

use App\Models\Setting;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'website_id' => Website::factory(),
            'setting_key' => fake()->unique()->slug(2),
            'setting_value' => fake()->sentence(),
        ];
    }
}
