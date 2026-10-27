<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Models\Page;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WebsitePageController extends Controller
{
    /**
     * Display a listing of pages for the specified website.
     */
    public function index(Website $website, Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', '');

        $query = $website->pages()->withCount('sections');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('page_type', 'like', "%{$search}%");
            });
        }

        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $pages = $query->orderByDesc('is_homepage')
            ->orderBy('title')
            ->paginate(10)
            ->withQueryString();

        $totalPages = $website->pages()->count();
        $publishedPages = $website->pages()->where('status', 1)->count();
        $hasHomepage = $website->pages()->where('is_homepage', true)->exists();

        return view('websites.pages.index', compact(
            'website',
            'pages',
            'search',
            'status',
            'totalPages',
            'publishedPages',
            'hasHomepage'
        ));
    }

    /**
     * Show the form for creating a new page for this website.
     */
    public function create(Website $website): View
    {
        $hasHomepage = $website->pages()->where('is_homepage', true)->exists();

        return view('websites.pages.create', compact('website', 'hasHomepage'));
    }

    /**
     * Store a newly created page in storage for this website.
     */
    public function store(Website $website, StorePageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $isHomepage = (bool) ($request->boolean('is_homepage'));

        DB::transaction(function () use ($website, $data, $isHomepage, &$page): void {
            if ($isHomepage) {
                // Unmark existing homepage for this website
                Page::where('website_id', $website->id)
                    ->where('is_homepage', true)
                    ->update(['is_homepage' => false]);
            }

            $page = $website->pages()->create([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'page_type' => $data['page_type'],
                'is_homepage' => $isHomepage,
                'status' => (int) ($data['status'] ?? 1),
            ]);
        });

        return redirect()
            ->route('websites.pages.show', [$website, $page])
            ->with('success', "Page '{$page->title}' created successfully.");
    }

    /**
     * Display the specified page and its sections.
     */
    public function show(Website $website, Page $page): View
    {
        // Guard against mismatched website/page
        if ($page->website_id !== $website->id) {
            abort(404);
        }

        $page->load(['sections', 'metadata']);
        $metadata = $page->metadata()->first();

        return view('websites.pages.show', compact('website', 'page', 'metadata'));
    }

    /**
     * Show the form for editing the specified page.
     */
    public function edit(Website $website, Page $page): View
    {
        if ($page->website_id !== $website->id) {
            abort(404);
        }

        $currentHomepage = $website->pages()->where('is_homepage', true)->first();

        return view('websites.pages.edit', compact('website', 'page', 'currentHomepage'));
    }

    /**
     * Update the specified page in storage.
     */
    public function update(Website $website, Page $page, UpdatePageRequest $request): RedirectResponse
    {
        if ($page->website_id !== $website->id) {
            abort(404);
        }

        $data = $request->validated();
        $isHomepage = (bool) ($request->boolean('is_homepage'));

        DB::transaction(function () use ($website, $page, $data, $isHomepage): void {
            if ($isHomepage && ! $page->is_homepage) {
                // Unmark any previous homepage
                Page::where('website_id', $website->id)
                    ->where('id', '!=', $page->id)
                    ->where('is_homepage', true)
                    ->update(['is_homepage' => false]);
            }

            $page->update([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'page_type' => $data['page_type'],
                'is_homepage' => $isHomepage,
                'status' => (int) ($data['status'] ?? 1),
            ]);
        });

        return redirect()
            ->route('websites.pages.show', [$website, $page])
            ->with('success', "Page '{$page->title}' updated successfully.");
    }

    /**
     * Remove the specified page from storage.
     */
    public function destroy(Website $website, Page $page): RedirectResponse
    {
        if ($page->website_id !== $website->id) {
            abort(404);
        }

        $title = $page->title;
        $wasHomepage = $page->is_homepage;

        DB::transaction(function () use ($page): void {
            // Delete related sections and page-scoped metadata
            $page->sections()->delete();
            $page->metadata()->delete();
            $page->delete();
        });

        $message = "Page '{$title}' deleted successfully.";
        if ($wasHomepage) {
            $message .= ' Note: You deleted the homepage. Please assign another page as homepage.';
        }

        return redirect()
            ->route('websites.pages.index', $website)
            ->with('success', $message);
    }
}
