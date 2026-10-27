<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSettingRequest;
use App\Models\Setting;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebsiteSettingController extends Controller
{
    /**
     * Display a listing of key/value settings for this website.
     */
    public function index(Website $website, Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $query = $website->settings();

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('setting_key', 'like', "%{$search}%")
                    ->orWhere('setting_value', 'like', "%{$search}%");
            });
        }

        $settings = $query->orderBy('setting_key')->get();

        // Common preset suggestion keys
        $presetKeys = [
            'site_name' => 'Site Name / Brand Display',
            'tagline' => 'Brand Tagline / Slogan',
            'contact_email' => 'Primary Contact Email',
            'support_phone' => 'Customer Support Phone',
            'address' => 'Physical Business Address',
            'google_analytics_id' => 'Google Analytics Tracking ID (G-XXXXX)',
            'currency' => 'Primary Currency Code (e.g. USD)',
            'timezone' => 'Operating Timezone (e.g. America/New_York)',
            'social_twitter' => 'Twitter / X Profile URL',
            'social_facebook' => 'Facebook Page URL',
            'social_linkedin' => 'LinkedIn Company URL',
            'header_announcement' => 'Global Top Announcement Banner Text',
        ];

        return view('websites.settings.index', compact('website', 'settings', 'search', 'presetKeys'));
    }

    /**
     * Store a new setting for the website.
     */
    public function store(Website $website, StoreSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $website->settings()->create([
            'setting_key' => $validated['setting_key'],
            'setting_value' => $validated['setting_value'],
        ]);

        return redirect()
            ->route('websites.settings.index', $website)
            ->with('success', "Setting '{$validated['setting_key']}' added successfully.");
    }

    /**
     * Update an existing setting value.
     */
    public function update(Website $website, Setting $setting, Request $request): RedirectResponse
    {
        if ($setting->website_id !== $website->id) {
            abort(404);
        }

        $validated = $request->validate([
            'setting_value' => ['required', 'string'],
        ]);

        $setting->update([
            'setting_value' => $validated['setting_value'],
        ]);

        return redirect()
            ->route('websites.settings.index', $website)
            ->with('success', "Setting '{$setting->setting_key}' updated.");
    }

    /**
     * Delete a setting from storage.
     */
    public function destroy(Website $website, Setting $setting): RedirectResponse
    {
        if ($setting->website_id !== $website->id) {
            abort(404);
        }

        $key = $setting->setting_key;
        $setting->delete();

        return redirect()
            ->route('websites.settings.index', $website)
            ->with('success', "Setting '{$key}' removed.");
    }
}
