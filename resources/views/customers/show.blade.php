@extends('layouts.dashboard')

@section('title', $customer->name)
@section('breadcrumb', $customer->name)

@section('content')
<div class="cbh-page-header">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('customers.index') }}" class="cbh-btn cbh-btn-secondary cbh-btn-icon" title="Back to Customers">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="cbh-page-title">{{ $customer->name }}</h1>
                <span class="cbh-badge cbh-badge-primary">#CST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</span>
            </div>
            <p class="cbh-page-subtitle">{{ $customer->email }} &middot; Registered {{ $customer->created_at?->format('F d, Y') }}</p>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('customers.edit', $customer) }}" class="cbh-btn cbh-btn-secondary">
            <i class="bi bi-pencil"></i>
            Edit Profile
        </a>
        <button type="button" class="cbh-btn cbh-btn-danger" data-bs-toggle="modal" data-bs-target="#deleteCustomerModal">
            <i class="bi bi-trash"></i>
            Delete
        </button>
        <a href="{{ route('websites.create') }}?customer_id={{ $customer->id }}" class="cbh-btn cbh-btn-primary">
            <i class="bi bi-plus-lg"></i>
            Deploy Site
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-md-4">
        <div class="cbh-card h-100">
            <div class="cbh-card-header">
                <h3 class="cbh-card-title">Customer Credentials</h3>
            </div>
            <div class="cbh-card-body d-flex flex-column gap-2" style="font-size: 0.85rem;">
                <div>
                    <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 600;">Contact Email</div>
                    <div class="fw-semibold text-dark">{{ $customer->email }}</div>
                </div>
                <div>
                    <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 600;">Email Verified</div>
                    <div>
                        @if ($customer->email_verified_at)
                            <span class="cbh-badge cbh-badge-success">
                                <i class="bi bi-check-circle-fill me-1" style="font-size: 0.38rem;"></i>
                                Verified ({{ $customer->email_verified_at->format('M d, Y') }})
                            </span>
                        @else
                            <span class="cbh-badge cbh-badge-warning">Unverified</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase; font-weight: 600;">Registered Date</div>
                    <div class="text-secondary">{{ $customer->created_at?->format('M d, Y H:i') ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-8">
        {{-- Associated Websites --}}
        <div class="cbh-card h-100">
            <div class="cbh-card-header">
                <div>
                    <h2 class="cbh-card-title">Customer Websites Portfolio</h2>
                    <p class="cbh-card-subtitle">All active website instances owned by {{ $customer->name }} ({{ $customer->websites->count() }})</p>
                </div>
            </div>

            <div class="cbh-table-responsive">
                <table class="cbh-table">
                    <thead>
                        <tr>
                            <th>Website Name</th>
                            <th>Domain</th>
                            <th>Hosting Provider</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customer->websites as $site)
                            <tr>
                                <td>
                                    <a href="{{ route('websites.show', $site) }}" class="fw-bold text-dark text-decoration-none">
                                        {{ $site->website_name }}
                                    </a>
                                </td>
                                <td>
                                    <span class="font-monospace text-secondary" style="font-size: 0.82rem;">{{ $site->domain }}</span>
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
                                    <a href="{{ route('websites.show', $site) }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm">
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-empty-state
                                        icon="bi-globe2"
                                        title="No websites assigned"
                                        description="This customer does not have any deployed websites yet."
                                        actionText="Create Website for Customer"
                                        actionUrl="{{ route('websites.create') }}?customer_id={{ $customer->id }}"
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
<div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-labelledby="deleteCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px;border-color:var(--cbh-border);">
            <div class="modal-header" style="border-bottom:1px solid var(--cbh-border);padding:1.25rem 1.5rem 1rem;">
                <h5 class="modal-title" id="deleteCustomerModalLabel" style="font-size:1rem;font-weight:700;color:#b91c1c;display:flex;align-items:center;gap:0.5rem;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Confirm Customer Deletion
                </h5>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('customers.destroy', $customer) }}">
                @csrf
                @method('DELETE')
                <div class="modal-body" style="padding:1rem 1.5rem;">
                    <p style="margin:0 0 0.5rem;font-size:0.9rem;">
                        Are you sure you want to delete customer <strong>{{ $customer->name }}</strong>?
                    </p>
                    <p style="font-size:0.82rem;color:var(--cbh-neutral-500);margin:0;">
                        @if ($customer->websites->count() > 0)
                            <strong>Notice:</strong> This customer currently owns <strong>{{ $customer->websites->count() }} website(s)</strong>.
                            Under the database integrity rules, those websites will be safely preserved and their ownership will be set to unassigned.
                        @else
                            This customer has no assigned websites. Deletion will cleanly remove this customer account.
                        @endif
                    </p>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--cbh-border);padding:1rem 1.5rem;gap:0.5rem;">
                    <button type="button" class="cbh-btn cbh-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="cbh-btn cbh-btn-danger">
                        <i class="bi bi-trash"></i>
                        Delete Customer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
