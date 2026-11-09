<?php

namespace Database\Seeders;

use App\Models\Domain;
use App\Models\Template;
use App\Models\Theme;
use App\Models\Website;
use App\Models\WebsiteDatabaseConnection;
use App\Models\WebsiteThemeSetting;
use Illuminate\Database\Seeder;

class VocabotSeeder extends Seeder
{
    /**
     * Run the database seeds to link Vocabot website, domain, template, and theme settings.
     */
    public function run(): void
    {
        // 1. Identify existing Vocabot website or create if not present (never duplicate)
        $website = Website::where('website_name', 'Vocabot')
            ->orWhere('domain', 'vocabot.ai')
            ->orWhere('domain', 'like', '%vocabot%')
            ->first();

        if (! $website) {
            $website = Website::create([
                'website_name' => 'Vocabot',
                'domain' => 'vocabot.ai',
                'description' => '24/7 AI Voice Receptionist for Business Call Answering and Scheduling.',
                'notes' => 'Codebase located at Vocabot/ with dedicated tenant database vocabot.',
                'provider' => 'local',
                'status' => 1,
                'provisioning_status' => 'provisioned',
            ]);
        }

        // 2. Link Primary Domain
        Domain::firstOrCreate(
            [
                'website_id' => $website->id,
                'domain_name' => $website->domain,
            ],
            [
                'is_primary' => true,
                'ssl_enabled' => true,
                'verified_at' => now(),
                'status' => 1,
            ]
        );

        // 3. Link Template
        $template = Template::firstOrCreate(
            ['slug' => 'vocabot'],
            [
                'template_name' => 'Vocabot AI Voice Agent',
                'view_path' => 'Vocabot',
                'description' => '24/7 AI Voice Receptionist and SaaS platform layout template',
                'preview_image' => 'images/logo2.webp',
                'version' => '1.0.0',
                'status' => 1,
            ]
        );

        $website->update(['template_id' => $template->id]);

        // 4. Link Theme
        $theme = Theme::firstOrCreate(
            ['slug' => 'vocabot-dark'],
            [
                'theme_name' => 'Vocabot Dark Theme',
                'description' => 'Premium dark SaaS voice agent theme with purple neon glow accents',
                'preview_image' => 'images/logo2.webp',
                'version' => '1.0.0',
                'status' => 1,
                'appearance' => [
                    'primary_color' => '#7c3aed',
                    'accent_color' => '#a855f7',
                    'background_color' => '#070313',
                    'text_color' => '#ffffff',
                    'font_family' => 'Plus Jakarta Sans',
                    'logo' => 'images/logo2.webp',
                    'button_style' => 'pill',
                ],
            ]
        );

        // 5. Store Theme Settings in database
        $themeSettings = [
            'active_theme' => 'active',
            'primary_color' => '#7c3aed',
            'accent_color' => '#a855f7',
            'background_color' => '#070313',
            'text_color' => '#ffffff',
            'font_family' => 'Plus Jakarta Sans',
            'logo' => 'images/logo2.webp',
            'button_style' => 'pill',
            'layout_mode' => 'boxed',
            'navbar_style' => 'sticky',
        ];

        foreach ($themeSettings as $key => $value) {
            WebsiteThemeSetting::updateOrCreate(
                [
                    'website_id' => $website->id,
                    'theme_id' => $theme->id,
                    'setting_key' => $key,
                ],
                [
                    'setting_value' => $value,
                ]
            );
        }

        // 6. Connect to Vocabot tenant database
        WebsiteDatabaseConnection::updateOrCreate(
            ['website_id' => $website->id],
            [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => 'vocabot',
                'username' => 'root',
                'password' => '',
            ]
        );
    }
}
