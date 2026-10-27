<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Domain;
use App\Models\Metadata;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Setting;
use App\Models\Template;
use App\Models\Theme;
use App\Models\Website;
use App\Models\WebsiteThemeSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WebsiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Templates
        $templates = [
            ['template_name' => 'Startup Showcase v2', 'slug' => 'startup-showcase-v2', 'description' => 'Modern high-conversion tech startup landing page', 'version' => '2.1.0'],
            ['template_name' => 'Storefront Pro', 'slug' => 'storefront-pro', 'description' => 'Full-featured direct-to-consumer eCommerce storefront', 'version' => '1.5.0'],
            ['template_name' => 'Creative Portfolio', 'slug' => 'creative-portfolio', 'description' => 'Interactive visual showcase for agencies and creators', 'version' => '3.0.0'],
            ['template_name' => 'B2B SaaS Minimal', 'slug' => 'b2b-saas-minimal', 'description' => 'Clean, typography-driven SaaS marketing site', 'version' => '1.2.0'],
            ['template_name' => 'Healthcare Plus', 'slug' => 'healthcare-plus', 'description' => 'Accessible clinic, hospital, and doctor portal', 'version' => '2.0.0'],
            ['template_name' => 'Dining & Hospitality', 'slug' => 'dining-hospitality', 'description' => 'Artisanal restaurant, bistro, and reservation layout', 'version' => '1.4.0'],
        ];

        foreach ($templates as $t) {
            Template::firstOrCreate(['slug' => $t['slug']], $t);
        }

        // 2. Seed Themes
        $themes = [
            ['theme_name' => 'Nordic Minimalist', 'slug' => 'nordic-minimalist', 'description' => 'Crisp monochrome with indigo highlights', 'version' => '1.0.0'],
            ['theme_name' => 'Midnight Slate', 'slug' => 'midnight-slate', 'description' => 'Dark sleek developer-centric theme', 'version' => '1.1.0'],
            ['theme_name' => 'Emerald Breeze', 'slug' => 'emerald-breeze', 'description' => 'Fresh organic tones tailored for health and eco products', 'version' => '1.0.0'],
            ['theme_name' => 'Cyberpunk Pulse', 'slug' => 'cyberpunk-pulse', 'description' => 'High-contrast vibrant theme with purple neon accents', 'version' => '1.3.0'],
        ];

        $createdThemes = [];
        foreach ($themes as $th) {
            $createdThemes[] = Theme::firstOrCreate(['slug' => $th['slug']], $th);
        }

        // 3. Seed Customers
        $customers = [
            ['name' => 'Acme Corporation', 'email' => 'tech@acmecorp.com', 'password' => Hash::make('password123')],
            ['name' => 'Apex Retail Group', 'email' => 'ops@apexmarket.com', 'password' => Hash::make('password123')],
            ['name' => 'Horizon Studio Ltd', 'email' => 'hello@horizondigital.agency', 'password' => Hash::make('password123')],
        ];

        $createdCustomers = [];
        foreach ($customers as $c) {
            $createdCustomers[] = Customer::firstOrCreate(['email' => $c['email']], $c);
        }

        // 4. Seed Websites
        $sites = [
            [
                'website_name' => 'TechSprint Portal',
                'domain' => 'techsprint.io',
                'description' => 'Flagship developer platform and technical documentation hub.',
                'notes' => 'Primary production deployment with cloud CDN enabled.',
                'provider' => 'cloud',
                'customer_id' => $createdCustomers[0]->id ?? null,
            ],
            [
                'website_name' => 'Apex eCommerce Store',
                'domain' => 'shop.apexmarket.com',
                'description' => 'Direct-to-consumer apparel and merchandise storefront.',
                'notes' => 'Integrated with stripe payments and webhook listeners.',
                'provider' => 'aws',
                'customer_id' => $createdCustomers[1]->id ?? null,
            ],
            [
                'website_name' => 'Horizon Digital Agency',
                'domain' => 'horizondigital.agency',
                'description' => 'Creative design agency showreel and interactive portfolio.',
                'notes' => 'Uses dynamic canvas animations and optimized asset compression.',
                'provider' => 'digitalocean',
                'customer_id' => $createdCustomers[2]->id ?? null,
            ],
            [
                'website_name' => 'Nova SaaS Landing',
                'domain' => 'novasaas.dev',
                'description' => 'High-converting lead generation landing page for analytics.',
                'notes' => 'A/B testing running across hero headlines.',
                'provider' => 'vercel',
                'customer_id' => $createdCustomers[0]->id ?? null,
            ],
            [
                'website_name' => 'Pulse Health Clinic',
                'domain' => 'pulsehealth.org',
                'description' => 'Patient consultation portal with appointment booking.',
                'notes' => 'HIPAA compliant contact routing implemented.',
                'provider' => 'cloud',
                'customer_id' => $createdCustomers[1]->id ?? null,
            ],
            [
                'website_name' => 'Urban Bistro & Cafe',
                'domain' => 'urbanbistro.net',
                'description' => 'Artisanal dining menu presentation and table reservations.',
                'notes' => 'Menu items updated weekly via CMS feed.',
                'provider' => 'forge',
                'customer_id' => $createdCustomers[2]->id ?? null,
            ],
        ];

        foreach ($sites as $idx => $siteData) {
            $website = Website::firstOrCreate(
                ['domain' => $siteData['domain']],
                $siteData
            );

            // Connect Primary Domain
            Domain::firstOrCreate(
                ['domain_name' => $website->domain],
                [
                    'website_id' => $website->id,
                    'is_primary' => true,
                    'ssl_enabled' => true,
                    'verified_at' => now(),
                    'status' => 1,
                ]
            );

            // Associate Theme
            if (! empty($createdThemes)) {
                $theme = $createdThemes[$idx % count($createdThemes)];
                WebsiteThemeSetting::firstOrCreate(
                    ['website_id' => $website->id, 'theme_id' => $theme->id, 'setting_key' => 'primary_color'],
                    ['setting_value' => '#4f46e5']
                );
            }

            // Seed Homepage Page
            $page = Page::firstOrCreate(
                ['website_id' => $website->id, 'slug' => 'home'],
                [
                    'title' => 'Home',
                    'page_type' => 'landing',
                    'is_homepage' => true,
                    'status' => 1,
                ]
            );

            // Seed Page Sections
            PageSection::firstOrCreate(
                ['page_id' => $page->id, 'section_name' => 'Hero Banner'],
                [
                    'section_type' => 'hero',
                    'content' => ['headline' => 'Welcome to '.$website->website_name, 'subheadline' => 'Crafted with CodeBaseHub.'],
                    'sort_order' => 1,
                    'status' => 1,
                ]
            );

            // Seed Metadata
            Metadata::firstOrCreate(
                ['website_id' => $website->id, 'page_id' => $page->id],
                [
                    'meta_title' => $website->website_name.' | Official Site',
                    'meta_description' => $website->description,
                    'canonical_url' => 'https://'.$website->domain,
                    'robots' => 'index, follow',
                ]
            );

            // Seed Setting
            Setting::firstOrCreate(
                ['website_id' => $website->id, 'setting_key' => 'analytics_id'],
                ['setting_value' => 'G-'.Str::upper(Str::random(8))]
            );
        }
    }
}
