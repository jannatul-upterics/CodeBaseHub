<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    /**
     * Display a listing of media assets across websites.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $websiteId = $request->query('website_id', '');

        $query = Media::query()->with('website');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('file_name', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%");
            });
        }

        if ($websiteId !== '') {
            $query->where('website_id', (int) $websiteId);
        }

        $media = $query->latest('id')
            ->paginate(12)
            ->withQueryString();

        $websites = Website::orderBy('website_name')->get();

        return view('media.index', compact('media', 'search', 'websiteId', 'websites'));
    }

    /**
     * Safely stream/serve a media file with proper MIME type headers.
     */
    public function showFile(Media $media): StreamedResponse
    {
        if (! $media->file_path || ! Storage::disk('public')->exists($media->file_path)) {
            abort(404, 'File not found on storage disk.');
        }

        return Storage::disk('public')->response($media->file_path, $media->file_name, [
            'Content-Type' => $media->mime_type ?? 'application/octet-stream',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
