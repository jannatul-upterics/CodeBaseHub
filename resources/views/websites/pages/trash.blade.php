@extends('layouts.dashboard')

@section('title', 'Deleted Pages (Trash) — ' . $website->website_name)
@section('breadcrumb', 'Pages Trash')

@section('content')
@include('websites._header', ['activeTab' => 'pages'])

{{-- Metric Badges & Info --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6">
        <div class="cbh-card p-3 d-flex align-items-center gap-3">
            <div style="width:42px;height:42px;border-radius:10px;background:rgba(220, 38, 38, 0.1);color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:1.25rem;">
                <i class="bi bi-trash"></i>
            </div>
            <div>
                <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--cbh-text-muted);">Pages in Trash</div>
                <div style="font-size:1.35rem;font-weight:700;color:var(--cbh-text-primary);">{{ $trashedCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="cbh-card p-3 d-flex align-items-center gap-3">
            <div style="width:42px;height:42px;border-radius:10px;background:var(--cbh-brand-50);color:var(--cbh-brand-600);display:flex;align-items:center;justify-content:center;font-size:1.25rem;">
                <i class="bi bi-file-earmark-check"></i>
            </div>
            <div>
                <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--cbh-text-muted);">Active Pages</div>
                <div style="font-size:1.35rem;font-weight:700;color:var(--cbh-text-primary);">{{ $activePagesCount }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Search & Back Toolbar --}}
<div class="cbh-card mb-4">
    <div class="cbh-card-body p-3">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <form method="GET" action="{{ route('websites.pages.trash', $website) }}" class="cbh-toolbar flex-grow-1">
                <div class="cbh-search-wrap flex-grow-1" style="min-width: 220px;">
                    <i class="bi bi-search cbh-search-icon"></i>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           class="cbh-input cbh-search-input"
                           placeholder="Search deleted pages by title or slug…"
                           aria-label="Search deleted pages">
                </div>

                <button type="submit" class="cbh-btn cbh-btn-secondary">
                    <i class="bi bi-filter"></i>
                    Filter
                </button>

                @if ($search !== '')
                    <a href="{{ route('websites.pages.trash', $website) }}" class="cbh-btn cbh-btn-secondary">
                        <i class="bi bi-x-circle"></i>
                        Clear
                    </a>
                @endif
            </form>

            <a href="{{ route('websites.pages.index', $website) }}" class="cbh-btn cbh-btn-secondary" style="white-space: nowrap;">
                <i class="bi bi-arrow-left"></i>
                Back to Active Pages
            </a>
        </div>
    </div>
</div>

{{-- Trashed Pages Table --}}
<div class="cbh-card">
    <div class="cbh-card-header">
        <div>
            <h2 class="cbh-card-title text-danger">
                <i class="bi bi-trash me-1"></i>
                Deleted Pages (Trash)
            </h2>
            <p class="cbh-card-subtitle">
                Showing {{ $pages->firstItem() ?? 0 }}–{{ $pages->lastItem() ?? 0 }} of {{ $pages->total() }} deleted {{ Str::plural('page', $pages->total()) }}
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
                    <th>Deleted At</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pages as $p)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-x text-danger" style="font-size: 1.1rem;"></i>
                                <span class="fw-bold text-dark">
                                    {{ $p->title }}
                                </span>
                                @if ($p->is_homepage)
                                    <span class="cbh-badge cbh-badge-neutral ms-1">Was Homepage</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="font-monospace text-muted" style="font-size: 0.82rem;">/{{ $p->slug }}</span>
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
                            <span class="text-secondary small" title="{{ $p->deleted_at }}">
                                {{ $p->deleted_at?->diffForHumans() }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2">
                                {{-- Restore Button --}}
                                <form method="POST" action="{{ route('websites.pages.restore', [$website, $p->id]) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="cbh-btn cbh-btn-secondary cbh-btn-sm" title="Restore Page">
                                        <i class="bi bi-arrow-counterclockwise text-success"></i>
                                        Restore
                                    </button>
                                </form>

                                {{-- Force Delete Trigger --}}
                                <button type="button"
                                        class="cbh-btn cbh-btn-danger cbh-btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#forceDeleteModal-{{ $p->id }}"
                                        title="Permanently Delete Page">
                                    <i class="bi bi-x-octagon"></i>
                                    Delete Permanently
                                </button>
                            </div>

                            {{-- Force Delete Confirmation Modal --}}
                            <div class="modal fade" id="forceDeleteModal-{{ $p->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content" style="text-align: left; border-radius: 12px;">
                                        <div class="modal-header border-bottom">
                                            <h5 class="modal-title fw-bold text-danger">
                                                <i class="bi bi-exclamation-octagon-fill me-1"></i>
                                                Permanently Delete Page
                                            </h5>
                                            <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="{{ route('websites.pages.force_delete', [$website, $p->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <div class="modal-body py-3">
                                                <div class="alert alert-danger py-2 small mb-3">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                    <strong>Warning:</strong> This action is irreversible.
                                                </div>
                                                <p class="mb-1">Are you sure you want to permanently delete <strong>{{ $p->title }}</strong> (/{{ $p->slug }})?</p>
                                                <p class="text-muted small mb-0">This record, its {{ $p->sections_count }} sections, metadata, and connected tenant database records will be permanently removed.</p>
                                            </div>
                                            <div class="modal-footer border-top">
                                                <button type="button" class="cbh-btn cbh-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="cbh-btn cbh-btn-danger">
                                                    <i class="bi bi-trash3-fill me-1"></i>
                                                    Permanently Delete
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
                        <td colspan="6">
                            <x-empty-state
                                icon="bi-trash"
                                title="Trash is empty"
                                description="No soft-deleted pages found for this website."
                                actionText="View Active Pages"
                                actionUrl="{{ route('websites.pages.index', $website) }}"
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
