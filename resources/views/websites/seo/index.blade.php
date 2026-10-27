@extends('layouts.dashboard')

@section('title', 'SEO & Metadata — ' . $website->website_name)
@section('breadcrumb', 'SEO & Metadata')

@section('content')
@include('websites._header', ['activeTab' => 'seo'])

<div class="row g-4">
    {{-- Left Column: Website-Level Defaults --}}
    <div class="col-12 col-lg-6">
        <div class="cbh-card mb-4">
            <div class="cbh-card-header">
                <div>
                    <h2 class="cbh-card-title">Website-Level Default SEO</h2>
                    <p class="cbh-card-subtitle">Global fallback meta tags applied to all pages lacking specific metadata</p>
                </div>
                @if ($websiteMeta)
                    <form method="POST" action="{{ route('websites.seo.destroy', [$website, $websiteMeta]) }}" class="m-0" onsubmit="return confirm('Reset site-level default SEO tags?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="cbh-btn cbh-btn-secondary cbh-btn-sm text-danger" title="Clear default metadata">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Reset
                        </button>
                    </form>
                @endif
            </div>

            <form method="POST" action="{{ route('websites.seo.save_website', $website) }}" novalidate>
                @csrf
                <div class="cbh-card-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="meta_title" class="cbh-form-label">Default Meta Title</label>
                            <input type="text"
                                   id="meta_title"
                                   name="meta_title"
                                   value="{{ old('meta_title', $websiteMeta?->meta_title ?? $website->website_name) }}"
                                   class="cbh-input @error('meta_title') is-invalid @enderror"
                                   placeholder="e.g. Acme Inc — Scalable Platform Solutions"
                                   maxlength="255">
                            <div class="form-text" style="font-size:0.75rem;">Recommended length: 50–60 characters.</div>
                            @error('meta_title')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="meta_description" class="cbh-form-label">Default Meta Description</label>
                            <textarea id="meta_description"
                                      name="meta_description"
                                      rows="3"
                                      class="cbh-input @error('meta_description') is-invalid @enderror"
                                      placeholder="Brief summary of your business, services, or website offerings..."
                                      maxlength="1000">{{ old('meta_description', $websiteMeta?->meta_description ?? $website->description) }}</textarea>
                            <div class="form-text" style="font-size:0.75rem;">Recommended length: 140–160 characters.</div>
                            @error('meta_description')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="canonical_url" class="cbh-form-label">Canonical URL Base</label>
                            <input type="url"
                                   id="canonical_url"
                                   name="canonical_url"
                                   value="{{ old('canonical_url', $websiteMeta?->canonical_url ?? 'https://' . $website->domain) }}"
                                   class="cbh-input font-monospace @error('canonical_url') is-invalid @enderror"
                                   placeholder="https://example.com">
                            @error('canonical_url')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="robots" class="cbh-form-label">Robots Indexing Directive</label>
                            <select id="robots" name="robots" class="cbh-select @error('robots') is-invalid @enderror">
                                @php $currentRobots = old('robots', $websiteMeta?->robots ?? 'index, follow'); @endphp
                                <option value="index, follow" {{ $currentRobots === 'index, follow' ? 'selected' : '' }}>index, follow (Allow search engines)</option>
                                <option value="noindex, follow" {{ $currentRobots === 'noindex, follow' ? 'selected' : '' }}>noindex, follow (Hide from search results)</option>
                                <option value="index, nofollow" {{ $currentRobots === 'index, nofollow' ? 'selected' : '' }}>index, nofollow (Index without link graph)</option>
                                <option value="noindex, nofollow" {{ $currentRobots === 'noindex, nofollow' ? 'selected' : '' }}>noindex, nofollow (Strict disallow)</option>
                            </select>
                            @error('robots')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="og_image" class="cbh-form-label">OpenGraph Social Share Image URL</label>
                            <input type="text"
                                   id="og_image"
                                   name="og_image"
                                   value="{{ old('og_image', $websiteMeta?->og_image) }}"
                                   class="cbh-input font-monospace @error('og_image') is-invalid @enderror"
                                   placeholder="https://.../og-banner.jpg">
                            @error('og_image')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="cbh-card-footer bg-light border-top p-3 d-flex justify-content-end">
                    <button type="submit" class="cbh-btn cbh-btn-primary">
                        <i class="bi bi-check-lg"></i>
                        Save Website SEO Defaults
                    </button>
                </div>
            </form>
        </div>

        {{-- Live Google Search Preview --}}
        <div class="cbh-card">
            <div class="cbh-card-header">
                <h3 class="cbh-card-title">
                    <i class="bi bi-google me-1 text-primary"></i>
                    SERP Snippet Preview
                </h3>
            </div>
            <div class="cbh-card-body p-4 bg-light">
                <div style="background:#fff; border-radius:8px; padding:1.25rem; border:1px solid var(--cbh-border); max-width:560px;">
                    <div style="font-size:0.75rem; color:#202124; display:flex; align-items:center; gap:0.35rem; margin-bottom:0.25rem;">
                        <span style="display:inline-block; width:16px; height:16px; border-radius:50%; background:#e2e8f0; text-align:center; font-size:10px; line-height:16px;">🌐</span>
                        <span class="font-monospace text-muted">{{ $websiteMeta?->canonical_url ?? 'https://' . $website->domain }}</span>
                    </div>
                    <div style="font-size:1.15rem; font-weight:500; color:#1a0dab; line-height:1.3; margin-bottom:0.35rem;">
                        {{ $websiteMeta?->meta_title ?? $website->website_name }}
                    </div>
                    <div style="font-size:0.84rem; color:#4d5156; line-height:1.5;">
                        {{ $websiteMeta?->meta_description ?? ($website->description ?? 'No meta description provided for this website.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Column: Page-Specific SEO Metadata --}}
    <div class="col-12 col-lg-6">
        <div class="cbh-card">
            <div class="cbh-card-header">
                <div>
                    <h2 class="cbh-card-title">Page-Specific SEO Overrides</h2>
                    <p class="cbh-card-subtitle">Fine-tune meta tags for individual pages</p>
                </div>
            </div>

            <div class="p-4 border-bottom bg-light">
                <form method="GET" action="{{ route('websites.seo.index', $website) }}">
                    <label for="page_id_select" class="cbh-form-label">Select Page to Configure</label>
                    <div class="d-flex gap-2">
                        <select id="page_id_select" name="page_id" class="cbh-select flex-grow-1" onchange="this.form.submit()">
                            <option value="">-- Choose a page to edit specific SEO tags --</option>
                            @foreach ($pages as $p)
                                <option value="{{ $p->id }}" {{ $selectedPage && $selectedPage->id === $p->id ? 'selected' : '' }}>
                                    {{ $p->title }} (/{{ $p->slug }}) {{ $p->metadata->count() > 0 ? '✓ Custom SEO' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <noscript>
                            <button type="submit" class="cbh-btn cbh-btn-secondary">Load</button>
                        </noscript>
                    </div>
                </form>
            </div>

            @if ($selectedPage)
                <form method="POST" action="{{ route('websites.seo.save_page', [$website, $selectedPage]) }}" novalidate>
                    @csrf
                    <div class="cbh-card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div>
                                <h4 class="fw-bold mb-0 text-dark" style="font-size:1rem;">{{ $selectedPage->title }}</h4>
                                <span class="font-monospace text-muted small">/{{ $selectedPage->slug }}</span>
                            </div>
                            @if ($selectedPageMeta)
                                <button type="button"
                                        class="cbh-btn cbh-btn-secondary cbh-btn-sm text-danger"
                                        onclick="document.getElementById('deletePageMetaForm').submit();"
                                        title="Remove page-level custom metadata">
                                    <i class="bi bi-trash"></i>
                                    Revert to Site Default
                                </button>
                            @endif
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="page_meta_title" class="cbh-form-label">Page Meta Title</label>
                                <input type="text"
                                       id="page_meta_title"
                                       name="meta_title"
                                       value="{{ old('meta_title', $selectedPageMeta?->meta_title ?? $selectedPage->title . ' — ' . $website->website_name) }}"
                                       class="cbh-input"
                                       placeholder="Page-specific title"
                                       maxlength="255">
                            </div>

                            <div class="col-12">
                                <label for="page_meta_description" class="cbh-form-label">Page Meta Description</label>
                                <textarea id="page_meta_description"
                                          name="meta_description"
                                          rows="3"
                                          class="cbh-input"
                                          placeholder="Page-specific search description..."
                                          maxlength="1000">{{ old('meta_description', $selectedPageMeta?->meta_description) }}</textarea>
                            </div>

                            <div class="col-12">
                                <label for="page_canonical_url" class="cbh-form-label">Canonical URL</label>
                                <input type="url"
                                       id="page_canonical_url"
                                       name="canonical_url"
                                       value="{{ old('canonical_url', $selectedPageMeta?->canonical_url ?? 'https://' . $website->domain . '/' . $selectedPage->slug) }}"
                                       class="cbh-input font-monospace"
                                       placeholder="https://example.com/page">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="page_robots" class="cbh-form-label">Robots Directive</label>
                                <select id="page_robots" name="robots" class="cbh-select">
                                    @php $pgRobots = old('robots', $selectedPageMeta?->robots ?? 'index, follow'); @endphp
                                    <option value="index, follow" {{ $pgRobots === 'index, follow' ? 'selected' : '' }}>index, follow</option>
                                    <option value="noindex, follow" {{ $pgRobots === 'noindex, follow' ? 'selected' : '' }}>noindex, follow</option>
                                    <option value="index, nofollow" {{ $pgRobots === 'index, nofollow' ? 'selected' : '' }}>index, nofollow</option>
                                    <option value="noindex, nofollow" {{ $pgRobots === 'noindex, nofollow' ? 'selected' : '' }}>noindex, nofollow</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="page_og_image" class="cbh-form-label">OpenGraph Social Banner URL</label>
                                <input type="text"
                                       id="page_og_image"
                                       name="og_image"
                                       value="{{ old('og_image', $selectedPageMeta?->og_image) }}"
                                       class="cbh-input font-monospace"
                                       placeholder="https://.../page-banner.jpg">
                            </div>
                        </div>
                    </div>

                    <div class="cbh-card-footer bg-light border-top p-3 d-flex justify-content-end">
                        <button type="submit" class="cbh-btn cbh-btn-primary">
                            <i class="bi bi-check-lg"></i>
                            Save Page SEO
                        </button>
                    </div>
                </form>

                @if ($selectedPageMeta)
                    <form id="deletePageMetaForm"
                          method="POST"
                          action="{{ route('websites.seo.destroy', [$website, $selectedPageMeta]) }}"
                          style="display:none;">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            @else
                <div class="p-4 text-center">
                    <x-empty-state
                        icon="bi-search-heart"
                        title="No page selected"
                        description="Choose a page from the dropdown menu above to customize its SEO title, description, and canonical path."
                    />
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
