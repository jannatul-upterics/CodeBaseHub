<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMediaRequest;
use App\Models\Media;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WebsiteMediaController extends Controller
{
    /**
     * Display a listing of media assets for this website.
     */
    public function index(Website $website, Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $fileType = $request->query('type', '');

        $query = $website->media();

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('file_name', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%");
            });
        }

        if ($fileType !== '') {
            $query->where('file_type', $fileType);
        }

        $media = $query->latest('id')
            ->paginate(12)
            ->withQueryString();

        $totalCount = $website->media()->count();
        $imageCount = $website->media()->where('file_type', 'image')->count();
        $docCount = $website->media()->where('file_type', 'document')->count();
        $totalBytes = (int) $website->media()->sum('file_size');

        return view('websites.media.index', compact(
            'website',
            'media',
            'search',
            'fileType',
            'totalCount',
            'imageCount',
            'docCount',
            'totalBytes'
        ));
    }

    /**
     * Handle file upload and store media record.
     */
    public function store(Website $website, StoreMediaRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $altText = $request->input('alt_text');

        // Extract verified server mime type and size
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->guessExtension() ?? $file->getClientOriginalExtension();

        // Sanitize base name
        $sanitizedName = Str::slug($originalName).'.'.$extension;

        // Categorize file type
        $imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $fileType = in_array($mimeType, $imageMimes, true) ? 'image' : 'document';

        // Store file safely under websites/{website_id}/media/
        $storedPath = $file->store("websites/{$website->id}/media", 'public');

        $website->media()->create([
            'file_name' => $sanitizedName,
            'file_path' => $storedPath,
            'file_type' => $fileType,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'alt_text' => $altText ? strip_tags((string) $altText) : null,
            'status' => 1,
        ]);

        return redirect()
            ->route('websites.media.index', $website)
            ->with('success', "File '{$sanitizedName}' uploaded successfully.");
    }

    /**
     * Update an existing media asset (alt text, file name, status).
     */
    public function update(Website $website, Media $media, Request $request): RedirectResponse
    {
        if ($media->website_id !== $website->id) {
            abort(404);
        }

        $validated = $request->validate([
            'file_name' => ['required', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
        ]);

        $media->update([
            'file_name' => strip_tags($validated['file_name']),
            'alt_text' => isset($validated['alt_text']) ? strip_tags($validated['alt_text']) : null,
            'status' => (int) ($validated['status'] ?? 1),
        ]);

        return redirect()
            ->route('websites.media.index', $website)
            ->with('success', "Media '{$media->file_name}' updated successfully.");
    }

    /**
     * Delete media asset and remove file from storage.
     */
    public function destroy(Website $website, Media $media): RedirectResponse
    {
        if ($media->website_id !== $website->id) {
            abort(404);
        }

        $name = $media->file_name;

        // Safe storage cleanup
        if ($media->file_path && Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }

        $media->delete();

        return redirect()
            ->route('websites.media.index', $website)
            ->with('success', "Media file '{$name}' permanently removed.");
    }
}
