@extends('layouts.dashboard')

@section('title', 'SEO & Metadata')
@section('breadcrumb', 'SEO & Metadata')

@section('content')
<div class="cbh-page-header">
    <div>
        <h1 class="cbh-page-title">
            SEO &amp; Metadata
            <span class="cbh-badge cbh-badge-primary ms-1" style="vertical-align: middle;">Search Engine Optimization</span>
        </h1>
        <p class="cbh-page-subtitle">Canonical URLs, meta page titles, descriptions, crawler robots directives, and social share tags.</p>
    </div>
</div>

{{-- Filter Toolbar --}}
<div class="cbh-card mb-4">
    <div class="cbh-card-body p-3">
        <form method="GET" action="{{ route('metadata.index') }}" class="cbh-toolbar">
            <div class="cbh-search-wrap flex-grow-1" style="min-width: 220px;">
                <i class="bi bi-search cbh-search-icon"></i>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       class="cbh-input cbh-search-input"
                       placeholder="Search title, description, or canonical URL…"
                       aria-label="Search metadata">
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
                <a href="{{ route('metadata.index') }}" class="cbh-btn cbh-btn-secondary">
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
            <h2 class="cbh-card-title">SEO &amp; Social Meta Records</h2>
            <p class="cbh-card-subtitle">
                Showing {{ $metadata->firstItem() ?? 0 }}–{{ $metadata->lastItem() ?? 0 }} of {{ $metadata->total() }} {{ Str::plural('record', $metadata->total()) }}
            </p>
        </div>
    </div>

    <div class="cbh-table-responsive">
        <table class="cbh-table">
            <thead>
                <tr>
                    <th>Website Property</th>
                    <th>Page Scope</th>
                    <th>Meta Title</th>
                    <th>Canonical URL</th>
                    <th>Robots Directive</th>
                    <th>Meta Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($metadata as $item)
                    <tr>
                        <td>
                            @if ($item->website)
                                <a href="{{ route('websites.show', $item->website) }}" class="fw-semibold text-decoration-none" style="color: var(--cbh-brand-600);">
                                    {{ $item->website->website_name }}
                                </a>
                            @else
                                <span class="text-muted">Global</span>
                            @endif
                        </td>
                        <td>
                            @if ($item->page)
                                <span class="cbh-badge cbh-badge-neutral">{{ $item->page->title }}</span>
                            @else
                                <span class="cbh-badge cbh-badge-primary">Site-Wide Default</span>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold text-dark" style="font-size: 0.84rem;">
                                {{ $item->meta_title ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <span class="font-monospace text-secondary" style="font-size: 0.785rem;">
                                {{ $item->canonical_url ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <code class="text-secondary" style="font-size: 0.75rem;">
                                {{ $item->robots ?? 'index, follow' }}
                            </code>
                        </td>
                        <td>
                            <span class="text-muted" style="font-size: 0.8rem;">
                                {{ Str::limit($item->meta_description, 50) ?? '—' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-empty-state
                                icon="bi-search-heart"
                                title="No metadata found"
                                description="No SEO metadata records match the selected criteria."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($metadata->hasPages())
        <div class="cbh-card-footer">
            {{ $metadata->links() }}
        </div>
    @endif
</div>
@endsection
