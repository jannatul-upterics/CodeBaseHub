@extends('layouts.dashboard')

@section('title', 'Website Pages')
@section('breadcrumb', 'Pages')

@section('content')
<div class="cbh-page-header">
    <div>
        <h1 class="cbh-page-title">
            Pages
            <span class="cbh-badge cbh-badge-primary ms-1" style="vertical-align: middle;">Site Hierarchy</span>
        </h1>
        <p class="cbh-page-subtitle">Hierarchical pages, homepages, routing slugs, and content section compositions.</p>
    </div>
</div>

{{-- Filter by Website --}}
<div class="cbh-card mb-4">
    <div class="cbh-card-body p-3">
        <form method="GET" action="{{ route('pages.index') }}" class="cbh-toolbar">
            <div class="cbh-search-wrap flex-grow-1" style="min-width: 220px;">
                <i class="bi bi-search cbh-search-icon"></i>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       class="cbh-input cbh-search-input"
                       placeholder="Search page title or slug…"
                       aria-label="Search pages">
            </div>

            <select name="website_id" class="cbh-select" style="min-width: 200px; max-width: 260px;" onchange="this.form.submit()">
                <option value="">All Websites</option>
                @foreach ($websites as $w)
                    <option value="{{ $w->id }}" {{ (string)$websiteId === (string)$w->id ? 'selected' : '' }}>
                        {{ $w->website_name }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="cbh-btn cbh-btn-primary">
                <i class="bi bi-search"></i>
                Filter
            </button>

            @if ($search !== '' || $websiteId !== '')
                <a href="{{ route('pages.index') }}" class="cbh-btn cbh-btn-secondary">
                    <i class="bi bi-x-circle"></i>
                    Clear
                </a>
            @endif
        </form>
    </div>
</div>

<div class="cbh-card">
    <div class="cbh-card-header">
        <div>
            <h2 class="cbh-card-title">Pages Directory</h2>
            <p class="cbh-card-subtitle">
                Showing {{ $pages->firstItem() ?? 0 }}–{{ $pages->lastItem() ?? 0 }} of {{ $pages->total() }} {{ Str::plural('page', $pages->total()) }}
            </p>
        </div>
    </div>

    <div class="cbh-table-responsive">
        <table class="cbh-table">
            <thead>
                <tr>
                    <th>Page Title</th>
                    <th>Slug Path</th>
                    <th>Website Property</th>
                    <th>Page Type</th>
                    <th>Sections</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pages as $page)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-text text-primary" style="font-size: 1.1rem;"></i>
                                <div>
                                    <span class="fw-bold text-dark">{{ $page->title }}</span>
                                    @if ($page->is_homepage)
                                        <span class="cbh-badge cbh-badge-success ms-1">
                                            <i class="bi bi-house-door-fill me-1" style="font-size: 0.38rem;"></i>
                                            Homepage
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="font-monospace text-secondary" style="font-size: 0.82rem;">/{{ $page->slug }}</span>
                        </td>
                        <td>
                            @if ($page->website)
                                <a href="{{ route('websites.show', $page->website) }}" class="fw-semibold text-decoration-none" style="color: var(--cbh-brand-600);">
                                    {{ $page->website->website_name }}
                                </a>
                            @else
                                <span class="text-muted">Unlinked</span>
                            @endif
                        </td>
                        <td>
                            <span class="cbh-badge cbh-badge-neutral text-capitalize">{{ $page->page_type }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ $page->sections_count }} {{ Str::plural('section', $page->sections_count) }}
                            </span>
                        </td>
                        <td>
                            @if ($page->status === 1)
                                <span class="text-success fw-semibold" style="font-size: 0.82rem;">Published</span>
                            @else
                                <span class="text-muted" style="font-size: 0.82rem;">Draft</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-empty-state
                                icon="bi-file-earmark-text"
                                title="No pages found"
                                description="No pages match the selected criteria."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($pages->hasPages())
        <div class="cbh-card-footer">
            {{ $pages->links() }}
        </div>
    @endif
</div>
@endsection
