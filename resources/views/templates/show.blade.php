@extends('layouts.dashboard')

@section('title', $template->template_name . ' — Template Blueprint')
@section('breadcrumb', 'Templates / ' . $template->template_name)

@section('content')
<div class="cbh-page-header">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('templates.index') }}" class="cbh-btn cbh-btn-secondary cbh-btn-icon" title="Back to Templates">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="cbh-page-title">{{ $template->template_name }}</h1>
                @if ($template->status === 1)
                    <span class="cbh-badge cbh-badge-success">Ready to Deploy</span>
                @else
                    <span class="cbh-badge cbh-badge-neutral">Draft</span>
                @endif
                <span class="cbh-badge cbh-badge-neutral font-monospace">v{{ $template->version ?? '1.0.0' }}</span>
            </div>
            <p class="cbh-page-subtitle">Layout blueprint specifications, wireframe structure, and catalog details.</p>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('templates.edit', $template) }}" class="cbh-btn cbh-btn-secondary">
            <i class="bi bi-pencil"></i>
            Edit Template
        </a>
        <button type="button" class="cbh-btn cbh-btn-danger" data-bs-toggle="modal" data-bs-target="#deleteTemplateModal">
            <i class="bi bi-trash"></i>
            Delete
        </button>
    </div>
</div>

<div class="row g-4">
    {{-- Blueprint Preview & Wireframe --}}
    <div class="col-12 col-lg-7">
        <div class="cbh-card mb-4">
            <div class="cbh-card-header">
                <h2 class="cbh-card-title">Wireframe &amp; Layout Blueprint</h2>
            </div>
            <div class="cbh-card-body">
                @if ($template->preview_image)
                    <div class="mb-4" style="border-radius: 8px; overflow: hidden; border: 1px solid var(--cbh-border); max-height: 380px; background: #f8fafc;">
                        <img src="{{ $template->preview_image }}"
                             alt="{{ $template->template_name }} Preview"
                             style="width: 100%; height: auto; object-fit: contain;"
                             onerror="this.onerror=null; this.parentElement.style.display='none';">
                    </div>
                @endif

                {{-- Interactive Layout Wireframe Schematic --}}
                <div style="background: var(--cbh-neutral-50); border: 2px dashed var(--cbh-neutral-200); border-radius: 8px; padding: 1.5rem;">
                    <div class="d-flex justify-content-between align-items-center p-2 mb-2 bg-white rounded border">
                        <span class="fw-semibold text-dark" style="font-size: 0.82rem;"><i class="bi bi-window-sidebar me-1"></i> Navigation Header Bar</span>
                        <span class="cbh-badge cbh-badge-neutral">Sticky Nav</span>
                    </div>
                    <div class="p-4 mb-2 bg-white rounded border text-center">
                        <div class="cbh-stat-icon cbh-stat-icon-teal mx-auto mb-2" style="width: 40px; height: 40px;">
                            <i class="bi bi-stars"></i>
                        </div>
                        <h4 class="fw-bold mb-1" style="font-size: 1rem;">{{ $template->template_name }}</h4>
                        <p class="text-muted mb-0" style="font-size: 0.8rem;">Hero Banner &amp; Primary Call-to-Action Region</p>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-4">
                            <div class="p-3 bg-white rounded border text-center">
                                <i class="bi bi-grid text-muted"></i>
                                <div style="font-size: 0.75rem; font-weight: 600; margin-top: 4px;">Feature Grid</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 bg-white rounded border text-center">
                                <i class="bi bi-chat-square-quote text-muted"></i>
                                <div style="font-size: 0.75rem; font-weight: 600; margin-top: 4px;">Testimonials</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 bg-white rounded border text-center">
                                <i class="bi bi-cash-stack text-muted"></i>
                                <div style="font-size: 0.75rem; font-weight: 600; margin-top: 4px;">Pricing Tier</div>
                            </div>
                        </div>
                    </div>
                    <div class="p-2 bg-white rounded border text-center text-muted" style="font-size: 0.785rem;">
                        <i class="bi bi-body-text me-1"></i> Multi-column Footer &amp; Legal Sitemap
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Template Metadata --}}
    <div class="col-12 col-lg-5">
        <div class="cbh-card mb-4">
            <div class="cbh-card-header">
                <h2 class="cbh-card-title">Catalog Specifications</h2>
            </div>
            <div class="cbh-card-body">
                <div class="d-flex flex-column gap-3">
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Blueprint Name</div>
                        <div class="fw-bold" style="font-size: 0.95rem;">{{ $template->template_name }}</div>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">System Slug</div>
                        <div class="font-monospace text-secondary" style="font-size: 0.85rem;">{{ $template->slug }}</div>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Deployment Version</div>
                        <span class="cbh-badge cbh-badge-neutral font-monospace">v{{ $template->version ?? '1.0.0' }}</span>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Description</div>
                        <p class="text-muted mb-0" style="font-size: 0.84rem; line-height: 1.5;">
                            {{ $template->description ?? 'No layout description specified for this template.' }}
                        </p>
                    </div>

                    <div class="cbh-alert cbh-alert-info">
                        <i class="bi bi-info-circle-fill cbh-alert-icon"></i>
                        <div style="font-size: 0.8rem;">
                            <strong>Schema Specification:</strong> Templates exist as standalone layout blueprints within the catalog repository.
                        </div>
                    </div>

                    <div class="border-top pt-2">
                        <div class="d-flex justify-content-between text-muted" style="font-size: 0.785rem;">
                            <span>Created:</span>
                            <span>{{ $template->created_at?->format('M d, Y') ?? 'N/A' }}</span>
                        </div>
                        <div class="d-flex justify-content-between text-muted mt-1" style="font-size: 0.785rem;">
                            <span>Last Updated:</span>
                            <span>{{ $template->updated_at?->diffForHumans() ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteTemplateModal" tabindex="-1" aria-labelledby="deleteTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px;border-color:var(--cbh-border);">
            <div class="modal-header" style="border-bottom:1px solid var(--cbh-border);padding:1.25rem 1.5rem 1rem;">
                <h5 class="modal-title" id="deleteTemplateModalLabel" style="font-size:1rem;font-weight:700;color:#b91c1c;display:flex;align-items:center;gap:0.5rem;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Confirm Template Deletion
                </h5>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('templates.destroy', $template) }}">
                @csrf
                @method('DELETE')
                <div class="modal-body" style="padding:1rem 1.5rem;">
                    <p style="margin:0 0 0.5rem;font-size:0.9rem;">
                        Are you sure you want to delete template <strong>{{ $template->template_name }}</strong>?
                    </p>
                    <p style="font-size:0.82rem;color:var(--cbh-neutral-500);margin:0;">
                        This action will remove the layout blueprint record from the catalog.
                    </p>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--cbh-border);padding:1rem 1.5rem;gap:0.5rem;">
                    <button type="button" class="cbh-btn cbh-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="cbh-btn cbh-btn-danger">
                        <i class="bi bi-trash"></i>
                        Delete Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
