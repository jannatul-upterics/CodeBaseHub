<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Display a listing of pages across websites.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $websiteId = $request->query('website_id', '');

        $query = Page::query()->with('website')->withCount('sections');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($websiteId !== '') {
            $query->where('website_id', (int) $websiteId);
        }

        $pages = $query->latest('id')
            ->paginate(12)
            ->withQueryString();

        $websites = Website::orderBy('website_name')->get();

        return view('pages.index', compact('pages', 'search', 'websiteId', 'websites'));
    }
}
