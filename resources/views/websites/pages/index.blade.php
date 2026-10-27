@extends('layouts.dashboard')

@section('title', 'Pages — ' . $website->website_name)
@section('breadcrumb', 'Pages')

@section('content')
@include('websites._header', ['activeTab' => 'pages'])

{{-- Metric Badges & Actions --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
        <div class="cbh-card p-3 d-flex align-items-center gap-3">
            <div style="width:42px;height:42px;border-radius:10px;background:var(--cbh-brand-50);color:var(--cbh-brand-600);display:flex;align-items:center;justify-content:center;font-size:1.25rem;">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            <div>
                <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--cbh-text-muted);">Total Pages</div>
                <div style="font-size:1.35rem;font-weight:700;color:var(--cbh-text-primary);">{{ $totalPages }}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="cbh-card p-3 d-flex align-items-center gap-3">
            <div style="width:42px;height:42px;border-radius:10px;background:var(--cbh-success-bg);color:var(--cbh-success-600);display:flex;align-items:center;justify-content:center;font-size:1.25rem;">
                <i class="bi bi-check-circle"></i>
            </div>
            <div>
                <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--cbh-text-muted);">Published</div>
                <div style="font-size:1.35rem;font-weight:700;color:var(--cbh-text-primary);">{{ $publishedPages }}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="cbh-card p-3 d-flex align-items-center gap-3">
            <div style="width:42px;height:42px;border-radius:10px;background:{{ $hasHomepage ? 'var(--cbh-brand-50)' : 'var(--cbh-warning-bg)' }};color:{{ $hasHomepage ? 'var(--cbh-brand-600)' : 'var(--cbh-warning-600)' }};display:flex;align-items:center;justify-content:center;font-size:1.25rem;">
                <i class="bi bi-house-door"></i>
            </div>
            <div>
                <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--cbh-text-muted);">Homepage Status</div>
                <div style="font-size:0.95rem;font-weight:700;color:var(--cbh-text-primary);">
                    {{ $hasHomepage ? 'Configured' : 'Missing Homepage' }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Search & Create Toolbar --}}
<div class="cbh-card mb-4">
    <div class="cbh-card-body p-3">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <form method="GET" action="{{ route('websites.pages.index', $website) }}" class="cbh-toolbar flex-grow-1">
                <div class="cbh-search-wrap flex-grow-1" style="min-width: 220px;">
                    <i class="bi bi-search cbh-search-icon"></i>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           class="cbh-input cbh-search-input"
                           placeholder="Search pages by title or slug…"
                           aria-label="Search pages">
                </div>

                <select name="status" class="cbh-select" style="min-width: 140px; max-width: 180px;" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="1" {{ (string)$status === '1' ? 'selected' : '' }}>Published</option>
                    <option value="0" {{ (string)$status === '0' ? 'selected' : '' }}>Draft</option>
                </select>

                <button type="submit" class="cbh-btn cbh-btn-secondary">
                    <i class="bi bi-filter"></i>
                    Filter
                </button>

                @if ($search !== '' || $status !== '')
                    <a href="{{ route('websites.pages.index', $website) }}" class="cbh-btn cbh-btn-secondary">
                        <i class="bi bi-x-circle"></i>
                        Clear
                    </a>
                @endif
            </form>

            <a href="{{ route('websites.pages.create', $website) }}" class="cbh-btn cbh-btn-primary" style="white-space: nowrap;">
                <i class="bi bi-plus-lg"></i>
                Create Page
            </a>
        </div>
    </div>
</div>

{{-- Pages Table --}}
<div class="cbh-card">
    <div class="cbh-card-header">
        <div>
            <h2 class="cbh-card-title">Pages for {{ $website->website_name }}</h2>
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
                    <th>Slug URL</th>
                    <th>Page Type</th>
                    <th>Sections</th>
                    <th>Homepage</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pages as $p)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-text text-primary" style="font-size: 1.1rem;"></i>
                                <a href="{{ route('websites.pages.show', [$website, $p]) }}" class="fw-bold text-decoration-none text-dark">
                                    {{ $p->title }}
                                </a>
                            </div>
                        </td>
                        <td>
                            <span class="font-monospace text-secondary" style="font-size: 0.82rem;">/{{ $p->slug }}</span>
                        </td>
                        <td>
                            <span class="cbh-badge cbh-badge-neutral text-capitalize">{{ $p->page_type }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ $p->sections_count }} {{ Str::plural('section', $p->sections_count) }}
                            </span>
                        </td>
                        <td>
                            @if ($p->is_homepage)
                                <span class="cbh-badge cbh-badge-primary">
                                    <i class="bi bi-house-door-fill me-1" style="font-size: 0.4rem;"></i>
                                    Home
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($p->status === 1)
                                <span class="cbh-badge cbh-badge-success">Published</span>
                            @else
                                <span class="cbh-badge cbh-badge-neutral">Draft</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('websites.pages.show', [$website, $p]) }}"
                                   class="cbh-btn cbh-btn-secondary cbh-btn-sm"
                                   title="Manage Page Sections">
                                    <i class="bi bi-layers"></i>
                                    Sections
                                </a>
                                <a href="{{ route('websites.pages.edit', [$website, $p]) }}"
                                   class="cbh-btn cbh-btn-secondary cbh-btn-sm"
                                   title="Edit Page">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button"
                                        class="cbh-btn cbh-btn-danger cbh-btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deletePageModal-{{ $p->id }}"
                                        title="Delete Page">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>

                            {{-- Delete Modal --}}
                            <div class="modal fade" id="deletePageModal-{{ $p->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content" style="text-align: left; border-radius: 12px;">
                                        <div class="modal-header border-bottom">
                                            <h5 class="modal-title fw-bold text-danger">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                Delete Page
                                            </h5>
                                            <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="{{ route('websites.pages.destroy', [$website, $p]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <div class="modal-body py-3">
                                                <p class="mb-1">Are you sure you want to delete <strong>{{ $p->title }}</strong> (/{{ $p->slug }})?</p>
                                                <p class="text-muted small mb-0">All associated page sections and page metadata will also be permanently deleted.</p>
                                            </div>
                                            <div class="modal-footer border-top">
                                                <button type="button" class="cbh-btn cbh-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="cbh-btn cbh-btn-primary" style="background:#dc2626;border-color:#dc2626;">
                                                    <i class="bi bi-trash"></i>
                                                    Delete Page
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-empty-state
                                icon="bi-file-earmark-text"
                                title="No pages found"
                                description="Create the first page or landing page for this website."
                                actionText="Create New Page"
                                actionUrl="{{ route('websites.pages.create', $website) }}"
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
