<?php

namespace App\Http\Controllers;

use App\Http\Middleware\TenantConnection;
use App\Models\Page;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

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

    /**
     * Display a published page fetched from the central CodeBaseHub database.
     */
    public function preview(string $slug): View
    {
        try {
            $connection = TenantConnection::getCentralConnection();
            $page = Page::on($connection)
                ->where('slug', $slug)
                ->where('status', 1)
                ->with(['sections', 'metadata', 'website', 'template'])
                ->first();
        } catch (Throwable $e) {
            Log::error('Failed to fetch central page', ['slug' => $slug, 'error' => $e->getMessage()]);
            abort(404, 'Page not found');
        }

        if (! $page) {
            abort(404, 'Page not found');
        }

        return view('pages.show', ['page' => $page]);
    }
}
