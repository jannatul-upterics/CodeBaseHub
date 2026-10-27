@extends('layouts.dashboard')

@section('title', 'Websites')
@section('breadcrumb', 'Websites')

@section('content')
{{-- Page Header --}}
<div class="cbh-page-header">
    <div>
        <h1 class="cbh-page-title">
            Websites
            <span class="cbh-badge cbh-badge-primary" style="vertical-align:middle;margin-left:0.5rem;">Directory</span>
        </h1>
        <p class="cbh-page-subtitle">Manage all registered websites, domains, and hosting configurations.</p>
    </div>
    <a href="{{ route('websites.create') }}" class="cbh-btn cbh-btn-primary">
        <i class="bi bi-plus-lg"></i>
        Add Website
    </a>
</div>

{{-- Search + Filter Bar --}}
<div class="cbh-card mb-4">
    <div class="cbh-card-body" style="padding:0.875rem 1.25rem;">
        <form method="GET" action="{{ route('websites.index') }}" id="filterForm">
            <div class="cbh-toolbar">
                {{-- Search --}}
                <div class="cbh-search-wrap" style="flex:2;min-width:220px;">
                    <i class="bi bi-search cbh-search-icon"></i>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           class="cbh-input cbh-search-input"
                           placeholder="Search by name, domain, or provider…"
                           aria-label="Search websites">
                </div>

                {{-- Provider Filter --}}
                <select name="provider"
                        class="cbh-select"
                        style="flex:1;min-width:150px;max-width:200px;"
                        onchange="this.form.submit()"
                        aria-label="Filter by provider">
                    <option value="">All Providers</option>
                    @foreach ($providers as $p)
                        <option value="{{ $p }}" {{ $provider === $p ? 'selected' : '' }}>
                            {{ ucfirst($p) }}
                        </option>
                    @endforeach
                </select>

                {{-- Status Filter --}}
                <select name="status"
                        class="cbh-select"
                        style="flex:1;min-width:130px;max-width:170px;"
                        onchange="this.form.submit()"
                        aria-label="Filter by status">
                    <option value="">All Statuses</option>
                    <option value="1" {{ $status === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ $status === '0' ? 'selected' : '' }}>Inactive</option>
                </select>

                {{-- Actions --}}
                <button type="submit" class="cbh-btn cbh-btn-primary">
                    <i class="bi bi-search"></i>
                    Search
                </button>
                @if ($search !== '' || $provider !== '' || $status !== '' || ($template ?? '') !== '')
                    <a href="{{ route('websites.index') }}" class="cbh-btn cbh-btn-secondary">
                        <i class="bi bi-x-circle"></i>
                        Clear Search Filters
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Active Filters Indicator --}}
@if ($search !== '' || $provider !== '' || $status !== '' || ($template ?? '') !== '')
    <div class="cbh-alert cbh-alert-warning" style="margin-bottom:1rem;">
        <i class="bi bi-funnel-fill cbh-alert-icon"></i>
        <div>
            <strong>Active Filters:</strong>
            @if ($search !== '') <span class="cbh-badge cbh-badge-primary" style="margin-left:0.25rem;">Keyword: "{{ $search }}"</span> @endif
            @if (($template ?? '') !== '') <span class="cbh-badge cbh-badge-primary" style="margin-left:0.25rem;">Template: {{ $template }}</span> @endif
            @if ($provider !== '') <span class="cbh-badge cbh-badge-neutral" style="margin-left:0.25rem;">Provider: {{ ucfirst($provider) }}</span> @endif
            @if ($status !== '') <span class="cbh-badge cbh-badge-neutral" style="margin-left:0.25rem;">Status: {{ $status === '1' ? 'Active' : 'Inactive' }}</span> @endif
            — {{ $websites->total() }} {{ Str::plural('result', $websites->total()) }}
        </div>
    </div>
@endif

{{-- Websites Table --}}
<div class="cbh-card">
    <div class="cbh-card-header">
        <div>
            <h2 class="cbh-card-title">Website Records</h2>
            <p class="cbh-card-subtitle">
                Showing {{ $websites->firstItem() ?? 0 }}–{{ $websites->lastItem() ?? 0 }} of {{ $websites->total() }} {{ Str::plural('website', $websites->total()) }}
            </p>
        </div>
    </div>

    <div class="cbh-table-wrap">
        <table class="cbh-table" id="websitesTable">
            <thead>
                <tr>
                    <th style="padding-left:1.25rem;">Website</th>
                    <th>Domain</th>
                    <th>Customer</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Added</th>
                    <th style="text-align:right;padding-right:1.25rem;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($websites as $website)
                    <tr>
                        {{-- Website Name --}}
                        <td style="padding-left:1.25rem;min-width:220px;">
                            <div style="display:flex;align-items:center;gap:0.625rem;">
                                <div class="cbh-icon-box cbh-icon-blue" style="width:34px;height:34px;font-size:0.95rem;flex-shrink:0;">
                                    <i class="bi bi-globe"></i>
                                </div>
                                <div>
                                    <a href="{{ route('websites.show', $website) }}"
                                       style="font-weight:600;color:var(--cbh-neutral-800);text-decoration:none;font-size:0.875rem;display:block;">
                                        {{ $website->website_name }}
                                    </a>
                                    <span style="font-size:0.7rem;color:var(--cbh-neutral-400);">#CBH-{{ str_pad($website->id, 4, '0', STR_PAD_LEFT) }}</span>
                                </div>
                            </div>
                        </td>

                        {{-- Domain --}}
                        <td>
                            <a href="https://{{ $website->domain }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="cbh-domain"
                               style="text-decoration:none;">
                                {{ $website->domain }}
                                <i class="bi bi-box-arrow-up-right" style="font-size:0.65rem;opacity:0.7;"></i>
                            </a>
                        </td>

                        {{-- Customer --}}
                        <td>
                            @if ($website->customer)
                                <span style="font-size:0.85rem;color:var(--cbh-neutral-700);">{{ $website->customer->name }}</span>
                            @else
                                <span class="cbh-text-subtle">—</span>
                            @endif
                        </td>

                        {{-- Provider --}}
                        <td>
                            <span class="cbh-provider">
                                <i class="bi bi-cloud"></i>
                                {{ ucfirst($website->provider) }}
                            </span>
                        </td>

                        {{-- Status --}}
                        <td>
                            @if ($website->status === 1)
                                <span class="cbh-badge cbh-badge-success">
                                    <i class="bi bi-circle-fill" style="font-size:0.4rem;"></i>
                                    Active
                                </span>
                            @else
                                <span class="cbh-badge cbh-badge-neutral">Inactive</span>
                            @endif
                        </td>

                        {{-- Date --}}
                        <td>
                            <span style="font-size:0.8rem;color:var(--cbh-neutral-500);">
                                {{ $website->created_at?->format('M j, Y') ?? '—' }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td style="text-align:right;padding-right:1.25rem;">
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:0.375rem;">
                                <a href="{{ route('websites.show', $website) }}"
                                   class="cbh-btn cbh-btn-ghost cbh-btn-sm"
                                   title="View details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('websites.edit', $website) }}"
                                   class="cbh-btn cbh-btn-secondary cbh-btn-sm"
                                   title="Edit website">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button"
                                        class="cbh-btn cbh-btn-danger cbh-btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteModal"
                                        data-name="{{ $website->website_name }}"
                                        data-action="{{ route('websites.destroy', $website) }}"
                                        title="Delete website">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="cbh-empty">
                                <div class="cbh-empty-icon">
                                    @if ($search !== '' || $provider !== '' || $status !== '')
                                        <i class="bi bi-search"></i>
                                    @else
                                        <i class="bi bi-globe2"></i>
                                    @endif
                                </div>
                                @if ($search !== '' || $provider !== '' || $status !== '' || ($template ?? '') !== '')
                                    <p class="cbh-empty-title">No matching websites found</p>
                                    <p class="cbh-empty-body">Try clearing your search filters or adjusting the criteria.</p>
                                    <a href="{{ route('websites.index') }}" class="cbh-btn cbh-btn-secondary">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                        Clear Search Filters
                                    </a>
                                @else
                                    <p class="cbh-empty-title">No websites registered yet</p>
                                    <p class="cbh-empty-body">Register your first website to start managing your platform.</p>
                                    <a href="{{ route('websites.create') }}" class="cbh-btn cbh-btn-primary">
                                        <i class="bi bi-plus-lg"></i>
                                        Add Your First Website
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($websites->hasPages())
        <div class="cbh-card-footer" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <span style="font-size:0.8rem;color:var(--cbh-neutral-500);">
                Showing <strong>{{ $websites->firstItem() }}</strong>–<strong>{{ $websites->lastItem() }}</strong>
                of <strong>{{ $websites->total() }}</strong>
            </span>
            <div>{{ $websites->links() }}</div>
        </div>
    @endif
</div>

{{-- Delete Confirmation Modal --}}
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
            <form id="deleteForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="modal-body" style="padding:1rem 1.5rem;">
                    <p style="margin:0 0 0.5rem;font-size:0.9rem;">
                        Are you sure you want to delete <strong id="deleteName">this website</strong>?
                    </p>
                    <p style="font-size:0.82rem;color:var(--cbh-neutral-500);margin:0;">
                        This will permanently remove the website and all associated data including pages, domains, settings, and metadata. This action cannot be undone.
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('deleteModal');
        if (modal) {
            modal.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                document.getElementById('deleteName').textContent = btn.getAttribute('data-name');
                document.getElementById('deleteForm').setAttribute('action', btn.getAttribute('data-action'));
            });
        }
    });
</script>
@endpush
