@extends('layouts.dashboard')

@section('title', $page->title . ' — Central Page Definition')
@section('breadcrumb', 'Pages / ' . $page->title)

@section('content')
<div class="cbh-page-header">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('pages.index') }}" class="cbh-btn cbh-btn-secondary cbh-btn-icon" title="Back to Pages">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="cbh-page-title">{{ $page->title }}</h1>
                @if ($page->is_homepage)
                    <span class="cbh-badge cbh-badge-success">Homepage</span>
                @endif
                <span class="cbh-badge cbh-badge-neutral">{{ ucfirst($page->page_type) }}</span>
                @if ($page->is_template)
                    <span class="cbh-badge cbh-badge-primary">Reusable Template</span>
                @endif
            </div>
            <p class="cbh-page-subtitle">
                Central page definition stored in CodeBaseHub &middot; Slug: <span class="font-monospace text-primary">/{{ $page->slug }}</span>
            </p>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        @if ($page->website)
            <a href="{{ route('websites.pages.show', [$page->website, $page]) }}" class="cbh-btn cbh-btn-primary">
                <i class="bi bi-gear"></i>
                Manage in Website Workspace
            </a>
        @endif
        <a href="{{ route('pages.index') }}" class="cbh-btn cbh-btn-secondary">
            <i class="bi bi-list"></i>
            All Pages
        </a>
    </div>
</div>

<div class="row g-4">
    {{-- Main Column: Content & Sections --}}
    <div class="col-12 col-lg-8">
        {{-- Page Content / Intro --}}
        @if ($page->content)
            <div class="cbh-card mb-4">
                <div class="cbh-card-header">
                    <h2 class="cbh-card-title">Page Overview</h2>
                </div>
                <div class="cbh-card-body">
                    <p class="mb-0" style="font-size:0.95rem;line-height:1.6;color:var(--cbh-neutral-700);">
                        {{ $page->content }}
                    </p>
                </div>
            </div>
        @endif

        {{-- Sections --}}
        <div class="cbh-card">
            <div class="cbh-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="cbh-card-title">
                        <i class="bi bi-layers me-2" style="color:var(--cbh-brand-500);"></i>
                        Content Sections ({{ $page->sections->count() }})
                    </h2>
                    <p class="cbh-card-subtitle">Stored structured sections in execution sequence order.</p>
                </div>
            </div>
            <div class="cbh-card-body p-0">
                @if ($page->sections->isNotEmpty())
                    <div class="list-group list-group-flush">
                        @foreach ($page->sections as $sec)
                            <div class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-light text-dark border font-monospace">#{{ $sec->sort_order }}</span>
                                        <strong style="color:var(--cbh-neutral-800);font-size:0.92rem;">{{ $sec->section_name }}</strong>
                                        <span class="cbh-badge cbh-badge-neutral font-monospace">{{ $sec->section_type }}</span>
                                    </div>
                                    @if ($sec->status === 1)
                                        <span class="cbh-badge cbh-badge-success" style="font-size:0.7rem;">Active</span>
                                    @else
                                        <span class="cbh-badge cbh-badge-neutral" style="font-size:0.7rem;">Inactive</span>
                                    @endif
                                </div>

                                <div class="p-2 rounded bg-light border" style="font-size:0.8rem;max-height:220px;overflow-y:auto;">
                                    <pre class="mb-0 font-monospace text-dark" style="white-space:pre-wrap;word-break:break-word;">{{ is_array($sec->content) ? json_encode($sec->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $sec->content }}</pre>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-layers-half" style="font-size:1.5rem;"></i>
                        <p class="mt-2 mb-0" style="font-size:0.85rem;">No sections stored for this page.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Side Column: Metadata & Specifications --}}
    <div class="col-12 col-lg-4">
        {{-- Specifications Card --}}
        <div class="cbh-card mb-4">
            <div class="cbh-card-header">
                <h2 class="cbh-card-title">Specifications</h2>
            </div>
            <div class="cbh-card-body">
                <div class="mb-3">
                    <span class="text-muted small d-block">Website Owner:</span>
                    <strong style="color:var(--cbh-neutral-800);">
                        {{ $page->website?->website_name ?? 'Central Template Library (Universal)' }}
                    </strong>
                </div>
                <div class="mb-3">
                    <span class="text-muted small d-block">Template Association:</span>
                    <strong style="color:var(--cbh-neutral-800);">
                        {{ $page->template?->template_name ?? 'Vocabot AI Voice Agent' }}
                    </strong>
                </div>
                <div class="mb-3">
                    <span class="text-muted small d-block">Sync Hash:</span>
                    <code class="small text-muted font-monospace" style="word-break:break-all;">
                        {{ $page->sync_hash ?? 'N/A' }}
                    </code>
                </div>
            </div>
        </div>

        {{-- SEO Metadata Card --}}
        <div class="cbh-card">
            <div class="cbh-card-header">
                <h2 class="cbh-card-title">
                    <i class="bi bi-search-heart me-2" style="color:var(--cbh-brand-500);"></i>
                    SEO &amp; OpenGraph
                </h2>
            </div>
            <div class="cbh-card-body">
                <div class="mb-3">
                    <span class="text-muted small d-block">Meta Title:</span>
                    <div style="font-size:0.875rem;color:var(--cbh-neutral-800);">{{ $page->meta_title ?? '—' }}</div>
                </div>
                <div class="mb-3">
                    <span class="text-muted small d-block">Meta Description:</span>
                    <div style="font-size:0.84rem;color:var(--cbh-neutral-600);">{{ $page->meta_description ?? '—' }}</div>
                </div>
                <div class="mb-3">
                    <span class="text-muted small d-block">Canonical URL:</span>
                    <div class="font-monospace small text-primary">{{ $page->canonical_url ?? '—' }}</div>
                </div>
                <div>
                    <span class="text-muted small d-block">OG Image:</span>
                    <div class="font-monospace small text-secondary">{{ $page->og_image ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
