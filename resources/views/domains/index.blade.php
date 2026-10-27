@extends('layouts.dashboard')

@section('title', 'Domains & DNS')
@section('breadcrumb', 'Domains')

@section('content')
<div class="cbh-page-header">
    <div>
        <h1 class="cbh-page-title">
            Domains
            <span class="cbh-badge cbh-badge-primary ms-1" style="vertical-align: middle;">DNS Management</span>
        </h1>
        <p class="cbh-page-subtitle">Configure primary domains, vanity hosts, SSL certificates, and DNS verification.</p>
    </div>
</div>

{{-- Search & Filter Toolbar --}}
<div class="cbh-card mb-4">
    <div class="cbh-card-body p-3">
        <form method="GET" action="{{ route('domains.index') }}" class="cbh-toolbar">
            <div class="cbh-search-wrap flex-grow-1" style="min-width: 240px;">
                <i class="bi bi-search cbh-search-icon"></i>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       class="cbh-input cbh-search-input"
                       placeholder="Search domain by hostname (e.g. example.com)…"
                       aria-label="Search domains">
            </div>

            <select name="status" class="cbh-select" style="min-width: 160px; max-width: 200px;" onchange="this.form.submit()">
                <option value="">All DNS Statuses</option>
                <option value="verified" {{ $status === 'verified' ? 'selected' : '' }}>Verified DNS</option>
                <option value="unverified" {{ $status === 'unverified' ? 'selected' : '' }}>Pending Verification</option>
            </select>

            <button type="submit" class="cbh-btn cbh-btn-primary">
                <i class="bi bi-search"></i>
                Filter
            </button>

            @if ($search !== '' || $status !== '')
                <a href="{{ route('domains.index') }}" class="cbh-btn cbh-btn-secondary">
                    <i class="bi bi-x-circle"></i>
                    Clear
                </a>
            @endif
        </form>
    </div>
</div>

{{-- Domains Table --}}
<div class="cbh-card">
    <div class="cbh-card-header">
        <div>
            <h2 class="cbh-card-title">Registered Domains</h2>
            <p class="cbh-card-subtitle">
                Showing {{ $domains->firstItem() ?? 0 }}–{{ $domains->lastItem() ?? 0 }} of {{ $domains->total() }} {{ Str::plural('domain', $domains->total()) }}
            </p>
        </div>
    </div>

    <div class="cbh-table-responsive">
        <table class="cbh-table">
            <thead>
                <tr>
                    <th>Domain Hostname</th>
                    <th>Linked Website</th>
                    <th>Routing Type</th>
                    <th>DNS Verification</th>
                    <th>SSL Encryption</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($domains as $d)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-hdd-network text-teal" style="font-size: 1.1rem; color: #0d9488;"></i>
                                <span class="fw-bold font-monospace text-dark" style="font-size: 0.885rem;">
                                    {{ $d->domain_name }}
                                </span>
                            </div>
                        </td>
                        <td>
                            @if ($d->website)
                                <a href="{{ route('websites.show', $d->website) }}" class="fw-semibold text-decoration-none" style="color: var(--cbh-brand-600);">
                                    {{ $d->website->website_name }}
                                </a>
                            @else
                                <span class="text-muted">Orphan Domain</span>
                            @endif
                        </td>
                        <td>
                            @if ($d->is_primary)
                                <span class="cbh-badge cbh-badge-primary">Primary Domain</span>
                            @else
                                <span class="cbh-badge cbh-badge-neutral">Alias / Secondary</span>
                            @endif
                        </td>
                        <td>
                            @if ($d->verified_at)
                                <span class="cbh-badge cbh-badge-success">
                                    <i class="bi bi-check-circle-fill me-1" style="font-size: 0.38rem;"></i>
                                    Verified ({{ $d->verified_at->format('M d, Y') }})
                                </span>
                            @else
                                <span class="cbh-badge cbh-badge-warning">
                                    <i class="bi bi-clock-history me-1"></i>
                                    Pending DNS
                                </span>
                            @endif
                        </td>
                        <td>
                            @if ($d->ssl_enabled)
                                <span class="cbh-badge cbh-badge-success">
                                    <i class="bi bi-lock-fill me-1"></i>
                                    Active SSL
                                </span>
                            @else
                                <span class="cbh-badge cbh-badge-neutral">
                                    <i class="bi bi-unlock me-1"></i>
                                    No SSL
                                </span>
                            @endif
                        </td>
                        <td>
                            @if ($d->status === 1)
                                <span class="text-success fw-semibold" style="font-size: 0.82rem;">Enabled</span>
                            @else
                                <span class="text-muted" style="font-size: 0.82rem;">Disabled</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-empty-state
                                icon="bi-hdd-network"
                                title="No domains found"
                                description="No domain records match your filter criteria."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($domains->hasPages())
        <div class="cbh-card-footer">
            {{ $domains->links() }}
        </div>
    @endif
</div>
@endsection
