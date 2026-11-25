<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class TenantPageController extends Controller
{
    /**
     * Render or return a published page from the active tenant database.
     */
    public function show(Request $request, ?string $slug = null): Response
    {
        $website = $request->attributes->get('website');
        $isHome = empty($slug) || $slug === 'home';
        $query = DB::table('pages')->where('status', 1);

        if (Schema::hasColumn('pages', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if ($isHome) {
            $page = (clone $query)->where('is_homepage', 1)->first()
                ?? (clone $query)->where('slug', 'home')->first();
        } else {
            $page = $query->where('slug', $slug)->first();
        }

        if (! $page) {
            abort(404, 'Page not found');
        }

        $sections = Schema::hasTable('page_sections')
            ? DB::table('page_sections')->where('page_id', $page->id)->orderBy('sort_order')->get()
            : collect();

        $metadata = Schema::hasTable('metadata')
            ? DB::table('metadata')->where('page_id', $page->id)->first()
            : null;

        if ($request->wantsJson() || ! view()->exists('tenant.page')) {
            return response()->json([
                'success' => true,
                'page' => $page,
                'sections' => $sections,
                'metadata' => $metadata,
                'website' => [
                    'id' => $website->id,
                    'name' => $website->website_name,
                    'domain' => $website->domain,
                ],
            ]);
        }

        return response()->view('tenant.page', compact('page', 'sections', 'metadata', 'website'));
    }
}
