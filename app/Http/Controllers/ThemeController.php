<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreThemeRequest;
use App\Http\Requests\UpdateThemeRequest;
use App\Models\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThemeController extends Controller
{
    /**
     * Display a listing of design themes.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', '');

        $query = Theme::query()->withCount('websites');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('theme_name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $themes = $query->orderBy('theme_name')
            ->paginate(12)
            ->withQueryString();

        return view('themes.index', compact('themes', 'search', 'status'));
    }

    /**
     * Show the form for creating a new theme.
     */
    public function create(): View
    {
        return view('themes.create');
    }

    /**
     * Store a newly created theme in storage.
     */
    public function store(StoreThemeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = isset($data['status']) ? (int) $data['status'] : 1;

        $theme = Theme::create($data);

        return redirect()
            ->route('themes.index')
            ->with('success', "Theme '{$theme->theme_name}' created successfully.");
    }

    /**
     * Display the specified theme.
     */
    public function show(Theme $theme): View
    {
        $theme->load(['websites.primaryDomain', 'websiteThemeSettings']);

        return view('themes.show', compact('theme'));
    }

    /**
     * Show the form for editing the specified theme.
     */
    public function edit(Theme $theme): View
    {
        return view('themes.edit', compact('theme'));
    }

    /**
     * Update the specified theme in storage.
     */
    public function update(UpdateThemeRequest $request, Theme $theme): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = isset($data['status']) ? (int) $data['status'] : 0;

        $theme->update($data);

        return redirect()
            ->route('themes.show', $theme)
            ->with('success', "Theme '{$theme->theme_name}' updated successfully.");
    }

    /**
     * Remove the specified theme from storage with safe dependency handling.
     */
    public function destroy(Theme $theme): RedirectResponse
    {
        $websiteCount = $theme->websites()->count();
        if ($websiteCount > 0) {
            return redirect()
                ->route('themes.index')
                ->with('error', "Cannot delete theme '{$theme->theme_name}' because it is currently assigned to {$websiteCount} website(s). Please reassign those websites to another theme first.");
        }

        $name = $theme->theme_name;
        $theme->delete();

        return redirect()
            ->route('themes.index')
            ->with('success', "Theme '{$name}' deleted successfully.");
    }
}
