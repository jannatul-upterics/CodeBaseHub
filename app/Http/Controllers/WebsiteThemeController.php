<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use App\Models\Website;
use App\Models\WebsiteThemeSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

        // Load website media assets (images) for logo picker
        $mediaAssets = $website->media()
            ->where('file_type', 'image')
            ->latest('id')
            ->get();

        $isVocabot = Str::contains(strtolower($website->website_name), 'vocabot')
            || Str::contains(strtolower($website->domain), 'vocabot');

        // Compile default appearance parameters
        $defaultAppearance = [
            'primary_color' => $isVocabot ? '#7c3aed' : '#2563eb',
            'accent_color' => $isVocabot ? '#a855f7' : '#a855f7',
            'background_color' => $isVocabot ? '#070313' : '#ffffff',
            'text_color' => $isVocabot ? '#ffffff' : '#1e293b',
            'font_family' => $isVocabot ? 'Plus Jakarta Sans' : 'Instrument Sans',
            'logo' => $isVocabot ? 'images/logo2.webp' : '',
            'button_style' => $isVocabot ? 'pill' : 'rounded',
            'layout_mode' => 'boxed',
            'navbar_style' => 'sticky',
            'custom_css' => '',
        ];

        if ($activeTheme && is_array($activeTheme->appearance)) {
            $defaultAppearance = array_merge($defaultAppearance, $activeTheme->appearance);
        }

        return view('websites.theme.index', compact(
            'website',
            'allThemes',
            'activeTheme',
            'themeSettings',
            'mediaAssets',
            'defaultAppearance',
            'isVocabot'
        ));
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
            'background_color' => ['nullable', 'string', 'regex:/^#([a-fA-F0-9]{3}){1,2}$/'],
            'text_color' => ['nullable', 'string', 'regex:/^#([a-fA-F0-9]{3}){1,2}$/'],
            'font_family' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'string', 'max:255'],
            'logo_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:5120'],
            'button_style' => ['nullable', 'string', 'in:pill,rounded,square'],
            'layout_mode' => ['nullable', 'string', 'in:boxed,fluid,narrow'],
            'navbar_style' => ['nullable', 'string', 'in:standard,sticky,transparent'],
            'custom_css' => ['nullable', 'string', 'max:5000'],
        ]);

        $themeId = (int) $validated['theme_id'];

        // Handle logo file upload if provided
        if ($request->hasFile('logo_file')) {
            $file = $request->file('logo_file');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->guessExtension() ?? $file->getClientOriginalExtension();
            $sanitizedName = Str::slug($originalName).'.'.$extension;
            $storedPath = $file->store("websites/{$website->id}/media", 'public');

            $website->media()->create([
                'file_name' => $sanitizedName,
                'file_path' => $storedPath,
                'file_type' => 'image',
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'alt_text' => 'Website Theme Logo',
                'status' => 1,
            ]);

            $validated['logo'] = 'storage/'.$storedPath;
        }

        $configurableKeys = [
            'primary_color',
            'accent_color',
            'background_color',
            'text_color',
            'font_family',
            'logo',
            'button_style',
            'layout_mode',
            'navbar_style',
            'custom_css',
        ];

        foreach ($configurableKeys as $key) {
            if (array_key_exists($key, $validated) || $request->has($key)) {
                $value = $validated[$key] ?? $request->input($key);
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

    /**
     * Reset website theme settings to default values.
     */
    public function resetSettings(Website $website, Request $request): RedirectResponse
    {
        $themeId = (int) ($request->input('theme_id') ?? $website->activeTheme()?->id ?? 1);
        $theme = Theme::find($themeId) ?? $website->activeTheme() ?? Theme::first();

        $isVocabot = Str::contains(strtolower($website->website_name), 'vocabot')
            || Str::contains(strtolower($website->domain), 'vocabot');

        $defaultValues = [
            'primary_color' => $isVocabot ? '#7c3aed' : '#2563eb',
            'accent_color' => $isVocabot ? '#a855f7' : '#a855f7',
            'background_color' => $isVocabot ? '#070313' : '#ffffff',
            'text_color' => $isVocabot ? '#ffffff' : '#1e293b',
            'font_family' => $isVocabot ? 'Plus Jakarta Sans' : 'Instrument Sans',
            'logo' => $isVocabot ? 'images/logo2.webp' : '',
            'button_style' => $isVocabot ? 'pill' : 'rounded',
            'layout_mode' => 'boxed',
            'navbar_style' => 'sticky',
            'custom_css' => '',
        ];

        if ($theme && is_array($theme->appearance) && ! empty($theme->appearance)) {
            $defaultValues = array_merge($defaultValues, $theme->appearance);
        }

        foreach ($defaultValues as $key => $val) {
            WebsiteThemeSetting::updateOrCreate(
                [
                    'website_id' => $website->id,
                    'theme_id' => $theme ? $theme->id : $themeId,
                    'setting_key' => $key,
                ],
                [
                    'setting_value' => (string) $val,
                ]
            );
        }

        return redirect()
            ->route('websites.theme.index', $website)
            ->with('success', 'Theme settings have been reset to default values.');
    }
}
