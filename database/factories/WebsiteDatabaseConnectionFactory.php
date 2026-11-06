<?php

namespace Database\Factories;

use App\Models\Website;
use App\Models\WebsiteDatabaseConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebsiteDatabaseConnection>
 */
class WebsiteDatabaseConnectionFactory extends Factory
{
    protected $model = WebsiteDatabaseConnection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'website_id' => Website::factory(),
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'cbh_tenant_'.fake()->unique()->lexify('??????'),
            'username' => 'root',
            'password' => 'secret_db_pass',
        ];
    }
}
