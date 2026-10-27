@extends('layouts.dashboard')

@section('title', 'Media Library')
@section('breadcrumb', 'Media')

@section('content')
<div class="cbh-page-header">
    <div>
        <h1 class="cbh-page-title">
            Media Library
            <span class="cbh-badge cbh-badge-primary ms-1" style="vertical-align: middle;">Asset Management</span>
        </h1>
        <p class="cbh-page-subtitle">Centralized media repository for website graphics, brand logos, hero banners, and file assets.</p>
    </div>
</div>

{{-- Filter Toolbar --}}
<div class="cbh-card mb-4">
    <div class="cbh-card-body p-3">
        <form method="GET" action="{{ route('media.index') }}" class="cbh-toolbar">
            <div class="cbh-search-wrap flex-grow-1" style="min-width: 220px;">
                <i class="bi bi-search cbh-search-icon"></i>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       class="cbh-input cbh-search-input"
                       placeholder="Search by file name or alt text…"
                       aria-label="Search media">
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
                <a href="{{ route('media.index') }}" class="cbh-btn cbh-btn-secondary">
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
            <h2 class="cbh-card-title">Uploaded Media Assets</h2>
            <p class="cbh-card-subtitle">
                Showing {{ $media->firstItem() ?? 0 }}–{{ $media->lastItem() ?? 0 }} of {{ $media->total() }} {{ Str::plural('asset', $media->total()) }}
            </p>
        </div>
    </div>

    <div class="cbh-table-responsive">
        <table class="cbh-table">
            <thead>
                <tr>
                    <th>File Name</th>
                    <th>Linked Website</th>
                    <th>Format &amp; MIME</th>
                    <th>File Size</th>
                    <th>Alt Text</th>
                    <th>Uploaded</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($media as $item)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-image text-primary" style="font-size: 1.15rem;"></i>
                                <div>
                                    <span class="fw-bold text-dark font-monospace" style="font-size: 0.85rem;">{{ $item->file_name }}</span>
                                    <div class="text-muted" style="font-size: 0.72rem;">{{ $item->file_path }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($item->website)
                                <a href="{{ route('websites.show', $item->website) }}" class="fw-semibold text-decoration-none" style="color: var(--cbh-brand-600);">
                                    {{ $item->website->website_name }}
                                </a>
                            @else
                                <span class="text-muted">Unlinked</span>
                            @endif
                        </td>
                        <td>
                            <span class="cbh-badge cbh-badge-neutral text-uppercase font-monospace">{{ $item->file_type }}</span>
                            <span class="text-muted ms-1" style="font-size: 0.75rem;">{{ $item->mime_type ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span class="text-secondary" style="font-size: 0.82rem;">
                                {{ $item->file_size ? number_format($item->file_size / 1024, 1) . ' KB' : 'N/A' }}
                            </span>
                        </td>
                        <td>
                            <span class="text-muted" style="font-size: 0.82rem;">{{ $item->alt_text ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="text-muted" style="font-size: 0.8rem;">
                                {{ $item->created_at?->format('M d, Y') ?? 'N/A' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-empty-state
                                icon="bi-images"
                                title="Media Library empty"
                                description="No media assets match the selected criteria."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($media->hasPages())
        <div class="cbh-card-footer">
            {{ $media->links() }}
        </div>
    @endif
</div>
@endsection
