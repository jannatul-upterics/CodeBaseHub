<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebsitePageSectionController extends Controller
{
    /**
     * Store a newly created page section.
     */
    public function store(Website $website, Page $page, Request $request): RedirectResponse
    {
        if ($page->website_id !== $website->id) {
            abort(404);
        }

        $validated = $request->validate([
            'section_name' => ['required', 'string', 'max:255'],
            'section_type' => ['required', 'string', 'max:50'],
            'content_format' => ['nullable', 'string', 'in:fields,json'],
            'content_json' => ['nullable', 'string'],
            'heading' => ['nullable', 'string', 'max:255'],
            'subheading' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
        ]);

        $content = $this->buildContentArray($validated, $request);

        $maxSortOrder = (int) $page->sections()->max('sort_order');

        $page->sections()->create([
            'section_name' => $validated['section_name'],
            'section_type' => $validated['section_type'],
            'content' => $content,
            'sort_order' => $maxSortOrder + 1,
            'status' => (int) ($validated['status'] ?? 1),
        ]);

        return redirect()
            ->route('websites.pages.show', [$website, $page])
            ->with('success', "Section '{$validated['section_name']}' added successfully.");
    }

    /**
     * Update an existing page section.
     */
    public function update(Website $website, Page $page, PageSection $section, Request $request): RedirectResponse
    {
        if ($page->website_id !== $website->id || $section->page_id !== $page->id) {
            abort(404);
        }

        $validated = $request->validate([
            'section_name' => ['required', 'string', 'max:255'],
            'section_type' => ['required', 'string', 'max:50'],
            'content_format' => ['nullable', 'string', 'in:fields,json'],
            'content_json' => ['nullable', 'string'],
            'heading' => ['nullable', 'string', 'max:255'],
            'subheading' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'integer', 'in:0,1'],
        ]);

        $content = $this->buildContentArray($validated, $request);

        $section->update([
            'section_name' => $validated['section_name'],
            'section_type' => $validated['section_type'],
            'content' => $content,
            'status' => (int) ($validated['status'] ?? 1),
        ]);

        return redirect()
            ->route('websites.pages.show', [$website, $page])
            ->with('success', "Section '{$section->section_name}' updated successfully.");
    }

    /**
     * Delete an existing page section.
     */
    public function destroy(Website $website, Page $page, PageSection $section): RedirectResponse
    {
        if ($page->website_id !== $website->id || $section->page_id !== $page->id) {
            abort(404);
        }

        $name = $section->section_name;
        $section->delete();

        return redirect()
            ->route('websites.pages.show', [$website, $page])
            ->with('success', "Section '{$name}' deleted.");
    }

    /**
     * Reorder a section (move up or move down).
     */
    public function reorder(Website $website, Page $page, Request $request): RedirectResponse
    {
        if ($page->website_id !== $website->id) {
            abort(404);
        }

        $validated = $request->validate([
            'section_id' => ['required', 'integer', 'exists:page_sections,id'],
            'direction' => ['required', 'string', 'in:up,down'],
        ]);

        /** @var PageSection $currentSection */
        $currentSection = $page->sections()->findOrFail($validated['section_id']);
        $direction = $validated['direction'];

        DB::transaction(function () use ($page, $currentSection, $direction): void {
            if ($direction === 'up') {
                $targetSection = $page->sections()
                    ->where('sort_order', '<', $currentSection->sort_order)
                    ->orderByDesc('sort_order')
                    ->first();
            } else {
                $targetSection = $page->sections()
                    ->where('sort_order', '>', $currentSection->sort_order)
                    ->orderBy('sort_order')
                    ->first();
            }

            if ($targetSection) {
                $currentOrder = $currentSection->sort_order;
                $targetOrder = $targetSection->sort_order;

                // Swap sort orders
                $currentSection->update(['sort_order' => $targetOrder]);
                $targetSection->update(['sort_order' => $currentOrder]);
            }
        });

        return redirect()
            ->route('websites.pages.show', [$website, $page])
            ->with('success', 'Section order updated.');
    }

    /**
     * Parse and structure the incoming content into an array.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function buildContentArray(array $validated, Request $request): array
    {
        if (($validated['content_format'] ?? '') === 'json' && ! empty($validated['content_json'])) {
            $decoded = json_decode((string) $validated['content_json'], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Default structured content builder
        $content = [];

        if (! empty($validated['heading'])) {
            $content['heading'] = strip_tags((string) $validated['heading']);
        }

        if (! empty($validated['subheading'])) {
            $content['subheading'] = strip_tags((string) $validated['subheading']);
        }

        if (! empty($validated['body'])) {
            // Store cleaned text content
            $content['body'] = (string) $validated['body'];
        }

        if (! empty($validated['button_text'])) {
            $content['button_text'] = strip_tags((string) $validated['button_text']);
        }

        if (! empty($validated['button_url'])) {
            $content['button_url'] = filter_var($validated['button_url'], FILTER_SANITIZE_URL);
        }

        return $content;
    }
}
