@extends('layouts.dashboard')

@section('title', 'Domains & DNS — ' . $website->website_name)
@section('breadcrumb', 'Domains & DNS')

@section('content')
@include('websites._header', ['activeTab' => 'domains'])

{{-- Add Domain & DNS Instructions Row --}}
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-7">
        <div class="cbh-card">
            <div class="cbh-card-header">
                <div>
                    <h2 class="cbh-card-title">Add Custom Domain</h2>
                    <p class="cbh-card-subtitle">Map an apex domain or subdomain to {{ $website->website_name }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('websites.domains.store', $website) }}" novalidate>
                @csrf
                <div class="cbh-card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label for="domain_name" class="cbh-form-label">
                                Domain Hostname <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   id="domain_name"
                                   name="domain_name"
                                   value="{{ old('domain_name') }}"
                                   class="cbh-input font-monospace @error('domain_name') is-invalid @enderror"
                                   placeholder="e.g. portal.mybrand.com"
                                   required>
                            @error('domain_name')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="status" class="cbh-form-label">Status</label>
                            <select id="status" name="status" class="cbh-select @error('status') is-invalid @enderror">
                                <option value="1" selected>Active</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       role="switch"
                                       id="is_primary"
                                       name="is_primary"
                                       value="1"
                                       {{ old('is_primary') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_primary">
                                    Set as Primary Routing Domain
                                </label>
                                <div class="form-text" style="font-size:0.75rem;">
                                    If checked, the main domain for {{ $website->website_name }} will automatically update to this host.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cbh-card-footer bg-light border-top p-3 d-flex justify-content-end">
                    <button type="submit" class="cbh-btn cbh-btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        Register Domain
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- DNS Setup Guidance --}}
    <div class="col-12 col-lg-5">
        <div class="cbh-card h-100">
            <div class="cbh-card-header">
                <h3 class="cbh-card-title">DNS Configuration Records</h3>
            </div>
            <div class="cbh-card-body p-4" style="font-size:0.835rem;">
                <p class="text-secondary mb-3">To connect your custom domain to this CodeBaseHub fleet node, configure either of these records at your DNS registrar:</p>
                <div class="p-2 mb-2 bg-light rounded border font-monospace small">
                    <div class="d-flex justify-content-between"><span class="text-muted">Type:</span> <strong>A</strong></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Host:</span> <strong>@</strong></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Points To:</span> <strong class="text-primary">192.0.2.1</strong></div>
                </div>
                <div class="p-2 mb-3 bg-light rounded border font-monospace small">
                    <div class="d-flex justify-content-between"><span class="text-muted">Type:</span> <strong>CNAME</strong></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Host:</span> <strong>www</strong></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Points To:</span> <strong class="text-primary">nodes.codebasehub.com</strong></div>
                </div>
                <div class="d-flex align-items-center gap-2 text-muted" style="font-size:0.75rem;">
                    <i class="bi bi-shield-check text-success"></i>
                    SSL certificates are issued automatically upon successful DNS resolution.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Registered Domains Table --}}
<div class="cbh-card">
    <div class="cbh-card-header">
        <div>
            <h2 class="cbh-card-title">Registered Domains</h2>
            <p class="cbh-card-subtitle">{{ $domains->count() }} {{ Str::plural('domain', $domains->count()) }} configured for {{ $website->website_name }}</p>
        </div>
    </div>

    <div class="cbh-table-responsive">
        <table class="cbh-table">
            <thead>
                <tr>
                    <th>Domain Name</th>
                    <th>Type</th>
                    <th>DNS Verification</th>
                    <th>SSL Encryption</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($domains as $d)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-hdd-network text-teal" style="font-size:1.1rem; color:#0d9488;"></i>
                                <span class="fw-bold font-monospace text-dark" style="font-size:0.885rem;">
                                    {{ $d->domain_name }}
                                </span>
                            </div>
                        </td>
                        <td>
                            @if ($d->is_primary)
                                <span class="cbh-badge cbh-badge-primary">
                                    <i class="bi bi-star-fill me-1" style="font-size:0.4rem;"></i>
                                    Primary Domain
                                </span>
                            @else
                                <span class="cbh-badge cbh-badge-neutral">Alias</span>
                            @endif
                        </td>
                        <td>
                            @if ($d->verified_at)
                                <span class="cbh-badge cbh-badge-success">
                                    <i class="bi bi-check-circle-fill me-1" style="font-size:0.4rem;"></i>
                                    Verified ({{ $d->verified_at->format('M j, Y') }})
                                </span>
                            @else
                                <span class="cbh-badge cbh-badge-warning">
                                    <i class="bi bi-clock-history me-1" style="font-size:0.4rem;"></i>
                                    Pending Verification
                                </span>
                            @endif
                        </td>
                        <td>
                            @if ($d->ssl_enabled)
                                <span class="cbh-badge cbh-badge-success">
                                    <i class="bi bi-shield-lock-fill me-1" style="font-size:0.4rem;"></i>
                                    Active SSL
                                </span>
                            @else
                                <span class="cbh-badge cbh-badge-neutral">No SSL</span>
                            @endif
                        </td>
                        <td>
                            @if ($d->status === 1)
                                <span class="cbh-badge cbh-badge-success">Active</span>
                            @else
                                <span class="cbh-badge cbh-badge-neutral">Disabled</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                {{-- Make Primary Button --}}
                                @if (!$d->is_primary)
                                    <form method="POST" action="{{ route('websites.domains.primary', [$website, $d]) }}" class="m-0">
                                        @csrf
                                        <button type="submit" class="cbh-btn cbh-btn-secondary cbh-btn-sm" title="Set as primary domain">
                                            <i class="bi bi-star"></i>
                                            Set Primary
                                        </button>
                                    </form>
                                @endif

                                {{-- Verify DNS Button --}}
                                <form method="POST" action="{{ route('websites.domains.verify', [$website, $d]) }}" class="m-0">
                                    @csrf
                                    <input type="hidden" name="force_verify" value="1">
                                    <button type="submit" class="cbh-btn cbh-btn-secondary cbh-btn-sm" title="Verify DNS records and test SSL">
                                        <i class="bi bi-arrow-repeat"></i>
                                        Verify
                                    </button>
                                </form>

                                {{-- Edit Modal Trigger --}}
                                <button type="button"
                                        class="cbh-btn cbh-btn-secondary cbh-btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editDomainModal-{{ $d->id }}"
                                        title="Edit Domain">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                {{-- Delete Modal Trigger --}}
                                <button type="button"
                                        class="cbh-btn cbh-btn-danger cbh-btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteDomainModal-{{ $d->id }}"
                                        title="Delete Domain">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>

                            {{-- Edit Domain Modal --}}
                            <div class="modal fade" id="editDomainModal-{{ $d->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content" style="text-align:left; border-radius:12px;">
                                        <div class="modal-header border-bottom">
                                            <h5 class="modal-title fw-bold">Edit Domain: {{ $d->domain_name }}</h5>
                                            <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="{{ route('websites.domains.update', [$website, $d]) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body p-4">
                                                <div class="mb-3">
                                                    <label class="cbh-form-label">Domain Name <span class="text-danger">*</span></label>
                                                    <input type="text" name="domain_name" class="cbh-input font-monospace" value="{{ old('domain_name', $d->domain_name) }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="cbh-form-label">Status</label>
                                                    <select name="status" class="cbh-select">
                                                        <option value="1" {{ old('status', (string)$d->status) === '1' ? 'selected' : '' }}>Active</option>
                                                        <option value="0" {{ old('status', (string)$d->status) === '0' ? 'selected' : '' }}>Disabled</option>
                                                    </select>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="is_primary_{{ $d->id }}" name="is_primary" value="1" {{ old('is_primary', $d->is_primary) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="is_primary_{{ $d->id }}">
                                                        Primary Routing Domain
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top">
                                                <button type="button" class="cbh-btn cbh-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="cbh-btn cbh-btn-primary">
                                                    <i class="bi bi-check-lg"></i>
                                                    Update Domain
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- Delete Domain Modal --}}
                            <div class="modal fade" id="deleteDomainModal-{{ $d->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content" style="text-align:left; border-radius:12px;">
                                        <div class="modal-header border-bottom">
                                            <h5 class="modal-title fw-bold text-danger">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                Delete Domain
                                            </h5>
                                            <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="{{ route('websites.domains.destroy', [$website, $d]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <div class="modal-body py-3">
                                                <p class="mb-1">Are you sure you want to remove domain <strong>{{ $d->domain_name }}</strong>?</p>
                                                <p class="text-muted small mb-0">Incoming traffic to this hostname will no longer route to {{ $website->website_name }}.</p>
                                            </div>
                                            <div class="modal-footer border-top">
                                                <button type="button" class="cbh-btn cbh-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="cbh-btn cbh-btn-primary" style="background:#dc2626;border-color:#dc2626;">
                                                    <i class="bi bi-trash"></i>
                                                    Delete Domain
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
                        <td colspan="6">
                            <x-empty-state
                                icon="bi-hdd-network"
                                title="No domains registered"
                                description="Configure a primary hostname for this website."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
