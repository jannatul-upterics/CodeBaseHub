<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Metadata;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Template;
use App\Models\Theme;
use App\Models\Website;
use App\Models\WebsiteDatabaseConnection;
use App\Models\WebsiteThemeSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VocabotPageLibraryService
{
    /**
     * Get or create the master Vocabot template in CodeBaseHub.
     */
    public function ensureTemplate(): Template
    {
        return Template::firstOrCreate(
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
    }

    /**
     * Get or create the master Vocabot website in CodeBaseHub.
     */
    public function ensureWebsite(?Template $template = null): Website
    {
        $template = $template ?? $this->ensureTemplate();

        $website = Website::where('website_name', 'Vocabot')
            ->orWhere('domain', 'vocabots.com')
            ->orWhere('domain', 'like', '%vocabot%')
            ->first();

        if (! $website) {
            $website = Website::create([
                'website_name' => 'Vocabot',
                'domain' => 'vocabots.com',
                'template_id' => $template->id,
                'description' => '24/7 AI Voice Receptionist for Business Call Answering and Scheduling.',
                'notes' => 'Codebase located at Vocabot/ with dedicated tenant database vocabot.',
                'provider' => 'aws',
                'status' => 1,
                'provisioning_status' => 'provisioned',
            ]);
        } else {
            $website->update([
                'template_id' => $template->id,
                'provisioning_status' => 'provisioned',
            ]);
        }

        // Link primary and recognized domains
        $domains = array_unique(array_filter([$website->domain, 'vocabots.com', 'vocabot.ai', '127.0.0.1', 'localhost']));
        foreach ($domains as $dName) {
            Domain::firstOrCreate(
                [
                    'website_id' => $website->id,
                    'domain_name' => $dName,
                ],
                [
                    'is_primary' => ($dName === $website->domain),
                    'ssl_enabled' => true,
                    'verified_at' => now(),
                    'status' => 1,
                ]
            );
        }

        // Ensure dark theme
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

        WebsiteThemeSetting::updateOrCreate(
            [
                'website_id' => $website->id,
                'theme_id' => $theme->id,
                'setting_key' => 'active_theme',
            ],
            ['setting_value' => 'active']
        );

        // Ensure database connection (in non-testing environments or if not already set)
        if (! $website->databaseConnection && ! app()->environment('testing')) {
            WebsiteDatabaseConnection::create([
                'website_id' => $website->id,
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => 'vocabot',
                'username' => 'root',
                'password' => '',
            ]);
        }

        return $website;
    }

    /**
     * Return all 7 original Vocabot page definitions, sections, and SEO metadata.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPageDefinitions(): array
    {
        return [
            // Page 1: Homepage
            [
                'title' => 'Vocabot | 24/7 AI Voice Receptionist for Inbound Calls & Bookings',
                'slug' => 'home',
                'page_type' => 'landing',
                'is_homepage' => true,
                'sort_order' => 1,
                'status' => 1,
                'content' => 'Vocabot is an AI voice receptionist that answers business phone calls 24/7, handles reservations and bookings, answers guest FAQs, and transfers complex calls.',
                'meta_title' => 'Vocabot | 24/7 AI Voice Receptionist for Inbound Calls & Bookings',
                'meta_description' => 'Vocabot is an AI voice receptionist that answers business phone calls 24/7, handles reservations and bookings, answers guest FAQs, and transfers complex calls.',
                'canonical_url' => '/',
                'og_image' => 'images/logo2.webp',
                'sections' => [
                    [
                        'section_name' => 'hero',
                        'section_type' => 'hero',
                        'sort_order' => 1,
                        'status' => 1,
                        'content' => [
                            'eyebrow' => 'AI Voice Agents • Available 24/7',
                            'heading' => 'Turn Every Call Into an Opportunity.',
                            'subheading' => 'Vocabot is the 24/7 AI voice receptionist that answers every call in seconds, books reservations, answers questions, and never lets a customer slip away.',
                            'primary_cta_text' => 'Try Vocabot Live',
                            'primary_cta_url' => '#demo',
                            'secondary_cta_text' => 'Book a Demo',
                            'secondary_cta_url' => '#contact',
                            'card_title' => 'Live Call Simulator',
                            'audio_src' => '/demo.mp3',
                        ],
                    ],
                    [
                        'section_name' => 'trust-strip',
                        'section_type' => 'trust-strip',
                        'sort_order' => 2,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Powering phone operations for modern multi-location & independent brands',
                            'brands' => ['The Grill Room', 'Artisan Kitchen', 'Omakase Bar', 'Bluefin Bistro', 'Bella Vista'],
                        ],
                    ],
                    [
                        'section_name' => 'problem',
                        'section_type' => 'features',
                        'sort_order' => 3,
                        'status' => 1,
                        'content' => [
                            'tag' => 'The Missed Call Crisis',
                            'heading' => 'The Hidden Cost of Missed Calls',
                            'subheading' => 'In high-volume businesses, an unanswered call isn\'t just an inconvenience — it\'s lost revenue, frustrated customers, and damaged reputation.',
                            'stats' => [
                                [
                                    'value' => '76%',
                                    'label' => 'Callers hang up on voicemail',
                                    'detail' => '76% of callers who reach voicemail hang up and call a competitor instead of leaving a message.',
                                ],
                                [
                                    'value' => '$4,200',
                                    'unit' => '/mo',
                                    'label' => 'Lost reservation revenue',
                                    'detail' => 'Average monthly revenue lost by restaurants and clinics due to unanswered peak-hour calls.',
                                ],
                                [
                                    'value' => '3.5 hrs',
                                    'unit' => '/day',
                                    'label' => 'Staff phone interruption',
                                    'detail' => 'Time frontline team members spend answering repetitive questions rather than serving guests in-person.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'section_name' => 'product',
                        'section_type' => 'features',
                        'sort_order' => 4,
                        'status' => 1,
                        'content' => [
                            'tag' => 'Core Capabilities',
                            'heading' => 'Built for High-Call Volume Businesses',
                            'subheading' => 'Vocabot operates like your most experienced front-desk team member — fast, knowledgeable, polite, and always on duty.',
                            'features' => [
                                [
                                    'title' => 'AI Reservation Assistant',
                                    'description' => 'Conversational booking management that checks live availability, records guest party sizes, notes dietary preferences, and confirms instantly.',
                                ],
                                [
                                    'title' => 'Intelligent FAQ Engine',
                                    'description' => 'Instantly answers questions about opening hours, parking, dress codes, directions, menu items, and private dining policies.',
                                ],
                                [
                                    'title' => 'Smart Call Escalation',
                                    'description' => 'Detects VIP guests, complex catering inquiries, or urgent situations and warm-transfers the caller directly to human staff.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'section_name' => 'how-it-works',
                        'section_type' => 'steps',
                        'sort_order' => 5,
                        'status' => 1,
                        'content' => [
                            'tag' => 'Simple 3-Step Setup',
                            'heading' => 'How Vocabot Works',
                            'subheading' => 'No new phone systems. No complex hardware. Vocabot seamlessly connects to your existing business line in under 15 minutes.',
                            'steps' => [
                                [
                                    'step' => '01',
                                    'title' => 'Connect Your Number',
                                    'description' => 'Set up conditional call forwarding from your current telecom provider or VoIP phone system.',
                                ],
                                [
                                    'step' => '02',
                                    'title' => 'Train Your AI Agent',
                                    'description' => 'Provide your business details, FAQs, reservation link, and staff escalation rules via our simple setup portal.',
                                ],
                                [
                                    'step' => '03',
                                    'title' => 'Go Live & Scale',
                                    'description' => 'Vocabot answers inbound calls immediately, handles peak rushes, and synchronizes booking data in real-time.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'section_name' => 'demo',
                        'section_type' => 'interactive',
                        'sort_order' => 6,
                        'status' => 1,
                        'content' => [
                            'tag' => 'Live Simulation',
                            'heading' => 'Experience the Difference',
                            'subheading' => 'Listen to how naturally Vocabot interacts with customers across different scenarios.',
                            'audio_src' => '/demo.mp3',
                        ],
                    ],
                    [
                        'section_name' => 'showcase',
                        'section_type' => 'slider',
                        'sort_order' => 7,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Designed to Feel Natural',
                            'subheading' => 'Sub-second conversational response latency powered by cutting-edge real-time voice intelligence.',
                        ],
                    ],
                    [
                        'section_name' => 'sectors',
                        'section_type' => 'industries',
                        'sort_order' => 8,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Tailored for Your Industry',
                            'subheading' => 'Specialized workflows crafted for high-touch service industries.',
                            'sectors' => ['Restaurants & Hospitality', 'Medical & Dental Clinics', 'Salons, Spas & Wellness', 'Home & Field Services'],
                        ],
                    ],
                    [
                        'section_name' => 'dashboard',
                        'section_type' => 'preview',
                        'sort_order' => 9,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Complete Visibility & Control',
                            'subheading' => 'Every call is transcribed, logged, and analyzed with sentiment and caller intent breakdown.',
                        ],
                    ],
                    [
                        'section_name' => 'integrations',
                        'section_type' => 'integrations',
                        'sort_order' => 10,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Works With the Tools You Already Use',
                            'subheading' => 'Bi-directional integrations with leading booking, POS, calendar, and CRM platforms.',
                            'integrations' => ['OpenTable', 'Resy', 'Google Calendar', 'SevenRooms', 'Toast', 'Square', 'Zapier', 'Webhooks'],
                        ],
                    ],
                    [
                        'section_name' => 'roi',
                        'section_type' => 'stats',
                        'sort_order' => 11,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Real ROI From Day One',
                            'subheading' => 'Quantifiable impact delivered across every telephone interaction.',
                            'metrics' => [
                                ['metric' => '99.4%', 'label' => 'Answer Rate'],
                                ['metric' => '<2s', 'label' => 'Answer Speed'],
                                ['metric' => '+38%', 'label' => 'More Bookings Captured'],
                                ['metric' => '100%', 'label' => '24/7 Availability'],
                            ],
                        ],
                    ],
                    [
                        'section_name' => 'faq',
                        'section_type' => 'faq',
                        'sort_order' => 12,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Frequently Asked Questions',
                            'items' => [
                                [
                                    'question' => 'What can Vocabot do?',
                                    'answer' => 'Vocabot is an AI voice receptionist that answers inbound phone calls 24/7. It understands customer intent, manages reservations and bookings, answers business FAQs, captures caller information, and transfers calls to your team when human assistance is required.',
                                ],
                                [
                                    'question' => 'Who is Vocabot built for?',
                                    'answer' => 'Vocabot is designed for customer-facing businesses where missed phone calls result in lost revenue. It provides specialized out-of-the-box workflows for restaurants, hospitality, and appointment-driven services, while adapting easily to any business with high inbound call volumes.',
                                ],
                                [
                                    'question' => 'Can Vocabot transfer calls to a human team member?',
                                    'answer' => 'Yes. You can define custom escalation rules so that complex requests, VIP callers, special events, or sensitive situations are transferred directly to your staff.',
                                ],
                                [
                                    'question' => 'How does Vocabot connect to existing booking tools?',
                                    'answer' => 'Vocabot integrates with calendars, reservation platforms, and custom business tools via webhooks and APIs to verify live availability and record bookings automatically.',
                                ],
                                [
                                    'question' => 'Does Vocabot handle calls outside business hours?',
                                    'answer' => 'Yes. Vocabot operates continuously 24/7/365, allowing your business to take table bookings and answer guest questions late at night, before opening, or during peak rush hours.',
                                ],
                                [
                                    'question' => 'How do we get started with setup?',
                                    'answer' => 'We work with your team to configure your business rules, hours, frequently asked questions, and booking parameters. Once tested, call forwarding is enabled and your AI voice agent goes live.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'section_name' => 'contact',
                        'section_type' => 'contact',
                        'sort_order' => 13,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Ready to Never Miss Another Call?',
                            'subheading' => 'Request a live personalized demo or speak to our voice automation specialists.',
                            'button_text' => 'Get Started with Vocabot',
                            'button_url' => '/contact',
                        ],
                    ],
                ],
            ],

            // Page 2: Product
            [
                'title' => 'Vocabot Platform | AI Voice Receptionist & Automation Engine',
                'slug' => 'product',
                'page_type' => 'standard',
                'is_homepage' => false,
                'sort_order' => 2,
                'status' => 1,
                'content' => 'Explore the Vocabot platform: real-time conversational AI, smart booking automation, CRM & calendar sync, and intelligent human escalation.',
                'meta_title' => 'Vocabot Platform | AI Voice Receptionist & Automation Engine',
                'meta_description' => 'Explore the Vocabot platform: real-time conversational AI, smart booking automation, CRM & calendar sync, and intelligent human escalation.',
                'canonical_url' => '/product',
                'og_image' => 'images/logo2.webp',
                'sections' => [
                    [
                        'section_name' => 'hero',
                        'section_type' => 'hero',
                        'sort_order' => 1,
                        'status' => 1,
                        'content' => [
                            'eyebrow' => 'The Complete Voice AI Platform',
                            'heading' => 'Turn Every Phone Call Into a Completed Transaction.',
                            'subheading' => 'An enterprise-grade voice intelligence engine engineered specifically for inbound call resolution, booking execution, and flawless customer service.',
                            'cta_primary' => 'Schedule a Demo',
                            'cta_url' => '/contact',
                        ],
                    ],
                    [
                        'section_name' => 'marquee',
                        'section_type' => 'marquee',
                        'sort_order' => 2,
                        'status' => 1,
                        'content' => [
                            'items' => [
                                'Real-Time Voice Intelligence',
                                'Zero Hold Times',
                                'Multi-Language Support',
                                'Automated Calendar Sync',
                                'Human Escalation Engine',
                                'POS & Table Booking Connectors',
                            ],
                        ],
                    ],
                    [
                        'section_name' => 'command',
                        'section_type' => 'features',
                        'sort_order' => 3,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Product Command Center',
                            'subheading' => 'Granular control over AI personality, knowledge base, operational parameters, and escalation protocols.',
                        ],
                    ],
                    [
                        'section_name' => 'solutions',
                        'section_type' => 'features',
                        'sort_order' => 4,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Jobs Vocabot Handles For You',
                            'subheading' => 'Automate high-frequency routine calls so your staff can focus 100% on serving in-person guests.',
                            'jobs' => [
                                'Table Reservations & Party Booking',
                                'Directions, Hours & Parking Inquiries',
                                'Menu Details & Dietary Accommodation',
                                'Curbside Pickup & Order Status Inquiries',
                            ],
                        ],
                    ],
                    [
                        'section_name' => 'conversation',
                        'section_type' => 'interactive',
                        'sort_order' => 5,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Natural Conversations, Not Robot Menus',
                            'subheading' => 'Say goodbye to "Press 1 for hours". Vocabot speaks naturally, handles interruptions gracefully, and understands nuanced human speech.',
                        ],
                    ],
                    [
                        'section_name' => 'workflow',
                        'section_type' => 'steps',
                        'sort_order' => 6,
                        'status' => 1,
                        'content' => [
                            'heading' => 'How Vocabot Orchestrates Inbound Calls',
                            'subheading' => 'From caller greeting to database confirmation in milliseconds.',
                        ],
                    ],
                    [
                        'section_name' => 'value',
                        'section_type' => 'stats',
                        'sort_order' => 7,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Measurable Business Value',
                            'subheading' => 'Direct contribution to top-line revenue and bottom-line efficiency.',
                        ],
                    ],
                    [
                        'section_name' => 'faq',
                        'section_type' => 'faq',
                        'sort_order' => 8,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Platform FAQs',
                            'items' => [
                                [
                                    'question' => 'How quickly does Vocabot respond to callers?',
                                    'answer' => 'Vocabot delivers sub-second conversational latency, creating a natural conversation flow that feels just like talking with an attentive human concierge.',
                                ],
                                [
                                    'question' => 'Can Vocabot handle accents and background noise?',
                                    'answer' => 'Yes. Built with state-of-the-art neural speech recognition, Vocabot filters background chatter and accurately comprehends regional accents and colloquial phrasing.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'section_name' => 'contact',
                        'section_type' => 'contact',
                        'sort_order' => 9,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Ready to Upgrade Your Phone Operations?',
                            'button_text' => 'Speak With an AI Specialist',
                            'button_url' => '/contact',
                        ],
                    ],
                ],
            ],

            // Page 3: How It Works
            [
                'title' => 'How Vocabot Works | AI Voice Receptionist Workflow',
                'slug' => 'how-it-works',
                'page_type' => 'standard',
                'is_homepage' => false,
                'sort_order' => 3,
                'status' => 1,
                'content' => 'Learn how Vocabot answers calls in under 2 seconds, understands customer intents, completes reservations, and connects with your staff.',
                'meta_title' => 'How Vocabot Works | AI Voice Receptionist Workflow',
                'meta_description' => 'Learn how Vocabot answers calls in under 2 seconds, understands customer intents, completes reservations, and connects with your staff.',
                'canonical_url' => '/how-it-works',
                'og_image' => 'images/logo2.webp',
                'sections' => [
                    [
                        'section_name' => 'hero',
                        'section_type' => 'hero',
                        'sort_order' => 1,
                        'status' => 1,
                        'content' => [
                            'eyebrow' => 'Automated Caller Workflow',
                            'heading' => 'From First Ring to Completed Booking in Seconds.',
                            'subheading' => 'Experience the seamless journey callers take when contacting your business powered by Vocabot voice intelligence.',
                        ],
                    ],
                    [
                        'section_name' => 'journey',
                        'section_type' => 'steps',
                        'sort_order' => 2,
                        'status' => 1,
                        'content' => [
                            'heading' => 'The 4-Step Caller Journey',
                            'steps' => [
                                ['step' => '01', 'title' => 'Instant Ring & Greet', 'description' => 'Within 2 rings, Vocabot answers with your brand-customized warm greeting.'],
                                ['step' => '02', 'title' => 'Intent Comprehension', 'description' => 'Understands reservations, inquiries, or transfers without rigid phone tree prompts.'],
                                ['step' => '03', 'title' => 'Real-Time Action', 'description' => 'Checks availability, writes booking to your calendar or POS, and answers complex questions.'],
                                ['step' => '04', 'title' => 'Confirmation & Sync', 'description' => 'Sends SMS confirmation to the caller and notifies your staff in real-time.'],
                            ],
                        ],
                    ],
                    [
                        'section_name' => 'conversation',
                        'section_type' => 'interactive',
                        'sort_order' => 3,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Live Conversation Flow Simulation',
                            'subheading' => 'Listen to a simulated call from start to finish.',
                        ],
                    ],
                    [
                        'section_name' => 'connection',
                        'section_type' => 'features',
                        'sort_order' => 4,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Zero Equipment Required',
                            'subheading' => 'Setup takes minutes using your existing telecom or VoIP line with simple call forwarding.',
                        ],
                    ],
                    [
                        'section_name' => 'benefits',
                        'section_type' => 'features',
                        'sort_order' => 5,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Why Modern Businesses Choose Vocabot',
                            'subheading' => 'Reliable, always-on voice reception that never takes a day off or misses an opportunity.',
                        ],
                    ],
                    [
                        'section_name' => 'cta',
                        'section_type' => 'contact',
                        'sort_order' => 6,
                        'status' => 1,
                        'content' => [
                            'heading' => 'See How It Works on Your Own Line',
                            'button_text' => 'Get Started Today',
                            'button_url' => '/contact',
                        ],
                    ],
                ],
            ],

            // Page 4: Solutions
            [
                'title' => 'AI Voice Solutions | Restaurant & Business Call Automation',
                'slug' => 'solutions',
                'page_type' => 'standard',
                'is_homepage' => false,
                'sort_order' => 4,
                'status' => 1,
                'content' => 'Discover Vocabot solutions tailored for restaurants, dental & medical clinics, salons, automotive, and multi-location businesses.',
                'meta_title' => 'AI Voice Solutions | Restaurant & Business Call Automation',
                'meta_description' => 'Discover Vocabot solutions tailored for restaurants, dental & medical clinics, salons, automotive, and multi-location businesses.',
                'canonical_url' => '/solutions',
                'og_image' => 'images/logo2.webp',
                'sections' => [
                    [
                        'section_name' => 'hero',
                        'section_type' => 'hero',
                        'sort_order' => 1,
                        'status' => 1,
                        'content' => [
                            'eyebrow' => 'Industry-Tailored AI Voice Solutions',
                            'heading' => 'Engineered for High-Call Inbound Industries.',
                            'subheading' => 'Every industry has unique terminology, workflows, and customer expectations. Vocabot comes pre-trained for your specific sector.',
                        ],
                    ],
                    [
                        'section_name' => 'use-cases',
                        'section_type' => 'industries',
                        'sort_order' => 2,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Specialized Sector Packages',
                            'items' => [
                                [
                                    'sector' => 'Restaurants & Dining',
                                    'features' => ['Table reservations', 'Peak rush offloading', 'Dietary inquiries', 'Directions & parking'],
                                ],
                                [
                                    'sector' => 'Dental & Healthcare Clinics',
                                    'features' => ['Patient consultation booking', 'Insurance FAQ answers', 'After-hours inquiries', 'Doctor scheduling'],
                                ],
                                [
                                    'sector' => 'Salons, Spas & Wellness',
                                    'features' => ['Service appointments', 'Staff availability', 'Treatment pricing', 'Rescheduling & cancellations'],
                                ],
                                [
                                    'sector' => 'Home & Field Services',
                                    'features' => ['Urgent dispatch screening', 'Service quotes', 'On-call escalation', 'Address qualification'],
                                ],
                            ],
                        ],
                    ],
                    [
                        'section_name' => 'workflow',
                        'section_type' => 'steps',
                        'sort_order' => 3,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Industry-Specific AI Logic & Workflows',
                            'subheading' => 'Pre-configured rules and integrations ready to deploy out-of-the-box.',
                        ],
                    ],
                    [
                        'section_name' => 'benefits',
                        'section_type' => 'features',
                        'sort_order' => 4,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Enterprise-Grade Reliability for Every Business Size',
                            'subheading' => 'Scales effortlessly whether you operate one boutique location or manage a nationwide franchise network.',
                        ],
                    ],
                    [
                        'section_name' => 'final-cta',
                        'section_type' => 'contact',
                        'sort_order' => 5,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Find the Right Solution for Your Business',
                            'button_text' => 'Request Industry Demo',
                            'button_url' => '/contact',
                        ],
                    ],
                ],
            ],

            // Page 5: Integrations
            [
                'title' => 'Integrations | Vocabot Connects Your Business Tools',
                'slug' => 'integrations',
                'page_type' => 'standard',
                'is_homepage' => false,
                'sort_order' => 5,
                'status' => 1,
                'content' => 'Vocabot integrates seamlessly with OpenTable, Resy, Google Calendar, Toast POS, Square, Zapier, and custom webhooks.',
                'meta_title' => 'Integrations | Vocabot Connects Your Business Tools',
                'meta_description' => 'Vocabot integrates seamlessly with OpenTable, Resy, Google Calendar, Toast POS, Square, Zapier, and custom webhooks.',
                'canonical_url' => '/integrations',
                'og_image' => 'images/logo2.webp',
                'sections' => [
                    [
                        'section_name' => 'hero',
                        'section_type' => 'hero',
                        'sort_order' => 1,
                        'status' => 1,
                        'content' => [
                            'eyebrow' => 'Connected Ecosystem',
                            'heading' => 'Connects Seamlessly With Your Existing Tech Stack.',
                            'subheading' => 'Vocabot syncs with your reservation platforms, POS systems, digital calendars, and CRMs without disrupting your current operations.',
                        ],
                    ],
                    [
                        'section_name' => 'stack-strip',
                        'section_type' => 'features',
                        'sort_order' => 2,
                        'status' => 1,
                        'content' => [
                            'categories' => ['Reservation Platforms', 'Point of Sale & Hospitality', 'Digital Calendars', 'CRMs & Automation'],
                        ],
                    ],
                    [
                        'section_name' => 'integrations-grid',
                        'section_type' => 'integrations',
                        'sort_order' => 3,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Supported Platforms',
                            'items' => [
                                ['name' => 'OpenTable', 'category' => 'Reservations', 'description' => 'Live table availability checks and automated guest booking creation.'],
                                ['name' => 'Resy', 'category' => 'Reservations', 'description' => 'Direct reservation management for premier dining establishments.'],
                                ['name' => 'Google Calendar', 'category' => 'Calendars', 'description' => 'Bi-directional schedule lookups and appointment slot holds.'],
                                ['name' => 'SevenRooms', 'category' => 'Hospitality', 'description' => 'Guest CRM profile lookup and direct reservation dispatch.'],
                                ['name' => 'Toast POS', 'category' => 'Point of Sale', 'description' => 'Menu lookup and order verification integration.'],
                                ['name' => 'Square', 'category' => 'Point of Sale', 'description' => 'Appointments and catalog synchronization.'],
                                ['name' => 'Zapier', 'category' => 'Automation', 'description' => 'Connect Vocabot call events to 5,000+ business applications.'],
                                ['name' => 'Custom Webhooks', 'category' => 'Developer', 'description' => 'POST real-time call transcripts, metadata, and booking payloads to any endpoint.'],
                            ],
                        ],
                    ],
                    [
                        'section_name' => 'api',
                        'section_type' => 'features',
                        'sort_order' => 4,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Developer API & Custom Webhooks',
                            'subheading' => 'Build bespoke voice automations with our modern REST API and webhook events.',
                        ],
                    ],
                    [
                        'section_name' => 'workflow',
                        'section_type' => 'steps',
                        'sort_order' => 5,
                        'status' => 1,
                        'content' => [
                            'heading' => 'How Integrations Power Automated Calls',
                            'subheading' => 'Every phone inquiry triggers instant background lookups, providing callers with live, accurate answers.',
                        ],
                    ],
                    [
                        'section_name' => 'cta',
                        'section_type' => 'contact',
                        'sort_order' => 6,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Need a Custom Integration?',
                            'subheading' => 'Our engineering team can build dedicated connectors for proprietary ERP or booking systems.',
                            'button_text' => 'Talk to an Integration Engineer',
                            'button_url' => '/contact',
                        ],
                    ],
                ],
            ],

            // Page 6: Contact
            [
                'title' => 'Contact Us | Vocabot AI Receptionist',
                'slug' => 'contact',
                'page_type' => 'contact',
                'is_homepage' => false,
                'sort_order' => 6,
                'status' => 1,
                'content' => 'Get in touch with the Vocabot team to request a demo, discuss custom integrations, or get support.',
                'meta_title' => 'Contact Us | Vocabot AI Receptionist',
                'meta_description' => 'Get in touch with the Vocabot team to request a demo, discuss custom integrations, or get support.',
                'canonical_url' => '/contact',
                'og_image' => 'images/logo2.webp',
                'sections' => [
                    [
                        'section_name' => 'hero',
                        'section_type' => 'hero',
                        'sort_order' => 1,
                        'status' => 1,
                        'content' => [
                            'eyebrow' => 'Get In Touch',
                            'heading' => 'We\'d Love to Hear From You.',
                            'subheading' => 'Have questions about Vocabot? Ready to set up your AI voice receptionist? Our team is here to assist.',
                        ],
                    ],
                    [
                        'section_name' => 'contact-details',
                        'section_type' => 'features',
                        'sort_order' => 2,
                        'status' => 1,
                        'content' => [
                            'channels' => [
                                ['label' => 'Email Us', 'value' => 'contact@vocabots.com', 'url' => 'mailto:contact@vocabots.com'],
                                ['label' => 'Live Demo Phone Line', 'value' => '+1 (800) 555-VOCABOT', 'url' => 'tel:+18005558622'],
                                ['label' => 'Hours of Operation', 'value' => 'Support: Mon-Fri 8am-8pm EST (AI Voice: 24/7/365)'],
                                ['label' => 'Headquarters', 'value' => 'San Francisco, CA & New York, NY'],
                            ],
                        ],
                    ],
                    [
                        'section_name' => 'contact-form',
                        'section_type' => 'contact',
                        'sort_order' => 3,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Send Us a Message',
                            'subheading' => 'Fill out the form below and an AI specialist will reach out within 1 business hour.',
                            'fields' => ['full_name', 'email', 'phone', 'business_name', 'industry', 'message'],
                            'submit_text' => 'Send Inquiry',
                        ],
                    ],
                    [
                        'section_name' => 'demo-cta',
                        'section_type' => 'interactive',
                        'sort_order' => 4,
                        'status' => 1,
                        'content' => [
                            'heading' => 'Prefer to Hear Vocabot in Action Right Now?',
                            'subheading' => 'Call our live demonstration line and test our conversational voice intelligence for yourself.',
                            'phone_number' => '+1 (800) 555-VOCABOT',
                        ],
                    ],
                ],
            ],

            // Page 7: Privacy Policy
            [
                'title' => 'Privacy Policy & Terms of Service | Vocabot',
                'slug' => 'policy',
                'page_type' => 'standard',
                'is_homepage' => false,
                'sort_order' => 7,
                'status' => 1,
                'content' => 'Vocabot privacy policy, terms of service, data processing agreements, and security compliance.',
                'meta_title' => 'Privacy Policy & Terms of Service | Vocabot',
                'meta_description' => 'Vocabot privacy policy, terms of service, data processing agreements, and security compliance.',
                'canonical_url' => '/policy',
                'og_image' => 'images/logo2.webp',
                'sections' => [
                    [
                        'section_name' => 'hero',
                        'section_type' => 'hero',
                        'sort_order' => 1,
                        'status' => 1,
                        'content' => [
                            'eyebrow' => 'Legal & Compliance',
                            'heading' => 'Privacy Policy & Terms of Service',
                            'subheading' => 'Transparency, privacy protection, and enterprise security are at the core of everything we build.',
                            'last_updated' => 'November 2026',
                        ],
                    ],
                    [
                        'section_name' => 'policy-nav',
                        'section_type' => 'navigation',
                        'sort_order' => 2,
                        'status' => 1,
                        'content' => [
                            'items' => [
                                ['anchor' => '#collection', 'title' => '1. Information We Collect'],
                                ['anchor' => '#usage', 'title' => '2. How We Use Information'],
                                ['anchor' => '#voice-data', 'title' => '3. Call Recordings & Voice Audio'],
                                ['anchor' => '#retention', 'title' => '4. Data Retention & Security'],
                                ['anchor' => '#rights', 'title' => '5. Your Rights & GDPR/CCPA'],
                                ['anchor' => '#contact', 'title' => '6. Contact & Legal Requests'],
                            ],
                        ],
                    ],
                    [
                        'section_name' => 'policy-content',
                        'section_type' => 'content',
                        'sort_order' => 3,
                        'status' => 1,
                        'content' => [
                            'summary' => 'We treat all caller audio, transcripts, and booking information with strict end-to-end encryption in transit and at rest. We never sell voice recordings or customer data to third parties.',
                            'sections' => [
                                [
                                    'title' => '1. Information We Collect',
                                    'body' => 'When callers phone a business powered by Vocabot, we process audio streams in real-time solely to interpret caller intent, fulfill requests, and provide customer support records to the business owner.',
                                ],
                                [
                                    'title' => '2. How We Use Information',
                                    'body' => 'Information captured (such as names, party sizes, reservation times, and contact phone numbers) is transferred directly into your designated reservation system, POS, or CRM.',
                                ],
                                [
                                    'title' => '3. Call Recordings & Voice Data Security',
                                    'body' => 'Call audio is encrypted with AES-256 at rest and TLS 1.3 in transit. Access is strictly controlled through authenticated business dashboard credentials.',
                                ],
                                [
                                    'title' => '4. Data Retention & Deletion',
                                    'body' => 'Business owners can configure custom data retention windows from 30 days to indefinite storage, or request full data purging at any time.',
                                ],
                                [
                                    'title' => '5. Your Rights & Regulatory Compliance',
                                    'body' => 'Vocabot complies fully with GDPR, CCPA, and applicable state telecom recording notification regulations.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Import all Vocabot pages and sections into the CodeBaseHub central database.
     *
     * @param Website|null $website If omitted, ensures the Vocabot website and template are linked.
     * @return array{imported_pages: int, total_sections: int, pages: array<Page>}
     */
    public function importToCentralDatabase(?Website $website = null): array
    {
        $template = $this->ensureTemplate();
        $website = $website ?? $this->ensureWebsite($template);
        $definitions = $this->getPageDefinitions();

        // 1. Create or update pure library master blueprint copies (website_id = NULL) first
        $blueprints = [];
        foreach ($definitions as $pageData) {
            $sections = $pageData['sections'] ?? [];
            unset($pageData['sections']);

            $templateBlueprint = Page::updateOrCreate(
                [
                    'website_id' => null,
                    'template_id' => $template->id,
                    'slug' => $pageData['slug'],
                ],
                [
                    'title' => $pageData['title'],
                    'page_type' => $pageData['page_type'],
                    'is_homepage' => (bool) ($pageData['is_homepage'] ?? false),
                    'is_template' => true,
                    'status' => (int) ($pageData['status'] ?? 1),
                    'content' => $pageData['content'] ?? null,
                    'meta_title' => $pageData['meta_title'] ?? null,
                    'meta_description' => $pageData['meta_description'] ?? null,
                    'canonical_url' => $pageData['canonical_url'] ?? null,
                    'og_image' => $pageData['og_image'] ?? null,
                    'sections_data' => $sections,
                    'sort_order' => (int) ($pageData['sort_order'] ?? 0),
                    'is_indexable' => true,
                ]
            );

            $blueprintSectionNames = [];
            foreach ($sections as $sec) {
                $blueprintSectionNames[] = $sec['section_name'];
                PageSection::updateOrCreate(
                    [
                        'page_id' => $templateBlueprint->id,
                        'section_name' => $sec['section_name'],
                    ],
                    [
                        'section_type' => $sec['section_type'],
                        'content' => $sec['content'],
                        'sort_order' => (int) ($sec['sort_order'] ?? 0),
                        'status' => (int) ($sec['status'] ?? 1),
                    ]
                );
            }
            PageSection::where('page_id', $templateBlueprint->id)
                ->whereNotIn('section_name', $blueprintSectionNames)
                ->delete();

            $blueprints[$pageData['slug']] = $templateBlueprint;
        }

        $importedPages = [];
        $totalSections = 0;

        // 2. Create or update website-specific derived pages linked to master blueprint pages
        foreach ($definitions as $pageData) {
            $sections = $pageData['sections'] ?? [];
            unset($pageData['sections']);

            $blueprint = $blueprints[$pageData['slug']] ?? null;

            // Calculate sync hash
            $syncHash = sha1(json_encode([
                'slug' => $pageData['slug'],
                'title' => $pageData['title'],
                'content' => $pageData['content'],
                'meta_title' => $pageData['meta_title'],
                'meta_description' => $pageData['meta_description'],
                'sections' => $sections,
            ]));

            // Locate existing derived page for this website using master_page_id
            $existing = null;
            if ($blueprint) {
                $existing = Page::where('website_id', $website->id)
                    ->where('master_page_id', $blueprint->id)
                    ->first();
            }

            if (! $existing) {
                // If this is the master Vocabot website itself, also check by slug if master_page_id was unset
                $isVocabotSite = strtolower($website->website_name) === 'vocabot' || str_contains(strtolower($website->domain), 'vocabot');
                if ($isVocabotSite) {
                    $existing = Page::where('website_id', $website->id)
                        ->where('slug', $pageData['slug'])
                        ->first();

                    if ($existing && $blueprint && ! $existing->master_page_id) {
                        $existing->update(['master_page_id' => $blueprint->id]);
                    }
                }
            }

            if ($existing) {
                // Preserve user customizations on existing derived page!
                $page = $existing;

                // Only import sections if none exist
                if ($page->sections()->count() === 0) {
                    foreach ($sections as $sec) {
                        PageSection::create([
                            'page_id' => $page->id,
                            'section_name' => $sec['section_name'],
                            'section_type' => $sec['section_type'],
                            'content' => $sec['content'],
                            'sort_order' => (int) ($sec['sort_order'] ?? 0),
                            'status' => (int) ($sec['status'] ?? 1),
                        ]);
                        $totalSections++;
                    }
                } else {
                    $totalSections += $page->sections()->count();
                }
            } else {
                // If homepage, ensure exclusivity within this website
                if (! empty($pageData['is_homepage'])) {
                    Page::where('website_id', $website->id)
                        ->where('is_homepage', true)
                        ->update(['is_homepage' => false]);
                }

                // Create new derived page in central database
                $page = Page::create([
                    'website_id' => $website->id,
                    'master_page_id' => $blueprint?->id,
                    'template_id' => $template->id,
                    'slug' => $pageData['slug'],
                    'title' => $pageData['title'],
                    'page_type' => $pageData['page_type'],
                    'is_homepage' => (bool) ($pageData['is_homepage'] ?? false),
                    'is_template' => true,
                    'status' => (int) ($pageData['status'] ?? 1),
                    'content' => $pageData['content'] ?? null,
                    'meta_title' => $pageData['meta_title'] ?? null,
                    'meta_description' => $pageData['meta_description'] ?? null,
                    'canonical_url' => $pageData['canonical_url'] ?? null,
                    'og_image' => $pageData['og_image'] ?? null,
                    'sections_data' => $sections,
                    'sync_hash' => $syncHash,
                    'sort_order' => (int) ($pageData['sort_order'] ?? 0),
                    'is_indexable' => true,
                ]);

                // Import each section into page_sections table
                foreach ($sections as $sec) {
                    PageSection::create([
                        'page_id' => $page->id,
                        'section_name' => $sec['section_name'],
                        'section_type' => $sec['section_type'],
                        'content' => $sec['content'],
                        'sort_order' => (int) ($sec['sort_order'] ?? 0),
                        'status' => (int) ($sec['status'] ?? 1),
                    ]);
                    $totalSections++;
                }

                // Import Metadata record into metadata table
                Metadata::create([
                    'website_id' => $website->id,
                    'page_id' => $page->id,
                    'meta_title' => $page->meta_title,
                    'meta_description' => $page->meta_description,
                    'canonical_url' => $page->canonical_url,
                    'robots' => 'index, follow',
                    'og_image' => $page->og_image,
                ]);
            }

            $importedPages[] = $page;
        }

        // If website has a tenant database connection, sync pages to tenant
        try {
            $isTestEnv = app()->environment('testing');
            $isMysqlInTest = $isTestEnv && ($website->databaseConnection?->driver === 'mysql');

            if ($website->databaseConnection && $website->provisioning_status === 'provisioned' && ! $isMysqlInTest) {
                $syncService = app(PageSyncService::class);
                foreach ($importedPages as $page) {
                    $syncService->syncPageToTenant($page, $website);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Vocabot tenant sync warning during central import: '.$e->getMessage());
        }

        return [
            'imported_pages' => count($importedPages),
            'total_sections' => $totalSections,
            'pages' => $importedPages,
        ];
    }
}
