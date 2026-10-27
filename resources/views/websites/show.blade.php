@extends('layouts.dashboard')

@section('title', $website->website_name)
@section('breadcrumb', $website->website_name)

@section('content')
@include('websites._header', ['activeTab' => 'overview'])

<div class="row g-4">

    {{-- Left Column --}}
    <div class="col-12 col-lg-8">

        {{-- Core Information --}}
        <div class="cbh-card mb-4">
            <div class="cbh-card-header">
                <h2 class="cbh-card-title">
                    <i class="bi bi-info-circle" style="color:var(--cbh-brand-500);margin-right:0.375rem;"></i>
                    Core Information
                </h2>
            </div>
            <div class="cbh-card-body">
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--cbh-neutral-400);margin-bottom:0.25rem;">Website Name</div>
                        <div style="font-size:0.95rem;font-weight:600;color:var(--cbh-neutral-800);">{{ $website->website_name }}</div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--cbh-neutral-400);margin-bottom:0.25rem;">Primary Domain</div>
                        <a href="https://{{ $website->domain }}" target="_blank" rel="noopener noreferrer" class="cbh-domain" style="text-decoration:none;">
                            {{ $website->domain }}
                            <i class="bi bi-box-arrow-up-right" style="font-size:0.65rem;opacity:0.7;"></i>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--cbh-neutral-400);margin-bottom:0.25rem;">Hosting Provider</div>
                        <span class="cbh-provider">
                            <i class="bi bi-cloud"></i>
                            {{ ucfirst($website->provider) }}
                        </span>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--cbh-neutral-400);margin-bottom:0.25rem;">Customer</div>
                        <div style="font-size:0.875rem;color:var(--cbh-neutral-700);">
                            {{ $website->customer?->name ?? '—' }}
                        </div>
                    </div>
                    @if ($website->description)
                        <div class="col-12">
                            <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--cbh-neutral-400);margin-bottom:0.25rem;">Description</div>
                            <div style="font-size:0.875rem;color:var(--cbh-neutral-700);line-height:1.6;">{{ $website->description }}</div>
                        </div>
                    @endif
                    @if ($website->notes)
                        <div class="col-12">
                            <div style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--cbh-neutral-400);margin-bottom:0.25rem;">Internal Notes</div>
                            <div style="font-size:0.875rem;color:var(--cbh-neutral-500);line-height:1.6;background:var(--cbh-neutral-50);padding:0.625rem 0.875rem;border-radius:7px;border:1px solid var(--cbh-border);">
                                {{ $website->notes }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Domains --}}
        <div class="cbh-card mb-4">
            <div class="cbh-card-header d-flex justify-content-between align-items-center">
                <h2 class="cbh-card-title mb-0">
                    <i class="bi bi-hdd-network-fill" style="color:var(--cbh-brand-500);margin-right:0.375rem;"></i>
                    Domains
                    <span class="cbh-badge cbh-badge-neutral" style="margin-left:0.375rem;">{{ $website->domains->count() }}</span>
                </h2>
                <a href="{{ route('websites.domains.index', $website) }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm">
                    Manage Domains
                </a>
            </div>
            @if ($website->domains->count() > 0)
                <div class="cbh-table-wrap">
                    <table class="cbh-table">
                        <thead>
                            <tr>
                                <th>Domain Name</th>
                                <th>Primary</th>
                                <th>SSL</th>
                                <th>Verified</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($website->domains as $domain)
                                <tr>
                                    <td class="cbh-domain">{{ $domain->domain_name }}</td>
                                    <td>
                                        @if ($domain->is_primary)
                                            <span class="cbh-badge cbh-badge-primary">Primary</span>
                                        @else
                                            <span class="cbh-text-subtle">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($domain->ssl_enabled)
                                            <span class="cbh-badge cbh-badge-success">
                                                <i class="bi bi-shield-check"></i> SSL
                                            </span>
                                        @else
                                            <span class="cbh-badge cbh-badge-warning">No SSL</span>
                                        @endif
                                    </td>
                                    <td style="font-size:0.8rem;color:var(--cbh-neutral-500);">
                                        {{ $domain->verified_at?->format('M j, Y') ?? 'Unverified' }}
                                    </td>
                                    <td>
                                        @if ($domain->status === 1)
                                            <span class="cbh-badge cbh-badge-success">Active</span>
                                        @else
                                            <span class="cbh-badge cbh-badge-neutral">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="cbh-empty" style="padding:1.75rem 1.5rem;">
                    <div class="cbh-empty-icon"><i class="bi bi-hdd-network"></i></div>
                    <p class="cbh-empty-title">No domains</p>
                    <p class="cbh-empty-body">No additional domain records have been added.</p>
                </div>
            @endif
        </div>

        {{-- Pages --}}
        <div class="cbh-card mb-4">
            <div class="cbh-card-header d-flex justify-content-between align-items-center">
                <h2 class="cbh-card-title mb-0">
                    <i class="bi bi-file-earmark-text-fill" style="color:var(--cbh-brand-500);margin-right:0.375rem;"></i>
                    Pages
                    <span class="cbh-badge cbh-badge-neutral" style="margin-left:0.375rem;">{{ $website->pages->count() }}</span>
                </h2>
                <a href="{{ route('websites.pages.index', $website) }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm">
                    Manage Pages
                </a>
            </div>
            @if ($website->pages->count() > 0)
                <div class="cbh-table-wrap">
                    <table class="cbh-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Slug</th>
                                <th>Type</th>
                                <th>Homepage</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($website->pages as $page)
                                <tr>
                                    <td style="font-weight:600;color:var(--cbh-neutral-800);">{{ $page->title }}</td>
                                    <td class="cbh-domain">/{{ $page->slug }}</td>
                                    <td>
                                        <span class="cbh-badge cbh-badge-neutral">{{ ucfirst($page->page_type) }}</span>
                                    </td>
                                    <td>
                                        @if ($page->is_homepage)
                                            <span class="cbh-badge cbh-badge-primary">
                                                <i class="bi bi-house-fill"></i> Home
                                            </span>
                                        @else
                                            <span class="cbh-text-subtle">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($page->status === 1)
                                            <span class="cbh-badge cbh-badge-success">Active</span>
                                        @else
                                            <span class="cbh-badge cbh-badge-neutral">Draft</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="cbh-empty" style="padding:1.75rem 1.5rem;">
                    <div class="cbh-empty-icon"><i class="bi bi-file-earmark-text"></i></div>
                    <p class="cbh-empty-title">No pages</p>
                    <p class="cbh-empty-body">No pages have been added to this website.</p>
                </div>
            @endif
        </div>

    </div>

    {{-- Right Column --}}
    <div class="col-12 col-lg-4">

        {{-- Metadata --}}
        <div class="cbh-card mb-4">
            <div class="cbh-card-header d-flex justify-content-between align-items-center">
                <h2 class="cbh-card-title mb-0">
                    <i class="bi bi-search-heart-fill" style="color:var(--cbh-brand-500);margin-right:0.375rem;"></i>
                    SEO Metadata
                </h2>
                <a href="{{ route('websites.seo.index', $website) }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm">
                    Manage SEO
                </a>
            </div>
            @if ($website->metadata->count() > 0)
                @php $meta = $website->metadata->first(); @endphp
                <div class="cbh-card-body" style="display:flex;flex-direction:column;gap:0.625rem;font-size:0.82rem;">
                    @if ($meta->meta_title)
                        <div>
                            <div style="font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--cbh-neutral-400);margin-bottom:0.2rem;">Meta Title</div>
                            <div style="color:var(--cbh-neutral-700);">{{ $meta->meta_title }}</div>
                        </div>
                    @endif
                    @if ($meta->meta_description)
                        <div>
                            <div style="font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--cbh-neutral-400);margin-bottom:0.2rem;">Meta Description</div>
                            <div style="color:var(--cbh-neutral-500);">{{ $meta->meta_description }}</div>
                        </div>
                    @endif
                    @if ($meta->canonical_url)
                        <div>
                            <div style="font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--cbh-neutral-400);margin-bottom:0.2rem;">Canonical URL</div>
                            <div class="cbh-domain" style="word-break:break-all;">{{ $meta->canonical_url }}</div>
                        </div>
                    @endif
                    @if ($meta->robots)
                        <div>
                            <div style="font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--cbh-neutral-400);margin-bottom:0.2rem;">Robots</div>
                            <span class="cbh-badge cbh-badge-neutral">{{ $meta->robots }}</span>
                        </div>
                    @endif
                </div>
            @else
                <div class="cbh-empty" style="padding:1.5rem;">
                    <div class="cbh-empty-icon" style="width:40px;height:40px;font-size:1.1rem;"><i class="bi bi-search-heart"></i></div>
                    <p class="cbh-empty-title" style="font-size:0.875rem;">No metadata</p>
                </div>
            @endif
        </div>

        {{-- Theme Settings --}}
        <div class="cbh-card mb-4">
            <div class="cbh-card-header d-flex justify-content-between align-items-center">
                <h2 class="cbh-card-title mb-0">
                    <i class="bi bi-palette-fill" style="color:var(--cbh-brand-500);margin-right:0.375rem;"></i>
                    Theme Settings
                </h2>
                <a href="{{ route('websites.theme.index', $website) }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm">
                    Customize Theme
                </a>
            </div>
            @if ($website->themeSettings->count() > 0)
                <div class="cbh-card-body" style="display:flex;flex-direction:column;gap:0.5rem;">
                    @foreach ($website->themeSettings as $ts)
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.8rem;padding:0.375rem 0;border-bottom:1px solid var(--cbh-border);">
                            <div>
                                @if ($ts->theme)
                                    <div style="font-weight:600;color:var(--cbh-neutral-700);">{{ $ts->theme->theme_name }}</div>
                                @endif
                                <div class="cbh-text-subtle">{{ $ts->setting_key }}</div>
                            </div>
                            <span class="cbh-badge cbh-badge-neutral">{{ $ts->setting_value }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="cbh-empty" style="padding:1.5rem;">
                    <div class="cbh-empty-icon" style="width:40px;height:40px;font-size:1.1rem;"><i class="bi bi-palette"></i></div>
                    <p class="cbh-empty-title" style="font-size:0.875rem;">No theme configured</p>
                </div>
            @endif

            <div class="cbh-card-footer bg-light border-top p-3">
                <form method="POST" action="{{ route('websites.theme', $website) }}" class="d-flex gap-2">
                    @csrf
                    <select name="theme_id" class="cbh-select form-select-sm" required style="font-size:0.8rem;">
                        <option value="">Switch / configure theme…</option>
                        @foreach ($allThemes as $t)
                            <option value="{{ $t->id }}">{{ $t->theme_name }} (v{{ $t->version ?? '1.0' }})</option>
                        @endforeach
                    </select>
                    <button type="submit" class="cbh-btn cbh-btn-secondary cbh-btn-sm" style="white-space:nowrap;">
                        Apply Theme
                    </button>
                </form>
            </div>
        </div>

        {{-- Record Meta --}}
        <div class="cbh-card" style="font-size:0.8rem;">
            <div class="cbh-card-body" style="display:flex;flex-direction:column;gap:0.5rem;">
                <div style="display:flex;justify-content:space-between;padding-bottom:0.375rem;border-bottom:1px solid var(--cbh-border);">
                    <span class="cbh-text-muted">ID</span>
                    <strong>#CBH-{{ str_pad($website->id, 4, '0', STR_PAD_LEFT) }}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding-bottom:0.375rem;border-bottom:1px solid var(--cbh-border);">
                    <span class="cbh-text-muted">Created</span>
                    <span>{{ $website->created_at?->format('M j, Y') ?? '—' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span class="cbh-text-muted">Updated</span>
                    <span>{{ $website->updated_at?->diffForHumans() ?? '—' }}</span>
                </div>
            </div>
            <div class="cbh-card-footer bg-light border-top p-3 text-end">
                <button type="button"
                        class="cbh-btn cbh-btn-danger cbh-btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteModal">
                    <i class="bi bi-trash"></i>
                    Delete Website
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px;border-color:var(--cbh-border);">
            <div class="modal-header" style="border-bottom:1px solid var(--cbh-border);padding:1.25rem 1.5rem 1rem;">
                <h5 class="modal-title" id="deleteModalLabel" style="font-size:1rem;font-weight:700;color:#b91c1c;display:flex;align-items:center;gap:0.5rem;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Delete Website
                </h5>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('websites.destroy', $website) }}">
                @csrf
                @method('DELETE')
                <div class="modal-body" style="padding:1rem 1.5rem;">
                    <p style="margin:0 0 0.5rem;font-size:0.9rem;">
                        Are you sure you want to delete <strong>{{ $website->website_name }}</strong>?
                    </p>
                    <p style="font-size:0.82rem;color:var(--cbh-neutral-500);margin:0;">
                        This will permanently remove the website and all associated data including pages, domains, settings, and metadata.
                    </p>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--cbh-border);padding:1rem 1.5rem;gap:0.5rem;">
                    <button type="button" class="cbh-btn cbh-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="cbh-btn cbh-btn-primary" style="background:#dc2626;border-color:#dc2626;">
                        <i class="bi bi-trash"></i>
                        Delete Website
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
