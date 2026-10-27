<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use App\Models\Website;
use App\Models\WebsiteThemeSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebsiteThemeController extends Controller
{
    /**
     * Display the theme configuration workspace for the website.
     */
    public function index(Website $website): View
    {
        $allThemes = Theme::where('status', 1)->orderBy('theme_name')->get();

        // Get currently active theme setting
        $activeThemeSetting = $website->themeSettings()
            ->where('setting_key', 'active_theme')
            ->first();

        $activeTheme = $activeThemeSetting?->theme ?? $allThemes->first();

        // Load all custom theme settings for this website
        $themeSettings = $website->themeSettings()
            ->with('theme')
            ->get();

        return view('websites.theme.index', compact('website', 'allThemes', 'activeTheme', 'themeSettings'));
    }

    /**
     * Set or switch the active theme for the website.
     */
    public function setActiveTheme(Website $website, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme_id' => ['required', 'integer', 'exists:themes,id'],
        ]);

        $theme = Theme::findOrFail($validated['theme_id']);

        WebsiteThemeSetting::updateOrCreate(
            [
                'website_id' => $website->id,
                'setting_key' => 'active_theme',
            ],
            [
                'theme_id' => $theme->id,
                'setting_value' => 'active',
            ]
        );

        return redirect()
            ->route('websites.theme.index', $website)
            ->with('success', "Active theme switched to '{$theme->theme_name}'.");
    }

    /**
     * Update theme-specific configuration parameters.
     */
    public function updateSettings(Website $website, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme_id' => ['required', 'integer', 'exists:themes,id'],
            'primary_color' => ['nullable', 'string', 'regex:/^#([a-fA-F0-9]{3}){1,2}$/'],
            'accent_color' => ['nullable', 'string', 'regex:/^#([a-fA-F0-9]{3}){1,2}$/'],
            'font_family' => ['nullable', 'string', 'max:100'],
            'layout_mode' => ['nullable', 'string', 'in:boxed,fluid,narrow'],
            'navbar_style' => ['nullable', 'string', 'in:standard,sticky,transparent'],
            'custom_css' => ['nullable', 'string', 'max:5000'],
        ]);

        $themeId = (int) $validated['theme_id'];
        $configurableKeys = ['primary_color', 'accent_color', 'font_family', 'layout_mode', 'navbar_style', 'custom_css'];

        foreach ($configurableKeys as $key) {
            if ($request->has($key)) {
                $value = $request->input($key);
                WebsiteThemeSetting::updateOrCreate(
                    [
                        'website_id' => $website->id,
                        'theme_id' => $themeId,
                        'setting_key' => $key,
                    ],
                    [
                        'setting_value' => $value !== null ? (string) $value : '',
                    ]
                );
            }
        }

        return redirect()
            ->route('websites.theme.index', $website)
            ->with('success', 'Theme configuration updated successfully.');
    }
}
