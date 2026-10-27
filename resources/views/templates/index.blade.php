@extends('layouts.dashboard')

@section('title', 'Templates Catalog')
@section('breadcrumb', 'Templates')

@section('content')
<div class="cbh-page-header">
    <div>
        <h1 class="cbh-page-title">
            Templates
            <span class="cbh-badge cbh-badge-primary ms-1" style="vertical-align: middle;">Layout Blueprints</span>
        </h1>
        <p class="cbh-page-subtitle">Production wireframes and website layout blueprints for rapid client provisioning.</p>
    </div>
    <a href="{{ route('templates.create') }}" class="cbh-btn cbh-btn-primary">
        <i class="bi bi-layout-text-window-reverse"></i>
        New Template
    </a>
</div>

{{-- Search & Filter Toolbar --}}
<div class="cbh-card mb-4">
    <div class="cbh-card-body p-3">
        <form method="GET" action="{{ route('templates.index') }}" class="cbh-toolbar">
            <div class="cbh-search-wrap flex-grow-1" style="min-width: 240px;">
                <i class="bi bi-search cbh-search-icon"></i>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       class="cbh-input cbh-search-input"
                       placeholder="Search template by name, slug, or description…"
                       aria-label="Search templates">
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
                <a href="{{ route('templates.index') }}" class="cbh-btn cbh-btn-secondary">
                    <i class="bi bi-x-circle"></i>
                    Clear
                </a>
            @endif
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    @forelse ($templates as $template)
        <div class="col-12 col-md-6 col-lg-4">
            <div class="cbh-card h-100 d-flex flex-column">
                @if ($template->preview_image)
                    <div style="height: 150px; overflow: hidden; background: #f8fafc; border-top-left-radius: 10px; border-top-right-radius: 10px;">
                        <img src="{{ $template->preview_image }}"
                             alt="{{ $template->template_name }} Preview"
                             style="width: 100%; height: 100%; object-fit: cover;"
                             onerror="this.onerror=null; this.parentElement.style.display='none';">
                    </div>
                @endif
                <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between">
                    <span class="cbh-badge cbh-badge-neutral font-monospace">v{{ $template->version ?? '1.0.0' }}</span>
                    @if ($template->status === 1)
                        <span class="cbh-badge cbh-badge-success">Ready to Deploy</span>
                    @else
                        <span class="cbh-badge cbh-badge-neutral">Draft</span>
                    @endif
                </div>
                <div class="cbh-card-body flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="cbh-stat-icon cbh-stat-icon-teal" style="width: 34px; height: 34px; font-size: 1rem; border-radius: 6px;">
                            <i class="bi bi-layout-text-window-reverse"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0" style="font-size: 0.985rem;">{{ $template->template_name }}</h4>
                            <span class="font-monospace text-muted" style="font-size: 0.72rem;">{{ $template->slug }}</span>
                        </div>
                    </div>
                    <p class="text-muted mb-0" style="font-size: 0.82rem; line-height: 1.45;">
                        {{ $template->description ?? 'Clean responsive layout template optimized for high conversion and accessible standards.' }}
                    </p>
                </div>
                <div class="cbh-card-footer bg-white border-top d-flex justify-content-between align-items-center">
                    <a href="{{ route('templates.show', $template) }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm">
                        View Blueprint
                    </a>
                    <a href="{{ route('templates.edit', $template) }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm" title="Edit Template">
                        <i class="bi bi-pencil"></i>
                        Edit
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <x-empty-state
                icon="bi-layout-text-window-reverse"
                title="No templates found"
                description="No layout templates match your query. Create a template blueprint to enrich your catalog."
                actionText="New Template"
                actionUrl="{{ route('templates.create') }}"
            />
        </div>
    @endforelse
</div>

@if ($templates->hasPages())
    <div class="d-flex justify-content-center">
        {{ $templates->links() }}
    </div>
@endif
@endsection
