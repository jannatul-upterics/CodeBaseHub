@extends('layouts.dashboard')

@section('title', 'Media Library — ' . $website->website_name)
@section('breadcrumb', 'Media Library')

@section('content')
@include('websites._header', ['activeTab' => 'media'])

{{-- Metric Counters --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-3">
        <div class="cbh-card p-3 d-flex align-items-center gap-3">
            <div style="width:40px;height:40px;border-radius:9px;background:var(--cbh-brand-50);color:var(--cbh-brand-600);display:flex;align-items:center;justify-content:center;font-size:1.15rem;">
                <i class="bi bi-images"></i>
            </div>
            <div>
                <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;color:var(--cbh-text-muted);">Total Assets</div>
                <div style="font-size:1.25rem;font-weight:700;color:var(--cbh-text-primary);">{{ $totalCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="cbh-card p-3 d-flex align-items-center gap-3">
            <div style="width:40px;height:40px;border-radius:9px;background:var(--cbh-success-bg);color:var(--cbh-success-600);display:flex;align-items:center;justify-content:center;font-size:1.15rem;">
                <i class="bi bi-card-image"></i>
            </div>
            <div>
                <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;color:var(--cbh-text-muted);">Images</div>
                <div style="font-size:1.25rem;font-weight:700;color:var(--cbh-text-primary);">{{ $imageCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="cbh-card p-3 d-flex align-items-center gap-3">
            <div style="width:40px;height:40px;border-radius:9px;background:var(--cbh-info-bg);color:var(--cbh-info-600);display:flex;align-items:center;justify-content:center;font-size:1.15rem;">
                <i class="bi bi-file-earmark-pdf"></i>
            </div>
            <div>
                <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;color:var(--cbh-text-muted);">Documents</div>
                <div style="font-size:1.25rem;font-weight:700;color:var(--cbh-text-primary);">{{ $docCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-3">
        <div class="cbh-card p-3 d-flex align-items-center gap-3">
            <div style="width:40px;height:40px;border-radius:9px;background:var(--cbh-neutral-100);color:var(--cbh-neutral-700);display:flex;align-items:center;justify-content:center;font-size:1.15rem;">
                <i class="bi bi-hdd"></i>
            </div>
            <div>
                <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;color:var(--cbh-text-muted);">Storage Used</div>
                <div style="font-size:1.25rem;font-weight:700;color:var(--cbh-text-primary);">
                    @if ($totalBytes >= 1048576)
                        {{ number_format($totalBytes / 1048576, 1) }} MB
                    @elseif ($totalBytes >= 1024)
                        {{ number_format($totalBytes / 1024, 0) }} KB
                    @else
                        {{ $totalBytes }} B
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Upload Card & Search --}}
<div class="row g-4 mb-4">
    {{-- Upload Form --}}
    <div class="col-12 col-lg-6">
        <div class="cbh-card h-100">
            <div class="cbh-card-header">
                <div>
                    <h2 class="cbh-card-title">Upload Asset</h2>
                    <p class="cbh-card-subtitle">Upload pictures, logos, and documents for this website</p>
                </div>
            </div>
            <form method="POST" action="{{ route('websites.media.store', $website) }}" enctype="multipart/form-data">
                @csrf
                <div class="cbh-card-body p-4">
                    <div class="mb-3">
                        <label for="file" class="cbh-form-label">Select File <span class="text-danger">*</span></label>
                        <input type="file" id="file" name="file" class="form-control @error('file') is-invalid @enderror" required>
                        <div class="form-text" style="font-size:0.75rem;">Supported: JPG, PNG, GIF, WEBP, SVG, PDF, DOC, ZIP (Max: 10 MB)</div>
                        @error('file')
                            <div class="cbh-form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="alt_text" class="cbh-form-label">Alternative Text (Accessibility / SEO)</label>
                        <input type="text" id="alt_text" name="alt_text" value="{{ old('alt_text') }}" class="cbh-input" placeholder="Descriptive image label...">
                        @error('alt_text')
                            <div class="cbh-form-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="cbh-card-footer bg-light border-top p-3 d-flex justify-content-end">
                    <button type="submit" class="cbh-btn cbh-btn-primary">
                        <i class="bi bi-cloud-upload"></i>
                        Upload to Website
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Search and Filter Toolbar Card --}}
    <div class="col-12 col-lg-6">
        <div class="cbh-card h-100">
            <div class="cbh-card-header">
                <div>
                    <h3 class="cbh-card-title">Filter Media</h3>
                    <p class="cbh-card-subtitle">Find media files by filename or tag</p>
                </div>
            </div>
            <div class="cbh-card-body p-4">
                <form method="GET" action="{{ route('websites.media.index', $website) }}">
                    <div class="mb-3">
                        <label class="cbh-form-label">Search Keyword</label>
                        <div class="cbh-search-wrap">
                            <i class="bi bi-search cbh-search-icon"></i>
                            <input type="text"
                                   name="search"
                                   value="{{ $search }}"
                                   class="cbh-input cbh-search-input"
                                   placeholder="Search by file name or alt text…">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="cbh-form-label">Asset Type</label>
                        <select name="type" class="cbh-select">
                            <option value="">All Media Types</option>
                            <option value="image" {{ $fileType === 'image' ? 'selected' : '' }}>Images Only</option>
                            <option value="document" {{ $fileType === 'document' ? 'selected' : '' }}>Documents Only</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="cbh-btn cbh-btn-primary">
                            <i class="bi bi-filter"></i>
                            Apply Filter
                        </button>
                        @if ($search !== '' || $fileType !== '')
                            <a href="{{ route('websites.media.index', $website) }}" class="cbh-btn cbh-btn-secondary">
                                Clear
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Media Assets Table --}}
<div class="cbh-card">
    <div class="cbh-card-header">
        <div>
            <h2 class="cbh-card-title">Uploaded Media Files</h2>
            <p class="cbh-card-subtitle">
                Showing {{ $media->firstItem() ?? 0 }}–{{ $media->lastItem() ?? 0 }} of {{ $media->total() }} {{ Str::plural('file', $media->total()) }}
            </p>
        </div>
    </div>

    <div class="cbh-table-responsive">
        <table class="cbh-table">
            <thead>
                <tr>
                    <th style="width: 70px;">Asset</th>
                    <th>File Name</th>
                    <th>Type</th>
                    <th>File Size</th>
                    <th>Alt Description</th>
                    <th>Uploaded</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($media as $m)
                    <tr>
                        <td>
                            @if ($m->file_type === 'image')
                                <img src="{{ route('media.file', $m) }}"
                                     alt="{{ $m->alt_text ?? $m->file_name }}"
                                     style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid var(--cbh-border);"
                                     loading="lazy">
                            @else
                                <div style="width: 48px; height: 48px; border-radius: 6px; background: var(--cbh-neutral-100); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; color: var(--cbh-neutral-600); border: 1px solid var(--cbh-border);">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('media.file', $m) }}" target="_blank" class="fw-bold font-monospace text-decoration-none text-dark" style="font-size:0.86rem;">
                                {{ $m->file_name }}
                                <i class="bi bi-box-arrow-up-right ms-1 text-muted" style="font-size:0.7rem;"></i>
                            </a>
                            <div class="text-muted small" style="font-size:0.72rem;">{{ $m->mime_type }}</div>
                        </td>
                        <td>
                            <span class="cbh-badge cbh-badge-neutral text-capitalize">{{ $m->file_type }}</span>
                        </td>
                        <td class="font-monospace text-secondary" style="font-size:0.82rem;">
                            @if ($m->file_size >= 1048576)
                                {{ number_format($m->file_size / 1048576, 2) }} MB
                            @elseif ($m->file_size >= 1024)
                                {{ number_format($m->file_size / 1024, 0) }} KB
                            @else
                                {{ $m->file_size }} B
                            @endif
                        </td>
                        <td>
                            <span class="text-secondary small">{{ $m->alt_text ?? '—' }}</span>
                        </td>
                        <td class="text-muted small" style="font-size:0.8rem;">
                            {{ $m->created_at?->format('M j, Y') ?? '—' }}
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('media.file', $m) }}" target="_blank" class="cbh-btn cbh-btn-secondary cbh-btn-sm" title="View / Download file">
                                    <i class="bi bi-download"></i>
                                </a>
                                <button type="button"
                                        class="cbh-btn cbh-btn-secondary cbh-btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editMediaModal-{{ $m->id }}"
                                        title="Edit File Metadata">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button"
                                        class="cbh-btn cbh-btn-danger cbh-btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteMediaModal-{{ $m->id }}"
                                        title="Delete Media File">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>

                            {{-- Edit Media Modal --}}
                            <div class="modal fade" id="editMediaModal-{{ $m->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content" style="text-align:left; border-radius:12px;">
                                        <div class="modal-header border-bottom">
                                            <h5 class="modal-title fw-bold">Edit Media: {{ $m->file_name }}</h5>
                                            <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="{{ route('websites.media.update', [$website, $m]) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body p-4">
                                                <div class="mb-3">
                                                    <label class="cbh-form-label">File Display Name <span class="text-danger">*</span></label>
                                                    <input type="text" name="file_name" class="cbh-input font-monospace" value="{{ old('file_name', $m->file_name) }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="cbh-form-label">Alt Text (Accessibility)</label>
                                                    <input type="text" name="alt_text" class="cbh-input" value="{{ old('alt_text', $m->alt_text) }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="cbh-form-label">Status</label>
                                                    <select name="status" class="cbh-select">
                                                        <option value="1" {{ old('status', (string)$m->status) === '1' ? 'selected' : '' }}>Active</option>
                                                        <option value="0" {{ old('status', (string)$m->status) === '0' ? 'selected' : '' }}>Disabled</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top">
                                                <button type="button" class="cbh-btn cbh-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="cbh-btn cbh-btn-primary">
                                                    <i class="bi bi-check-lg"></i>
                                                    Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- Delete Media Modal --}}
                            <div class="modal fade" id="deleteMediaModal-{{ $m->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content" style="text-align:left; border-radius:12px;">
                                        <div class="modal-header border-bottom">
                                            <h5 class="modal-title fw-bold text-danger">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                Delete Media File
                                            </h5>
                                            <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="{{ route('websites.media.destroy', [$website, $m]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <div class="modal-body py-3">
                                                <p class="mb-1">Are you sure you want to permanently delete <strong>{{ $m->file_name }}</strong>?</p>
                                                <p class="text-muted small mb-0">The file will be wiped from disk storage immediately.</p>
                                            </div>
                                            <div class="modal-footer border-top">
                                                <button type="button" class="cbh-btn cbh-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="cbh-btn cbh-btn-primary" style="background:#dc2626;border-color:#dc2626;">
                                                    <i class="bi bi-trash"></i>
                                                    Delete File
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
                                icon="bi-images"
                                title="No media uploaded"
                                description="Upload brand logos, hero banners, or documents for this website."
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
