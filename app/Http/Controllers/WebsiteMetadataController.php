<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMetadataRequest;
use App\Models\Metadata;
use App\Models\Page;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebsiteMetadataController extends Controller
{
    /**
     * Display the SEO and metadata management interface for the website.
     */
    public function index(Website $website, Request $request): View
    {
        // Website-level default metadata
        $websiteMeta = $website->metadata()->whereNull('page_id')->first();

        // Pages with their scoped metadata
        $pages = $website->pages()->with('metadata')->orderBy('title')->get();

        $selectedPageId = $request->query('page_id');
        $selectedPage = null;
        $selectedPageMeta = null;

        if ($selectedPageId) {
            $selectedPage = $website->pages()->with('metadata')->find($selectedPageId);
            $selectedPageMeta = $selectedPage?->metadata()->first();
        }

        return view('websites.seo.index', compact(
            'website',
            'websiteMeta',
            'pages',
            'selectedPage',
            'selectedPageMeta'
        ));
    }

    /**
     * Save or update website-level default metadata.
     */
    public function saveWebsiteMeta(Website $website, StoreMetadataRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $website->metadata()->updateOrCreate(
            [
                'website_id' => $website->id,
                'page_id' => null,
            ],
            [
                'meta_title' => $validated['meta_title'] ?? null,
                'meta_description' => $validated['meta_description'] ?? null,
                'canonical_url' => $validated['canonical_url'] ?? null,
                'robots' => $validated['robots'] ?? 'index, follow',
                'og_image' => $validated['og_image'] ?? null,
            ]
        );

        return redirect()
            ->route('websites.seo.index', $website)
            ->with('success', 'Website-level default SEO metadata saved successfully.');
    }

    /**
     * Save or update page-specific metadata.
     */
    public function savePageMeta(Website $website, Page $page, StoreMetadataRequest $request): RedirectResponse
    {
        if ($page->website_id !== $website->id) {
            abort(404);
        }

        $validated = $request->validated();

        Metadata::updateOrCreate(
            [
                'website_id' => $website->id,
                'page_id' => $page->id,
            ],
            [
                'meta_title' => $validated['meta_title'] ?? null,
                'meta_description' => $validated['meta_description'] ?? null,
                'canonical_url' => $validated['canonical_url'] ?? null,
                'robots' => $validated['robots'] ?? 'index, follow',
                'og_image' => $validated['og_image'] ?? null,
            ]
        );

        return redirect()
            ->route('websites.seo.index', ['website' => $website, 'page_id' => $page->id])
            ->with('success', "SEO metadata for page '{$page->title}' saved successfully.");
    }

    /**
     * Delete a metadata record.
     */
    public function destroy(Website $website, Metadata $metadata): RedirectResponse
    {
        if ($metadata->website_id !== $website->id) {
            abort(404);
        }

        $isPageMeta = (bool) $metadata->page_id;
        $pageName = $metadata->page?->title;

        $metadata->delete();

        $msg = $isPageMeta && $pageName
            ? "Custom SEO metadata for page '{$pageName}' removed (falling back to site default)."
            : 'Default site SEO metadata reset.';

        return redirect()
            ->route('websites.seo.index', $website)
            ->with('success', $msg);
    }
}
