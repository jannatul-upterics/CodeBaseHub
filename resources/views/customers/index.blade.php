@extends('layouts.dashboard')

@section('title', 'Customers')
@section('breadcrumb', 'Customers')

@section('content')
<div class="cbh-page-header">
    <div>
        <h1 class="cbh-page-title">
            Customers
            <span class="cbh-badge cbh-badge-primary ms-1" style="vertical-align: middle;">Directory</span>
        </h1>
        <p class="cbh-page-subtitle">Manage client organizations and tenant accounts owning website instances.</p>
    </div>
    <a href="{{ route('customers.create') }}" class="cbh-btn cbh-btn-primary">
        <i class="bi bi-person-plus-fill"></i>
        New Customer
    </a>
</div>

{{-- Search & Filter Toolbar --}}
<div class="cbh-card mb-4">
    <div class="cbh-card-body p-3">
        <form method="GET" action="{{ route('customers.index') }}" class="cbh-toolbar">
            <div class="cbh-search-wrap flex-grow-1" style="min-width: 240px;">
                <i class="bi bi-search cbh-search-icon"></i>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       class="cbh-input cbh-search-input"
                       placeholder="Search customer by name or email…"
                       aria-label="Search customers">
            </div>

            <select name="portfolio" class="cbh-select" style="min-width: 170px; max-width: 220px;" onchange="this.form.submit()" aria-label="Filter by portfolio">
                <option value="">All Accounts</option>
                <option value="has_sites" {{ ($portfolio ?? '') === 'has_sites' ? 'selected' : '' }}>With Websites</option>
                <option value="no_sites" {{ ($portfolio ?? '') === 'no_sites' ? 'selected' : '' }}>Without Websites</option>
            </select>

            <button type="submit" class="cbh-btn cbh-btn-primary">
                <i class="bi bi-search"></i>
                Search
            </button>

            @if ($search !== '' || ($portfolio ?? '') !== '')
                <a href="{{ route('customers.index') }}" class="cbh-btn cbh-btn-secondary">
                    <i class="bi bi-x-circle"></i>
                    Clear
                </a>
            @endif
        </form>
    </div>
</div>

{{-- Customers Table --}}
<div class="cbh-card">
    <div class="cbh-card-header">
        <div>
            <h2 class="cbh-card-title">Customer Accounts</h2>
            <p class="cbh-card-subtitle">
                Showing {{ $customers->firstItem() ?? 0 }}–{{ $customers->lastItem() ?? 0 }} of {{ $customers->total() }} {{ Str::plural('account', $customers->total()) }}
            </p>
        </div>
    </div>

    <div class="cbh-table-responsive">
        <table class="cbh-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Email Address</th>
                    <th>Managed Websites</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="cbh-stat-icon cbh-stat-icon-purple" style="width: 32px; height: 32px; font-size: 0.85rem; border-radius: 6px;">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                                <div>
                                    <a href="{{ route('customers.show', $customer) }}" class="fw-bold text-dark text-decoration-none">
                                        {{ $customer->name }}
                                    </a>
                                    <div class="text-muted" style="font-size: 0.72rem;">#CST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="text-secondary">{{ $customer->email }}</span>
                        </td>
                        <td>
                            <span class="cbh-badge cbh-badge-primary">
                                <i class="bi bi-globe2 me-1"></i>
                                {{ $customer->websites_count }} {{ Str::plural('website', $customer->websites_count) }}
                            </span>
                        </td>
                        <td>
                            <span class="text-muted" style="font-size: 0.82rem;">
                                {{ $customer->created_at?->format('M d, Y') ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex align-items-center gap-1">
                                <a href="{{ route('customers.show', $customer) }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm" title="View details and websites">
                                    View
                                </a>
                                <a href="{{ route('customers.edit', $customer) }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm" title="Edit customer">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-empty-state
                                icon="bi-people"
                                title="No customers found"
                                description="No customer accounts match your search criteria. Create a customer to begin organizing sites."
                                actionText="Add Customer"
                                actionUrl="{{ route('customers.create') }}"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($customers->hasPages())
        <div class="cbh-card-footer">
            {{ $customers->links() }}
        </div>
    @endif
</div>
@endsection
