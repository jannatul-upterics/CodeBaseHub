@extends('layouts.dashboard')

@section('title', $theme->theme_name . ' — Theme Details')
@section('breadcrumb', 'Themes / ' . $theme->theme_name)

@section('content')
<div class="cbh-page-header">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('themes.index') }}" class="cbh-btn cbh-btn-secondary cbh-btn-icon" title="Back to Themes">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="cbh-page-title">{{ $theme->theme_name }}</h1>
                @if ($theme->status === 1)
                    <span class="cbh-badge cbh-badge-success">Active</span>
                @else
                    <span class="cbh-badge cbh-badge-neutral">Inactive</span>
                @endif
                <span class="cbh-badge cbh-badge-primary">v{{ $theme->version ?? '1.0.0' }}</span>
            </div>
            <p class="cbh-page-subtitle">Design system specifications and installed website instances.</p>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('themes.edit', $theme) }}" class="cbh-btn cbh-btn-secondary">
            <i class="bi bi-pencil"></i>
            Edit Theme
        </a>
        <button type="button" class="cbh-btn cbh-btn-danger" data-bs-toggle="modal" data-bs-target="#deleteThemeModal">
            <i class="bi bi-trash"></i>
            Delete
        </button>
    </div>
</div>

<div class="row g-4">
    {{-- Theme Overview Card --}}
    <div class="col-12 col-lg-4">
        <div class="cbh-card mb-4">
            @if ($theme->preview_image)
                <div style="height: 180px; overflow: hidden; background: #f1f5f9; border-top-left-radius: 12px; border-top-right-radius: 12px;">
                    <img src="{{ $theme->preview_image }}"
                         alt="{{ $theme->theme_name }} Preview"
                         style="width: 100%; height: 100%; object-fit: cover;"
                         onerror="this.onerror=null; this.parentElement.style.display='none';">
                </div>
            @endif
            <div class="cbh-card-header">
                <h2 class="cbh-card-title">Theme Profile</h2>
            </div>
            <div class="cbh-card-body">
                <div class="d-flex flex-column gap-3">
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Theme Name</div>
                        <div class="fw-bold" style="font-size: 0.95rem;">{{ $theme->theme_name }}</div>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">System Slug</div>
                        <div class="font-monospace text-secondary" style="font-size: 0.85rem;">{{ $theme->slug }}</div>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Semantic Version</div>
                        <span class="cbh-badge cbh-badge-neutral">v{{ $theme->version ?? '1.0.0' }}</span>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Description</div>
                        <p class="text-muted mb-0" style="font-size: 0.84rem; line-height: 1.5;">
                            {{ $theme->description ?? 'No detailed description provided for this design system.' }}
                        </p>
                    </div>
                    <div class="border-top pt-2">
                        <div class="d-flex justify-content-between text-muted" style="font-size: 0.785rem;">
                            <span>Created:</span>
                            <span>{{ $theme->created_at?->format('M d, Y') ?? 'N/A' }}</span>
                        </div>
                        <div class="d-flex justify-content-between text-muted mt-1" style="font-size: 0.785rem;">
                            <span>Last Updated:</span>
                            <span>{{ $theme->updated_at?->diffForHumans() ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Websites Using This Theme --}}
    <div class="col-12 col-lg-8">
        <div class="cbh-card">
            <div class="cbh-card-header">
                <div>
                    <h2 class="cbh-card-title">Assigned Websites ({{ $theme->websites->count() }})</h2>
                    <p class="cbh-card-subtitle">Websites currently configured with this theme through website_theme_settings.</p>
                </div>
                <a href="{{ route('websites.create') }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm">
                    <i class="bi bi-plus-lg"></i>
                    Assign to New Website
                </a>
            </div>

            <div class="cbh-table-responsive">
                <table class="cbh-table">
                    <thead>
                        <tr>
                            <th>Website</th>
                            <th>Domain</th>
                            <th>Provider</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($theme->websites as $site)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="cbh-stat-icon cbh-stat-icon-blue" style="width: 28px; height: 28px; font-size: 0.8rem; border-radius: 5px;">
                                            <i class="bi bi-globe2"></i>
                                        </div>
                                        <a href="{{ route('websites.show', $site) }}" class="fw-semibold text-dark text-decoration-none">
                                            {{ $site->website_name }}
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <span class="font-monospace text-secondary" style="font-size: 0.82rem;">{{ $site->domain }}</span>
                                </td>
                                <td>
                                    <span class="cbh-badge cbh-badge-neutral">{{ ucfirst($site->provider) }}</span>
                                </td>
                                <td>
                                    @if ($site->status === 1)
                                        <span class="cbh-badge cbh-badge-success">Active</span>
                                    @else
                                        <span class="cbh-badge cbh-badge-neutral">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('websites.show', $site) }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm">
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-empty-state
                                        icon="bi-palette"
                                        title="No websites currently using this theme"
                                        description="You can assign this theme to any website via the website configuration manager."
                                        actionText="Browse Websites"
                                        actionUrl="{{ route('websites.index') }}"
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Safe Delete Confirmation Modal --}}
<div class="modal fade" id="deleteThemeModal" tabindex="-1" aria-labelledby="deleteThemeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px;border-color:var(--cbh-border);">
            <div class="modal-header" style="border-bottom:1px solid var(--cbh-border);padding:1.25rem 1.5rem 1rem;">
                <h5 class="modal-title" id="deleteThemeModalLabel" style="font-size:1rem;font-weight:700;color:#b91c1c;display:flex;align-items:center;gap:0.5rem;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Confirm Theme Deletion
                </h5>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            @if ($theme->websites->count() > 0)
                <div class="modal-body" style="padding:1rem 1.5rem;">
                    <div class="cbh-alert cbh-alert-danger mb-3">
                        <i class="bi bi-shield-x cbh-alert-icon"></i>
                        <div>
                            <strong>Integrity Protection:</strong> This theme cannot be deleted because it is currently assigned to <strong>{{ $theme->websites->count() }} website(s)</strong>.
                        </div>
                    </div>
                    <p style="font-size:0.85rem;color:var(--cbh-neutral-600);margin:0;">
                        To protect database constraints and foreign-key integrity, please reassign those websites to a different theme before deleting this record.
                    </p>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--cbh-border);padding:1rem 1.5rem;">
                    <button type="button" class="cbh-btn cbh-btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            @else
                <form method="POST" action="{{ route('themes.destroy', $theme) }}">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body" style="padding:1rem 1.5rem;">
                        <p style="margin:0 0 0.5rem;font-size:0.9rem;">
                            Are you sure you want to delete theme <strong>{{ $theme->theme_name }}</strong>?
                        </p>
                        <p style="font-size:0.82rem;color:var(--cbh-neutral-500);margin:0;">
                            This theme has no assigned websites. Deleting it will permanently remove the record from the catalog.
                        </p>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--cbh-border);padding:1rem 1.5rem;gap:0.5rem;">
                        <button type="button" class="cbh-btn cbh-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="cbh-btn cbh-btn-danger">
                            <i class="bi bi-trash"></i>
                            Delete Theme
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
