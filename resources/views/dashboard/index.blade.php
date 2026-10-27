@extends('layouts.dashboard')

@section('title', 'Admin Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
{{-- Page Header --}}
<div class="cbh-page-header">
    <div>
        <h1 class="cbh-page-title">Admin Dashboard</h1>
        <p class="cbh-page-subtitle">Real-time overview of your customer fleet, website deployments, and domain health. Database-backed inventory</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('customers.create') }}" class="cbh-btn cbh-btn-secondary">
            <i class="bi bi-person-plus-fill"></i>
            New Customer
        </a>
        <a href="{{ route('websites.create') }}" class="cbh-btn cbh-btn-primary">
            <i class="bi bi-plus-lg"></i>
            New Website
        </a>
    </div>
</div>

{{-- Empty Database Onboarding Guidance Banner --}}
@if ($isEmptyDatabase)
    <div class="cbh-card mb-4" style="border-left: 4px solid var(--cbh-brand-600); background: linear-gradient(135deg, var(--cbh-brand-50) 0%, #ffffff 100%);">
        <div class="cbh-card-body p-4">
            <div class="d-flex align-items-start gap-3">
                <div class="cbh-stat-icon cbh-stat-icon-blue" style="width: 48px; height: 48px; font-size: 1.5rem;">
                    <i class="bi bi-rocket-takeoff-fill"></i>
                </div>
                <div class="flex-grow-1">
                    <h3 class="fw-bold mb-1" style="font-size: 1.15rem; color: var(--cbh-text-primary);">
                        Welcome to CodeBaseHub — Let's Get Your Platform Started!
                    </h3>
                    <p class="text-muted mb-3" style="font-size: 0.865rem; max-width: 720px;">
                        Your database is currently fresh and empty. Follow these simple steps to configure your multi-tenant website platform:
                    </p>

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <div class="p-3 bg-white rounded-3 border h-100 shadow-xs">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-primary rounded-pill">1</span>
                                    <span class="fw-bold" style="font-size: 0.85rem;">Add a Customer</span>
                                </div>
                                <p class="text-muted mb-2" style="font-size: 0.785rem;">
                                    Create a client or organization account that will own websites.
                                </p>
                                <a href="{{ route('customers.create') }}" class="fw-semibold text-primary" style="font-size: 0.8rem;">
                                    Create Customer &rarr;
                                </a>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="p-3 bg-white rounded-3 border h-100 shadow-xs">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-primary rounded-pill">2</span>
                                    <span class="fw-bold" style="font-size: 0.85rem;">Create a Website</span>
                                </div>
                                <p class="text-muted mb-2" style="font-size: 0.785rem;">
                                    Register a website instance with provider settings and domains.
                                </p>
                                <a href="{{ route('websites.create') }}" class="fw-semibold text-primary" style="font-size: 0.8rem;">
                                    Add Website &rarr;
                                </a>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="p-3 bg-white rounded-3 border h-100 shadow-xs">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-primary rounded-pill">3</span>
                                    <span class="fw-bold" style="font-size: 0.85rem;">Configure Domains &amp; Themes</span>
                                </div>
                                <p class="text-muted mb-2" style="font-size: 0.785rem;">
                                    Verify custom domains with SSL and assign themes and templates.
                                </p>
                                <a href="{{ route('themes.index') }}" class="fw-semibold text-primary" style="font-size: 0.8rem;">
                                    Browse Themes &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Summary Cards Row --}}
<div class="row g-3 mb-4">
    {{-- Total Websites --}}
    <div class="col-12 col-sm-6 col-xl-2">
        <div class="cbh-stat-card">
            <div class="cbh-stat-header">
                <div>
                    <div class="cbh-stat-label">Websites</div>
                    <div class="cbh-stat-value">{{ number_format($metrics['total_websites']) }}</div>
                </div>
                <div class="cbh-stat-icon cbh-stat-icon-blue">
                    <i class="bi bi-globe2"></i>
                </div>
            </div>
            <div class="cbh-stat-footer">
                <a href="{{ route('websites.index') }}" style="color:var(--cbh-brand-600);font-weight:600;font-size:0.78rem;">
                    View all websites &rarr;
                </a>
            </div>
        </div>
    </div>

    {{-- Active Websites --}}
    <div class="col-12 col-sm-6 col-xl-2">
        <div class="cbh-stat-card">
            <div class="cbh-stat-header">
                <div>
                    <div class="cbh-stat-label">Active</div>
                    <div class="cbh-stat-value text-success">{{ number_format($metrics['active_websites']) }}</div>
                </div>
                <div class="cbh-stat-icon cbh-stat-icon-green">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
            <div class="cbh-stat-footer text-muted">
                @if ($metrics['total_websites'] > 0)
                    <span><strong>{{ $metrics['website_active_rate'] }}%</strong> operational rate</span>
                @else
                    <span>0 operational sites</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Total Customers --}}
    <div class="col-12 col-sm-6 col-xl-2">
        <div class="cbh-stat-card">
            <div class="cbh-stat-header">
                <div>
                    <div class="cbh-stat-label">Customers</div>
                    <div class="cbh-stat-value">{{ number_format($metrics['total_customers']) }}</div>
                </div>
                <div class="cbh-stat-icon cbh-stat-icon-purple">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
            <div class="cbh-stat-footer">
                <a href="{{ route('customers.index') }}" style="color:#7c3aed;font-weight:600;font-size:0.78rem;">
                    Client organizations &rarr;
                </a>
            </div>
        </div>
    </div>

    {{-- Total Themes & Templates --}}
    <div class="col-12 col-sm-6 col-xl-2">
        <div class="cbh-stat-card">
            <div class="cbh-stat-header">
                <div>
                    <div class="cbh-stat-label">Themes &amp; Templates</div>
                    <div class="cbh-stat-value">{{ number_format($metrics['total_themes'] + $metrics['total_templates']) }}</div>
                </div>
                <div class="cbh-stat-icon cbh-stat-icon-amber">
                    <i class="bi bi-palette-fill"></i>
                </div>
            </div>
            <div class="cbh-stat-footer text-muted">
                <span>{{ $metrics['total_themes'] }} Themes &middot; {{ $metrics['total_templates'] }} Templates</span>
            </div>
        </div>
    </div>

    {{-- Total Domains --}}
    <div class="col-12 col-sm-6 col-xl-2">
        <div class="cbh-stat-card">
            <div class="cbh-stat-header">
                <div>
                    <div class="cbh-stat-label">Domains</div>
                    <div class="cbh-stat-value">{{ number_format($metrics['total_domains']) }}</div>
                </div>
                <div class="cbh-stat-icon cbh-stat-icon-teal">
                    <i class="bi bi-hdd-network-fill"></i>
                </div>
            </div>
            <div class="cbh-stat-footer">
                <a href="{{ route('domains.index') }}" style="color:#0d9488;font-weight:600;font-size:0.78rem;">
                    Manage DNS records &rarr;
                </a>
            </div>
        </div>
    </div>

    {{-- Domain Verification Status --}}
    <div class="col-12 col-sm-6 col-xl-2">
        <div class="cbh-stat-card">
            <div class="cbh-stat-header">
                <div>
                    <div class="cbh-stat-label">Verified DNS</div>
                    <div class="cbh-stat-value" style="color: #0284c7;">{{ number_format($metrics['verified_domains']) }}</div>
                </div>
                <div class="cbh-stat-icon" style="background:#f0f9ff;color:#0284c7;">
                    <i class="bi bi-shield-check"></i>
                </div>
            </div>
            <div class="cbh-stat-footer text-muted">
                @if ($metrics['total_domains'] > 0)
                    <span><strong>{{ $metrics['domain_verification_rate'] }}%</strong> verified &middot; {{ $metrics['ssl_domains'] }} SSL</span>
                @else
                    <span>No active domains</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Main Grid: Left Column (Recent Websites & Customers) + Right Column (Status Overview & Quick Actions) --}}
<div class="row g-4">

    {{-- LEFT COLUMN: Tables & Activity Feeds --}}
    <div class="col-12 col-lg-8">

        {{-- 1. Recently Created or Updated Websites --}}
        <div class="cbh-card mb-4">
            <div class="cbh-card-header">
                <div>
                    <h2 class="cbh-card-title">Recent Websites</h2>
                    <p class="cbh-card-subtitle">Latest active and modified website properties</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('websites.create') }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm">
                        <i class="bi bi-plus-lg"></i>
                        Add Site
                    </a>
                    <a href="{{ route('websites.index') }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm">
                        View All
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="cbh-table-responsive">
                <table class="cbh-table">
                    <thead>
                        <tr>
                            <th>Website</th>
                            <th>Primary Domain</th>
                            <th>Customer</th>
                            <th>Provider</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentWebsites as $site)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="cbh-stat-icon cbh-stat-icon-blue" style="width: 32px; height: 32px; font-size: 0.85rem; border-radius: 6px;">
                                            <i class="bi bi-window-sidebar"></i>
                                        </div>
                                        <div>
                                            <a href="{{ route('websites.show', $site) }}" class="fw-bold text-dark text-decoration-none" style="font-size: 0.865rem;">
                                                {{ $site->website_name }}
                                            </a>
                                            <div class="text-muted" style="font-size: 0.72rem;">
                                                Updated {{ $site->updated_at?->diffForHumans() ?? 'recently' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="font-monospace text-secondary" style="font-size: 0.82rem;">
                                        {{ $site->domain }}
                                    </span>
                                </td>
                                <td>
                                    @if ($site->customer)
                                        <a href="{{ route('customers.show', $site->customer) }}" class="text-decoration-none fw-semibold" style="font-size: 0.82rem; color: var(--cbh-brand-600);">
                                            {{ $site->customer->name }}
                                        </a>
                                    @else
                                        <span class="text-muted" style="font-size: 0.785rem;">Unassigned</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="cbh-badge cbh-badge-neutral">
                                        <i class="bi bi-cloud me-1"></i>
                                        {{ ucfirst($site->provider) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($site->status === 1)
                                        <span class="cbh-badge cbh-badge-success">
                                            <i class="bi bi-circle-fill" style="font-size: 0.38rem;"></i>
                                            Active
                                        </span>
                                    @else
                                        <span class="cbh-badge cbh-badge-neutral">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('websites.show', $site) }}" class="cbh-btn cbh-btn-ghost cbh-btn-sm" title="Manage website">
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <x-empty-state
                                        icon="bi-globe2"
                                        title="No websites found"
                                        description="You have not created any website instances yet. Add your first website to begin building."
                                        actionText="Add New Website"
                                        actionUrl="{{ route('websites.create') }}"
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 2. Recent Customers Section --}}
        <div class="cbh-card">
            <div class="cbh-card-header">
                <div>
                    <h2 class="cbh-card-title">Recent Customers</h2>
                    <p class="cbh-card-subtitle">Client accounts and their associated website portfolios</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('customers.create') }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm">
                        <i class="bi bi-person-plus-fill"></i>
                        New Client
                    </a>
                    <a href="{{ route('customers.index') }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm">
                        View All
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="cbh-table-responsive">
                <table class="cbh-table">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Email Address</th>
                            <th>Websites Count</th>
                            <th>Registered</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentCustomers as $customer)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="cbh-stat-icon cbh-stat-icon-purple" style="width: 32px; height: 32px; font-size: 0.85rem; border-radius: 6px;">
                                            <i class="bi bi-person-badge"></i>
                                        </div>
                                        <div>
                                            <a href="{{ route('customers.show', $customer) }}" class="fw-bold text-dark text-decoration-none" style="font-size: 0.865rem;">
                                                {{ $customer->name }}
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted" style="font-size: 0.82rem;">{{ $customer->email }}</span>
                                </td>
                                <td>
                                    <span class="cbh-badge cbh-badge-primary">
                                        {{ $customer->websites_count }} {{ Str::plural('site', $customer->websites_count) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted" style="font-size: 0.8rem;">
                                        {{ $customer->created_at?->format('M d, Y') ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('customers.show', $customer) }}" class="cbh-btn cbh-btn-ghost cbh-btn-sm">
                                        Profile
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-empty-state
                                        icon="bi-people"
                                        title="No customers registered"
                                        description="There are currently no customer accounts in the database."
                                        actionText="Add Customer"
                                        actionUrl="{{ route('customers.create') }}"
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- RIGHT COLUMN: Status Overview, Hosting Providers & Quick Actions --}}
    <div class="col-12 col-lg-4">

        {{-- Quick Actions Box --}}
        <div class="cbh-card mb-4">
            <div class="cbh-card-header">
                <div>
                    <h3 class="cbh-card-title">Quick Actions</h3>
                    <p class="cbh-card-subtitle">Common administration shortcuts</p>
                </div>
            </div>
            <div class="cbh-card-body d-flex flex-column gap-2">
                <a href="{{ route('websites.create') }}" class="cbh-btn cbh-btn-secondary d-flex justify-content-between align-items-center py-2">
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-plus-circle-fill text-primary"></i>
                        <span>Create New Website</span>
                    </span>
                    <i class="bi bi-chevron-right text-muted" style="font-size: 0.75rem;"></i>
                </a>

                <a href="{{ route('customers.create') }}" class="cbh-btn cbh-btn-secondary d-flex justify-content-between align-items-center py-2">
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-plus-fill text-purple" style="color: #7c3aed;"></i>
                        <span>Add Customer Account</span>
                    </span>
                    <i class="bi bi-chevron-right text-muted" style="font-size: 0.75rem;"></i>
                </a>

                <a href="{{ route('domains.index') }}" class="cbh-btn cbh-btn-secondary d-flex justify-content-between align-items-center py-2">
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-hdd-network-fill text-teal" style="color: #0d9488;"></i>
                        <span>Verify Custom Domains</span>
                    </span>
                    <i class="bi bi-chevron-right text-muted" style="font-size: 0.75rem;"></i>
                </a>

                <a href="{{ route('themes.index') }}" class="cbh-btn cbh-btn-secondary d-flex justify-content-between align-items-center py-2">
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-palette-fill text-warning"></i>
                        <span>Theme Catalog</span>
                    </span>
                    <i class="bi bi-chevron-right text-muted" style="font-size: 0.75rem;"></i>
                </a>
            </div>
        </div>

        {{-- Website Status & Health Overview --}}
        <div class="cbh-card mb-4">
            <div class="cbh-card-header">
                <div>
                    <h3 class="cbh-card-title">Website Status Overview</h3>
                    <p class="cbh-card-subtitle">Fleet availability &amp; domain security</p>
                </div>
            </div>
            <div class="cbh-card-body">
                {{-- Active Sites Progress --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.8rem;">
                        <span class="fw-semibold text-dark">Active Websites Ratio</span>
                        <span class="text-muted">{{ $metrics['active_websites'] }} / {{ $metrics['total_websites'] }} ({{ $metrics['website_active_rate'] }}%)</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $metrics['website_active_rate'] }}%;" aria-valuenow="{{ $metrics['website_active_rate'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                {{-- Domain Verification Progress --}}
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.8rem;">
                        <span class="fw-semibold text-dark">Domain Verification Rate</span>
                        <span class="text-muted">{{ $metrics['verified_domains'] }} / {{ $metrics['total_domains'] }} ({{ $metrics['domain_verification_rate'] }}%)</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $metrics['domain_verification_rate'] }}%;" aria-valuenow="{{ $metrics['domain_verification_rate'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                {{-- Hosting Provider Distribution --}}
                <div class="pt-2 border-top">
                    <div class="fw-bold text-dark mb-2" style="font-size: 0.8rem;">
                        Hosting Infrastructure
                    </div>
                    @forelse ($providers as $item)
                        <div class="d-flex justify-content-between align-items-center py-1" style="font-size: 0.8rem;">
                            <span class="d-flex align-items-center gap-2">
                                <i class="bi bi-hdd-stack text-muted" style="font-size: 0.75rem;"></i>
                                <span class="text-capitalize">{{ $item->provider }}</span>
                            </span>
                            <span class="cbh-badge cbh-badge-neutral">{{ $item->count }} {{ Str::plural('site', $item->count) }}</span>
                        </div>
                    @empty
                        <div class="text-muted text-center py-2" style="font-size: 0.78rem;">
                            No providers configured
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Platform System Architecture Card --}}
        <div class="cbh-card" style="border-color: var(--cbh-brand-200); background: var(--cbh-brand-50);">
            <div class="cbh-card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="cbh-brand-mark" style="width: 28px; height: 28px; font-size: 0.9rem;">
                        <i class="bi bi-shield-shaded"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size: 0.885rem; color: var(--cbh-brand-700);">CodeBaseHub Core</div>
                        <div style="font-size: 0.7rem; color: var(--cbh-brand-600);">Multi-Tenant Builder System</div>
                    </div>
                </div>
                <div class="d-flex flex-column gap-1 text-muted" style="font-size: 0.785rem;">
                    <div class="d-flex justify-content-between border-bottom py-1" style="border-color: var(--cbh-brand-100)!important;">
                        <span>Laravel Framework</span>
                        <strong>v{{ app()->version() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-1" style="border-color: var(--cbh-brand-100)!important;">
                        <span>PHP Engine</span>
                        <strong>v{{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span>Database Engine</span>
                        <strong>SQLite (Normalized)</strong>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
