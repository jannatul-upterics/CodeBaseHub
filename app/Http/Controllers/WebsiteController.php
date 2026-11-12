<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWebsiteRequest;
use App\Http\Requests\UpdateWebsiteRequest;
use App\Models\Customer;
use App\Models\Domain;
use App\Models\Template;
use App\Models\Theme;
use App\Models\Website;
use App\Models\WebsiteThemeSetting;
use App\Services\WebsiteProvisioningService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class WebsiteController extends Controller
{
    /**
     * Display a listing of websites with server-side search and pagination.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', '');
        $provider = trim((string) $request->query('provider', ''));
        $template = trim((string) $request->query('template', ''));

        $query = Website::query()->with(['customer', 'template', 'primaryDomain', 'themes', 'databaseConnection']);

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('website_name', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%")
                    ->orWhereHas('template', function (Builder $templateQuery) use ($search): void {
                        $templateQuery->where('template_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('themes', function (Builder $themeQuery) use ($search): void {
                        $themeQuery->where('theme_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        if ($provider !== '') {
            $query->where('provider', $provider);
        }

        if ($template !== '') {
            $query->where(function (Builder $builder) use ($template): void {
                $builder->whereHas('template', function (Builder $templateQuery) use ($template): void {
                    $templateQuery->where('template_name', $template);
                })->orWhereHas('themes', function (Builder $themeQuery) use ($template): void {
                    $themeQuery->where('theme_name', $template);
                });
            });
        }

        $websites = $query->latest('id')
            ->paginate(10)
            ->withQueryString();

        $providers = Website::query()
            ->select('provider')
            ->distinct()
            ->orderBy('provider')
            ->pluck('provider');

        return view('websites.index', compact('websites', 'search', 'status', 'provider', 'providers', 'template'));
    }

    /**
     * Show the form for creating a new website.
     */
    public function create(Request $request): View
    {
        $customers = Customer::orderBy('name')->get();
        $themes = Theme::where('status', 1)->orderBy('theme_name')->get();
        $templates = Template::where('status', 1)->orderBy('template_name')->get();
        $selectedCustomerId = $request->query('customer_id');

        return view('websites.create', compact('customers', 'themes', 'templates', 'selectedCustomerId'));
    }

    /**
     * Store a newly created website in storage.
     */
    public function store(
        StoreWebsiteRequest $request,
        WebsiteProvisioningService $provisioningService
    ): RedirectResponse {
        $data = $request->validated();
        $themeId = $data['theme_id'] ?? null;
        unset($data['theme_id']);

        $tenantDbMode = $data['tenant_db_mode'] ?? null;
        $driver = (string) ($data['driver'] ?? config('database.default', 'mysql'));
        $dbConfig = [
            'driver' => $driver,
            'host' => $data['db_host'] ?? config("database.connections.{$driver}.host", '127.0.0.1'),
            'port' => (int) ($data['db_port'] ?? config("database.connections.{$driver}.port", 3306)),
            'database' => $data['db_name'] ?? null,
            'username' => $data['db_username'] ?? config("database.connections.{$driver}.username", 'root'),
            'password' => $data['db_password'] ?? config("database.connections.{$driver}.password", ''),
        ];
        $runMigrations = (bool) ($data['run_migrations'] ?? true);

        unset(
            $data['tenant_db_mode'],
            $data['driver'],
            $data['db_host'],
            $data['db_port'],
            $data['db_name'],
            $data['db_username'],
            $data['db_password'],
            $data['run_migrations']
        );

        // Safely persist website, theme setting, and primary domain within a transaction
        $website = DB::transaction(function () use ($data, $themeId) {
            $website = Website::create($data);

            // Attach theme configuration if selected
            if ($themeId) {
                WebsiteThemeSetting::create([
                    'website_id' => $website->id,
                    'theme_id' => (int) $themeId,
                    'setting_key' => 'active_theme',
                    'setting_value' => 'active',
                ]);
            }

            // Ensure bare domain record is created in domains table
            Domain::firstOrCreate(
                ['domain_name' => $website->domain],
                [
                    'website_id' => $website->id,
                    'is_primary' => true,
                    'ssl_enabled' => false,
                    'status' => 1,
                ]
            );

            return $website;
        });

        if ($tenantDbMode) {
            try {
                if ($tenantDbMode === 'connect') {
                    $provisioningService->connectExistingDatabase($website, $dbConfig, $runMigrations);
                } else {
                    $provisioningService->provisionNewDatabase($website, $dbConfig);
                }

                return redirect()
                    ->route('websites.index')
                    ->with('success', "Website '{$website->website_name}' created and tenant database provisioned successfully.");
            } catch (Throwable $e) {
                Log::error('Website tenant provisioning failed on store', [
                    'website_id' => $website->id,
                    'error' => $e->getMessage(),
                ]);

                return redirect()
                    ->route('websites.show', $website)
                    ->with('warning', "Website '{$website->website_name}' was registered, but tenant database provisioning failed: {$e->getMessage()}. You can retry provisioning below.");
            }
        }

        return redirect()
            ->route('websites.index')
            ->with('success', "Website '{$website->website_name}' created successfully.");
    }

    /**
     * Display the specified website.
     */
    public function show(Website $website): View
    {
        $website->load(['customer', 'template', 'domains', 'pages', 'themeSettings.theme', 'settings', 'metadata', 'databaseConnection']);
        $allThemes = Theme::where('status', 1)->orderBy('theme_name')->get();

        return view('websites.show', compact('website', 'allThemes'));
    }

    /**
     * Show the form for editing the specified website.
     */
    public function edit(Website $website): View
    {
        $customers = Customer::orderBy('name')->get();
        $themes = Theme::where('status', 1)->orderBy('theme_name')->get();
        $templates = Template::where('status', 1)->orderBy('template_name')->get();
        $currentThemeId = $website->themeSettings()->where('setting_key', 'active_theme')->value('theme_id')
            ?? $website->themeSettings()->first()?->theme_id;

        return view('websites.edit', compact('website', 'customers', 'themes', 'templates', 'currentThemeId'));
    }

    /**
     * Update the specified website in storage.
     */
    public function update(UpdateWebsiteRequest $request, Website $website): RedirectResponse
    {
        $data = $request->validated();
        $themeId = $data['theme_id'] ?? null;
        unset($data['theme_id']);

        $website->update($data);

        if ($themeId) {
            WebsiteThemeSetting::updateOrCreate(
                ['website_id' => $website->id, 'setting_key' => 'active_theme'],
                ['theme_id' => (int) $themeId, 'setting_value' => 'active']
            );
        }

        return redirect()
            ->route('websites.show', $website)
            ->with('success', "Website '{$website->website_name}' updated successfully.");
    }

    /**
     * Assign or update a theme configuration on the website.
     */
    public function assignTheme(Request $request, Website $website): RedirectResponse
    {
        $validated = $request->validate([
            'theme_id' => ['required', 'integer', 'exists:themes,id'],
            'setting_key' => ['nullable', 'string', 'max:100'],
            'setting_value' => ['nullable', 'string', 'max:255'],
        ]);

        $theme = Theme::findOrFail($validated['theme_id']);

        WebsiteThemeSetting::updateOrCreate(
            ['website_id' => $website->id, 'setting_key' => $validated['setting_key'] ?? 'active_theme'],
            [
                'theme_id' => $theme->id,
                'setting_value' => $validated['setting_value'] ?? 'active',
            ]
        );

        return redirect()
            ->route('websites.show', $website)
            ->with('success', "Theme '{$theme->theme_name}' applied to website.");
    }

    /**
     * Remove the specified website from storage.
     */
    public function destroy(Website $website): RedirectResponse
    {
        $name = $website->website_name;

        $website->delete();

        return redirect()
            ->route('websites.index')
            ->with('success', "Website '{$name}' deleted successfully.");
    }

    /**
     * Test database connection before saving.
     */
    public function testDatabaseConnection(
        Request $request,
        WebsiteProvisioningService $provisioningService
    ): JsonResponse {
        $validated = $request->validate([
            'mode' => ['required', 'string', 'in:create,connect'],
            'driver' => ['nullable', 'string'],
            'host' => ['nullable', 'string'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'database' => ['nullable', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_]+$/'],
            'username' => ['nullable', 'string'],
            'password' => ['nullable', 'string'],
        ]);

        $driver = (string) ($validated['driver'] ?? config('database.default', 'mysql'));
        $driverConfig = config("database.connections.{$driver}", []);

        $config = [
            'driver' => $driver,
            'host' => $validated['host'] ?? ($driverConfig['host'] ?? '127.0.0.1'),
            'port' => (int) ($validated['port'] ?? ($driverConfig['port'] ?? 3306)),
            'database' => $validated['database'] ?? null,
            'username' => $validated['username'] ?? ($driverConfig['username'] ?? 'root'),
            'password' => $validated['password'] ?? ($driverConfig['password'] ?? ''),
        ];

        $result = $provisioningService->testConnection($config, $validated['mode']);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Retry tenant database provisioning for a website.
     */
    public function retryProvisioning(
        Request $request,
        Website $website,
        WebsiteProvisioningService $provisioningService
    ): RedirectResponse {
        $validated = $request->validate([
            'tenant_db_mode' => ['nullable', 'string', 'in:create,connect'],
            'db_host' => ['nullable', 'string'],
            'db_port' => ['nullable', 'integer', 'between:1,65535'],
            'db_name' => ['nullable', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_]+$/'],
            'db_username' => ['nullable', 'string'],
            'db_password' => ['nullable', 'string'],
            'run_migrations' => ['nullable', 'boolean'],
        ]);

        try {
            $provisioningService->retryProvisioning($website, $validated);

            return redirect()
                ->route('websites.show', $website)
                ->with('success', "Tenant database for '{$website->website_name}' provisioned successfully.");
        } catch (Throwable $e) {
            return redirect()
                ->route('websites.show', $website)
                ->with('error', 'Tenant database provisioning failed: '.$e->getMessage());
        }
    }
}
