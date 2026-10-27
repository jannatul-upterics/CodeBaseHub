<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Domain;
use App\Models\Template;
use App\Models\Theme;
use App\Models\Website;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard with summary metrics, activity feeds, and onboarding guidance.
     */
    public function index(): View
    {
        $totalWebsites = Website::count();
        $activeWebsites = Website::where('status', 1)->count();
        $inactiveWebsites = max(0, $totalWebsites - $activeWebsites);

        $totalCustomers = Customer::count();
        $totalThemes = Theme::count();
        $totalTemplates = Template::count();

        $totalDomains = Domain::count();
        $verifiedDomains = Domain::whereNotNull('verified_at')->count();
        $sslDomains = Domain::where('ssl_enabled', true)->count();

        $metrics = [
            'total_websites' => $totalWebsites,
            'active_websites' => $activeWebsites,
            'inactive_websites' => $inactiveWebsites,
            'total_customers' => $totalCustomers,
            'total_themes' => $totalThemes,
            'total_templates' => $totalTemplates,
            'total_domains' => $totalDomains,
            'verified_domains' => $verifiedDomains,
            'ssl_domains' => $sslDomains,
            'domain_verification_rate' => $totalDomains > 0 ? (int) round(($verifiedDomains / $totalDomains) * 100) : 0,
            'website_active_rate' => $totalWebsites > 0 ? (int) round(($activeWebsites / $totalWebsites) * 100) : 0,
        ];

        // Provider distribution breakdown (e.g., AWS, Cloud, Vercel, DigitalOcean)
        $providers = Website::query()
            ->selectRaw('provider, count(*) as count')
            ->groupBy('provider')
            ->orderByDesc('count')
            ->get();

        // Recent websites with customer and primaryDomain eager loaded (avoids N+1)
        $recentWebsites = Website::with(['customer', 'primaryDomain'])
            ->latest('updated_at')
            ->take(5)
            ->get();

        // Recent customers with website counts (avoids N+1)
        $recentCustomers = Customer::withCount('websites')
            ->latest('created_at')
            ->take(5)
            ->get();

        $isEmptyDatabase = ($totalWebsites === 0 && $totalCustomers === 0);

        return view('dashboard.index', compact(
            'metrics',
            'providers',
            'recentWebsites',
            'recentCustomers',
            'isEmptyDatabase'
        ));
    }
}
