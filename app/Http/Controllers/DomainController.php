<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DomainController extends Controller
{
    /**
     * Display a listing of all registered domains across websites.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', '');

        $query = Domain::query()->with('website');

        if ($search !== '') {
            $query->where('domain_name', 'like', "%{$search}%");
        }

        if ($status === 'verified') {
            $query->whereNotNull('verified_at');
        } elseif ($status === 'unverified') {
            $query->whereNull('verified_at');
        }

        $domains = $query->latest('id')
            ->paginate(12)
            ->withQueryString();

        $websites = Website::orderBy('website_name')->get();

        return view('domains.index', compact('domains', 'search', 'status', 'websites'));
    }
}
