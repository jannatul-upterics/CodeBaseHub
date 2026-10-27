<?php

namespace App\Http\Controllers;

use App\Models\Metadata;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MetadataController extends Controller
{
    /**
     * Display a listing of SEO metadata entries across websites and pages.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $websiteId = $request->query('website_id', '');

        $query = Metadata::query()->with(['website', 'page']);

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('meta_title', 'like', "%{$search}%")
                    ->orWhere('meta_description', 'like', "%{$search}%")
                    ->orWhere('canonical_url', 'like', "%{$search}%");
            });
        }

        if ($websiteId !== '') {
            $query->where('website_id', (int) $websiteId);
        }

        $metadata = $query->latest('id')
            ->paginate(12)
            ->withQueryString();

        $websites = Website::orderBy('website_name')->get();

        return view('metadata.index', compact('metadata', 'search', 'websiteId', 'websites'));
    }
}
