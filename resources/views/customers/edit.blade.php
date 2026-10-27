@extends('layouts.dashboard')

@section('title', 'Edit Customer')
@section('breadcrumb', 'Edit Customer')

@section('content')
<div class="cbh-page-header">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('customers.show', $customer) }}" class="cbh-btn cbh-btn-secondary cbh-btn-icon" title="Back to Customer">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="cbh-page-title">Edit Customer Profile</h1>
            <p class="cbh-page-subtitle">Update credentials and organization details for {{ $customer->name }}.</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="cbh-card">
            <div class="cbh-card-header">
                <h3 class="cbh-card-title">Customer Information</h3>
            </div>

            <div class="cbh-card-body">
                <form method="POST" action="{{ route('customers.update', $customer) }}">
                    @csrf
                    @method('PUT')

                    {{-- Customer Name --}}
                    <div class="mb-3">
                        <label for="name" class="cbh-label">Customer or Company Name <span class="text-danger">*</span></label>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name', $customer->name) }}"
                               class="cbh-input @error('name') is-invalid @enderror"
                               placeholder="e.g. Acme Corporation"
                               required
                               autofocus>
                        @error('name')
                            <div class="cbh-form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Email Address --}}
                    <div class="mb-3">
                        <label for="email" class="cbh-label">Contact Email Address <span class="text-danger">*</span></label>
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email', $customer->email) }}"
                               class="cbh-input @error('email') is-invalid @enderror"
                               placeholder="e.g. admin@acmecorp.com"
                               required>
                        @error('email')
                            <div class="cbh-form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="mb-4">
                        <label for="password" class="cbh-label">New Password (optional)</label>
                        <input type="password"
                               id="password"
                               name="password"
                               class="cbh-input @error('password') is-invalid @enderror"
                               placeholder="Leave blank to keep existing password unchanged">
                        <div class="cbh-form-hint">Leave blank to retain current password. Minimum 8 characters if changing.</div>
                        @error('password')
                            <div class="cbh-form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex align-items-center gap-2 pt-2 border-top">
                        <button type="submit" class="cbh-btn cbh-btn-primary">
                            <i class="bi bi-check-lg"></i>
                            Save Changes
                        </button>
                        <a href="{{ route('customers.show', $customer) }}" class="cbh-btn cbh-btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
