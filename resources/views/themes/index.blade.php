@extends('layouts.dashboard')

@section('title', 'Themes Catalog')
@section('breadcrumb', 'Themes')

@section('content')
<div class="cbh-page-header">
    <div>
        <h1 class="cbh-page-title">
            Themes
            <span class="cbh-badge cbh-badge-primary ms-1" style="vertical-align: middle;">Design Systems</span>
        </h1>
        <p class="cbh-page-subtitle">Visual styling systems, typography kits, and component themes for platform websites.</p>
    </div>
    <a href="{{ route('themes.create') }}" class="cbh-btn cbh-btn-primary">
        <i class="bi bi-palette-fill"></i>
        New Theme
    </a>
</div>

{{-- Search & Filter Toolbar --}}
<div class="cbh-card mb-4">
    <div class="cbh-card-body p-3">
        <form method="GET" action="{{ route('themes.index') }}" class="cbh-toolbar">
            <div class="cbh-search-wrap flex-grow-1" style="min-width: 240px;">
                <i class="bi bi-search cbh-search-icon"></i>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       class="cbh-input cbh-search-input"
                       placeholder="Search theme by name, slug, or description…"
                       aria-label="Search themes">
            </div>

            <select name="status" class="cbh-select" style="min-width: 140px; max-width: 180px;" onchange="this.form.submit()" aria-label="Filter by status">
                <option value="">All Statuses</option>
                <option value="1" {{ ($status ?? '') === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ ($status ?? '') === '0' ? 'selected' : '' }}>Inactive</option>
            </select>

            <button type="submit" class="cbh-btn cbh-btn-primary">
                <i class="bi bi-search"></i>
                Search
            </button>

            @if ($search !== '' || ($status ?? '') !== '')
                <a href="{{ route('themes.index') }}" class="cbh-btn cbh-btn-secondary">
                    <i class="bi bi-x-circle"></i>
                    Clear
                </a>
            @endif
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    @forelse ($themes as $theme)
        <div class="col-12 col-md-6 col-lg-4 col-xl-3">
            <div class="cbh-card h-100 d-flex flex-column">
                @if ($theme->preview_image)
                    <div style="height: 140px; overflow: hidden; background: #f1f5f9; border-top-left-radius: 10px; border-top-right-radius: 10px;">
                        <img src="{{ $theme->preview_image }}"
                             alt="{{ $theme->theme_name }} Preview"
                             style="width: 100%; height: 100%; object-fit: cover;"
                             onerror="this.onerror=null; this.parentElement.style.display='none';">
                    </div>
                @endif
                <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between">
                    <span class="cbh-badge cbh-badge-primary">v{{ $theme->version ?? '1.0.0' }}</span>
                    <div class="d-flex align-items-center gap-1">
                        @if ($theme->status === 1)
                            <span class="cbh-badge cbh-badge-success">Active</span>
                        @else
                            <span class="cbh-badge cbh-badge-neutral">Inactive</span>
                        @endif
                        <span class="cbh-badge cbh-badge-neutral">{{ $theme->websites_count }} {{ Str::plural('site', $theme->websites_count) }}</span>
                    </div>
                </div>
                <div class="cbh-card-body flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="cbh-stat-icon cbh-stat-icon-amber" style="width: 34px; height: 34px; font-size: 1rem; border-radius: 6px;">
                            <i class="bi bi-palette-fill"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0" style="font-size: 0.985rem;">{{ $theme->theme_name }}</h4>
                            <span class="font-monospace text-muted" style="font-size: 0.72rem;">{{ $theme->slug }}</span>
                        </div>
                    </div>
                    <p class="text-muted mb-0" style="font-size: 0.82rem; line-height: 1.45;">
                        {{ $theme->description ?? 'Pre-styled multi-page theme architecture with integrated components.' }}
                    </p>
                </div>
                <div class="cbh-card-footer bg-white border-top d-flex justify-content-between align-items-center">
                    <a href="{{ route('themes.show', $theme) }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm">
                        View Details
                    </a>
                    <a href="{{ route('themes.edit', $theme) }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm" title="Edit Theme">
                        <i class="bi bi-pencil"></i>
                        Edit
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <x-empty-state
                icon="bi-palette"
                title="No themes found"
                description="No theme records match your query. Create a new theme to start styling websites."
                actionText="New Theme"
                actionUrl="{{ route('themes.create') }}"
            />
        </div>
    @endforelse
</div>

@if ($themes->hasPages())
    <div class="d-flex justify-content-center">
        {{ $themes->links() }}
    </div>
@endif
@endsection
