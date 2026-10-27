@extends('layouts.dashboard')

@section('title', $page->title . ' — Sections & Content')
@section('breadcrumb', $page->title)

@section('content')
@include('websites._header', ['activeTab' => 'pages'])

{{-- Page Summary Header --}}
<div class="cbh-card mb-4">
    <div class="cbh-card-body p-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h2 class="cbh-card-title mb-0" style="font-size:1.25rem;">{{ $page->title }}</h2>
                    <span class="font-monospace text-muted" style="font-size:0.85rem;">/{{ $page->slug }}</span>
                    @if ($page->is_homepage)
                        <span class="cbh-badge cbh-badge-primary">
                            <i class="bi bi-house-door-fill me-1" style="font-size:0.4rem;"></i>
                            Homepage
                        </span>
                    @endif
                    @if ($page->status === 1)
                        <span class="cbh-badge cbh-badge-success">Published</span>
                    @else
                        <span class="cbh-badge cbh-badge-neutral">Draft</span>
                    @endif
                    <span class="cbh-badge cbh-badge-neutral text-capitalize">{{ $page->page_type }}</span>
                </div>
                <div class="text-muted" style="font-size:0.8rem;">
                    Created {{ $page->created_at?->format('M j, Y') ?? '—' }} &middot;
                    Contains {{ $page->sections->count() }} {{ Str::plural('section', $page->sections->count()) }}
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="button" class="cbh-btn cbh-btn-primary" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                    <i class="bi bi-plus-lg"></i>
                    Add Section
                </button>
                <a href="{{ route('websites.pages.edit', [$website, $page]) }}" class="cbh-btn cbh-btn-secondary">
                    <i class="bi bi-pencil"></i>
                    Edit Page
                </a>
                <a href="{{ route('websites.seo.index', ['website' => $website, 'page_id' => $page->id]) }}" class="cbh-btn cbh-btn-secondary">
                    <i class="bi bi-search-heart"></i>
                    Page SEO
                </a>
                <a href="{{ route('websites.pages.index', $website) }}" class="cbh-btn cbh-btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Back to Pages
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Page Sections List --}}
<div class="cbh-card">
    <div class="cbh-card-header">
        <div>
            <h3 class="cbh-card-title">Content Sections</h3>
            <p class="cbh-card-subtitle">Manage, reorder, and edit layout sections displayed on this page</p>
        </div>
        <button type="button" class="cbh-btn cbh-btn-secondary cbh-btn-sm" data-bs-toggle="modal" data-bs-target="#addSectionModal">
            <i class="bi bi-plus-lg"></i>
            Add Section
        </button>
    </div>

    @if ($page->sections->count() > 0)
        <div class="list-group list-group-flush">
            @foreach ($page->sections as $index => $section)
                <div class="list-group-item p-3 border-bottom">
                    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                        <div class="d-flex align-items-center gap-3">
                            {{-- Reorder Controls --}}
                            <div class="d-flex flex-column align-items-center gap-1">
                                <form method="POST" action="{{ route('websites.pages.sections.reorder', [$website, $page]) }}" class="m-0">
                                    @csrf
                                    <input type="hidden" name="section_id" value="{{ $section->id }}">
                                    <input type="hidden" name="direction" value="up">
                                    <button type="submit"
                                            class="btn btn-sm btn-light border p-0 d-flex align-items-center justify-content-center"
                                            style="width:24px;height:22px;"
                                            title="Move Section Up"
                                            {{ $index === 0 ? 'disabled' : '' }}>
                                        <i class="bi bi-chevron-up" style="font-size:0.75rem;"></i>
                                    </button>
                                </form>
                                <span class="font-monospace text-muted small" style="font-size:0.7rem;">#{{ $section->sort_order }}</span>
                                <form method="POST" action="{{ route('websites.pages.sections.reorder', [$website, $page]) }}" class="m-0">
                                    @csrf
                                    <input type="hidden" name="section_id" value="{{ $section->id }}">
                                    <input type="hidden" name="direction" value="down">
                                    <button type="submit"
                                            class="btn btn-sm btn-light border p-0 d-flex align-items-center justify-content-center"
                                            style="width:24px;height:22px;"
                                            title="Move Section Down"
                                            {{ $index === $page->sections->count() - 1 ? 'disabled' : '' }}>
                                        <i class="bi bi-chevron-down" style="font-size:0.75rem;"></i>
                                    </button>
                                </form>
                            </div>

                            {{-- Section Information --}}
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h4 class="fw-bold text-dark mb-0" style="font-size:0.95rem;">{{ $section->section_name }}</h4>
                                    <span class="cbh-badge cbh-badge-neutral text-capitalize">{{ $section->section_type }}</span>
                                    @if ($section->status === 1)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.68rem;">Active</span>
                                    @else
                                        <span class="badge bg-light text-muted border" style="font-size:0.68rem;">Hidden</span>
                                    @endif
                                </div>

                                {{-- Structured Content Preview --}}
                                @php $content = is_array($section->content) ? $section->content : []; @endphp
                                <div style="font-size:0.82rem; color:var(--cbh-text-muted);">
                                    @if (!empty($content['heading']))
                                        <div><strong class="text-secondary">Heading:</strong> {{ $content['heading'] }}</div>
                                    @endif
                                    @if (!empty($content['subheading']))
                                        <div><strong class="text-secondary">Subheading:</strong> {{ $content['subheading'] }}</div>
                                    @endif
                                    @if (!empty($content['body']))
                                        <div class="text-truncate" style="max-width:550px;"><strong class="text-secondary">Body:</strong> {{ Str::limit($content['body'], 90) }}</div>
                                    @endif
                                    @if (!empty($content['button_text']))
                                        <div><strong class="text-secondary">Button:</strong> {{ $content['button_text'] }} ({{ $content['button_url'] ?? '#' }})</div>
                                    @endif
                                    @if (empty($content))
                                        <em class="text-muted">No content parameters defined.</em>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Section Action Buttons --}}
                        <div class="d-flex gap-2">
                            <button type="button"
                                    class="cbh-btn cbh-btn-secondary cbh-btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editSectionModal-{{ $section->id }}">
                                <i class="bi bi-pencil"></i>
                                Edit
                            </button>
                            <button type="button"
                                    class="cbh-btn cbh-btn-danger cbh-btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteSectionModal-{{ $section->id }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Edit Section Modal --}}
                    <div class="modal fade" id="editSectionModal-{{ $section->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content" style="border-radius:12px;">
                                <div class="modal-header border-bottom">
                                    <h5 class="modal-title fw-bold">Edit Section: {{ $section->section_name }}</h5>
                                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('websites.pages.sections.update', [$website, $page, $section]) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body p-4">
                                        <div class="row g-3">
                                            <div class="col-12 col-md-8">
                                                <label class="cbh-form-label">Section Name <span class="text-danger">*</span></label>
                                                <input type="text" name="section_name" class="cbh-input" value="{{ old('section_name', $section->section_name) }}" required>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <label class="cbh-form-label">Section Type <span class="text-danger">*</span></label>
                                                <select name="section_type" class="cbh-select" required>
                                                    @foreach (['hero' => 'Hero Banner', 'features' => 'Feature Grid', 'text' => 'Rich Text Block', 'cta' => 'Call to Action', 'faq' => 'FAQ Accordion', 'gallery' => 'Media Gallery', 'custom' => 'Custom Block'] as $k => $label)
                                                        <option value="{{ $k }}" {{ old('section_type', $section->section_type) === $k ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label class="cbh-form-label">Heading</label>
                                                <input type="text" name="heading" class="cbh-input" value="{{ old('heading', $content['heading'] ?? '') }}" placeholder="Primary headline">
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="cbh-form-label">Subheading</label>
                                                <input type="text" name="subheading" class="cbh-input" value="{{ old('subheading', $content['subheading'] ?? '') }}" placeholder="Supporting sub-header">
                                            </div>

                                            <div class="col-12">
                                                <label class="cbh-form-label">Body Content</label>
                                                <textarea name="body" class="cbh-input" rows="3" placeholder="Paragraph copy or descriptions...">{{ old('body', $content['body'] ?? '') }}</textarea>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label class="cbh-form-label">Call to Action Button Text</label>
                                                <input type="text" name="button_text" class="cbh-input" value="{{ old('button_text', $content['button_text'] ?? '') }}" placeholder="e.g. Learn More">
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="cbh-form-label">Button Target URL</label>
                                                <input type="text" name="button_url" class="cbh-input font-monospace" value="{{ old('button_url', $content['button_url'] ?? '') }}" placeholder="/pricing or https://...">
                                            </div>

                                            <div class="col-12">
                                                <label class="cbh-form-label">Status</label>
                                                <select name="status" class="cbh-select">
                                                    <option value="1" {{ old('status', (string)$section->status) === '1' ? 'selected' : '' }}>Active (Visible)</option>
                                                    <option value="0" {{ old('status', (string)$section->status) === '0' ? 'selected' : '' }}>Hidden (Draft)</option>
                                                </select>
                                            </div>
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

                    {{-- Delete Section Modal --}}
                    <div class="modal fade" id="deleteSectionModal-{{ $section->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content" style="border-radius:12px;">
                                <div class="modal-header border-bottom">
                                    <h5 class="modal-title fw-bold text-danger">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        Delete Section
                                    </h5>
                                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('websites.pages.sections.destroy', [$website, $page, $section]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <div class="modal-body py-3">
                                        Are you sure you want to delete section <strong>{{ $section->section_name }}</strong>?
                                    </div>
                                    <div class="modal-footer border-top">
                                        <button type="button" class="cbh-btn cbh-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="cbh-btn cbh-btn-primary" style="background:#dc2626;border-color:#dc2626;">
                                            <i class="bi bi-trash"></i>
                                            Delete Section
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="p-4">
            <x-empty-state
                icon="bi-layers"
                title="No sections on this page"
                description="Break this page down into modular hero banners, text blocks, and feature grids."
            >
                <button type="button" class="cbh-btn cbh-btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                    <i class="bi bi-plus-lg"></i>
                    Add First Section
                </button>
            </x-empty-state>
        </div>
    @endif
</div>

{{-- Add Section Modal --}}
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">Add Section to {{ $page->title }}</h5>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('websites.pages.sections.store', [$website, $page]) }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label class="cbh-form-label">Section Name <span class="text-danger">*</span></label>
                            <input type="text" name="section_name" class="cbh-input" placeholder="e.g. Hero Banner" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="cbh-form-label">Section Type <span class="text-danger">*</span></label>
                            <select name="section_type" class="cbh-select" required>
                                <option value="hero">Hero Banner</option>
                                <option value="features">Feature Grid</option>
                                <option value="text">Rich Text Block</option>
                                <option value="cta">Call to Action</option>
                                <option value="faq">FAQ Accordion</option>
                                <option value="gallery">Media Gallery</option>
                                <option value="custom">Custom Block</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="cbh-form-label">Headline / Title</label>
                            <input type="text" name="heading" class="cbh-input" placeholder="e.g. Build Fast, Scale Faster">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="cbh-form-label">Subheading / Kicker</label>
                            <input type="text" name="subheading" class="cbh-input" placeholder="e.g. The Next Generation Framework">
                        </div>

                        <div class="col-12">
                            <label class="cbh-form-label">Body Content</label>
                            <textarea name="body" class="cbh-input" rows="3" placeholder="Enter paragraph or descriptive copy..."></textarea>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="cbh-form-label">Button Text</label>
                            <input type="text" name="button_text" class="cbh-input" placeholder="e.g. Get Started">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="cbh-form-label">Button Target URL</label>
                            <input type="text" name="button_url" class="cbh-input font-monospace" placeholder="/signup or https://...">
                        </div>

                        <div class="col-12">
                            <label class="cbh-form-label">Visibility Status</label>
                            <select name="status" class="cbh-select">
                                <option value="1" selected>Active (Visible)</option>
                                <option value="0">Draft (Hidden)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="cbh-btn cbh-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="cbh-btn cbh-btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        Add Section
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
