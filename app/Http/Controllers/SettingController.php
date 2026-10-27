<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Display a listing of website configuration settings.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $websiteId = $request->query('website_id', '');

        $query = Setting::query()->with('website');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('setting_key', 'like', "%{$search}%")
                    ->orWhere('setting_value', 'like', "%{$search}%");
            });
        }

        if ($websiteId !== '') {
            $query->where('website_id', (int) $websiteId);
        }

        $settings = $query->latest('id')
            ->paginate(15)
            ->withQueryString();

        $websites = Website::orderBy('website_name')->get();

        return view('settings.index', compact('settings', 'search', 'websiteId', 'websites'));
    }
}
