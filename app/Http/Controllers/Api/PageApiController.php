<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Page;
use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageApiController extends Controller
{
    /**
     * Resolve the website from the request domain header or query param.
     */
    protected function resolveWebsite(Request $request): ?Website
    {
        $domain = $request->header('X-Website-Domain')
            ?: $request->query('domain');

        if (! $domain) {
            return null;
        }

        $domain = explode(':', (string) $domain)[0];

        $domainRecord = Domain::where('domain_name', $domain)->first();

        return $domainRecord?->website;
    }

    /**
     * List all published pages for a website.
     *
     * GET /api/v1/pages
     * Header: X-Website-Domain: vocabot.ai   OR  ?domain=vocabot.ai
     */
    public function index(Request $request): JsonResponse
    {
        $website = $this->resolveWebsite($request);

        if (! $website) {
            return response()->json(['success' => false, 'message' => 'Website not found for the given domain.'], 404);
        }

        $query = Page::where('website_id', $website->id)
            ->active()
            ->where('status', 1);

        if ($request->boolean('only_trashed')) {
            $query->onlyTrashed();
        } elseif ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }

        $pages = $query->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'title', 'slug', 'page_type', 'is_homepage', 'meta_title', 'meta_description', 'canonical_url', 'og_image']);

        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    /**
     * Return the published homepage for a website.
     *
     * GET /api/v1/pages/homepage
     */
    public function homepage(Request $request): JsonResponse
    {
        $website = $this->resolveWebsite($request);

        if (! $website) {
            return response()->json(['success' => false, 'message' => 'Website not found for the given domain.'], 404);
        }

        $page = Page::where('website_id', $website->id)
            ->active()
            ->where('status', 1)
            ->where('is_homepage', 1)
            ->first();

        if (! $page) {
            // Fall back to a page with slug 'home'
            $page = Page::where('website_id', $website->id)
                ->active()
                ->where('status', 1)
                ->where('slug', 'home')
                ->first();
        }

        if (! $page) {
            return response()->json(['success' => false, 'message' => 'Homepage not found or not published.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatPage($page),
        ]);
    }

    /**
     * Return a single published page by slug.
     *
     * GET /api/v1/pages/{slug}
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $website = $this->resolveWebsite($request);

        if (! $website) {
            return response()->json(['success' => false, 'message' => 'Website not found for the given domain.'], 404);
        }

        $page = Page::where('website_id', $website->id)
            ->active()
            ->where('status', 1)
            ->where('slug', $slug)
            ->first();

        if (! $page) {
            return response()->json(['success' => false, 'message' => 'Page not found or not published.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatPage($page),
        ]);
    }

    /**
     * Format a page model with its sections and metadata for API response.
     *
     * @return array<string, mixed>
     */
    protected function formatPage(Page $page): array
    {
        $sections = $page->sections()
            ->where('status', 1)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($section) => [
                'id' => $section->id,
                'section_name' => $section->section_name,
                'section_type' => $section->section_type,
                'content' => $section->content,
                'sort_order' => $section->sort_order,
            ])
            ->values()
            ->all();

        $metadataRecord = $page->metadata()->first();

        $metadata = [
            'meta_title' => $metadataRecord?->meta_title ?? $page->meta_title,
            'meta_description' => $metadataRecord?->meta_description ?? $page->meta_description,
            'canonical_url' => $metadataRecord?->canonical_url ?? $page->canonical_url,
            'og_image' => $metadataRecord?->og_image ?? $page->og_image,
        ];

        return [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'page_type' => $page->page_type,
            'is_homepage' => (bool) $page->is_homepage,
            'status' => $page->status,
            'content' => $page->content,
            'meta_title' => $metadata['meta_title'],
            'meta_description' => $metadata['meta_description'],
            'canonical_url' => $metadata['canonical_url'],
            'og_image' => $metadata['og_image'],
            'sections' => $sections,
            'metadata' => $metadata,
        ];
    }
}
