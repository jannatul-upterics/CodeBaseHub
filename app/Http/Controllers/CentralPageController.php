<?php

namespace App\Http\Controllers;

use App\Http\Middleware\TenantConnection;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CentralPageController extends Controller
{
    /**
     * Display a page fetched from the central CodeBaseHub database.
     * Only published pages (status = 1) are displayed.
     */
    public function show(string $slug, Request $request): Response
    {
        try {
            $connection = TenantConnection::getCentralConnection();
            $page = Page::on($connection)
                ->where('slug', $slug)
                ->where('status', 1)
                ->with(['sections', 'metadata', 'website', 'template'])
                ->first();
        } catch (\Throwable $e) {
            Log::error('Failed to fetch central page', ['slug' => $slug, 'error' => $e->getMessage()]);
            abort(404, 'Page not found');
        }

        if (! $page) {
            abort(404, 'Page not found');
        }

        return response()->view('pages.show', ['page' => $page]);
    }
}
