@extends('layouts.dashboard')

@section('title', 'Add Website')
@section('breadcrumb', 'Add Website')

@section('content')
{{-- Page Header --}}
<div class="cbh-page-header">
    <div style="display:flex;align-items:center;gap:0.75rem;">
        <a href="{{ route('websites.index') }}"
           class="cbh-btn cbh-btn-secondary cbh-btn-icon"
           title="Back to Websites">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="cbh-page-title">Add Website</h1>
            <p class="cbh-page-subtitle">Register a new website with the platform.</p>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('websites.store') }}" id="createWebsiteForm">
    @csrf

    <div class="row g-4">
        {{-- Main Fields --}}
        <div class="col-12 col-lg-8">
            <div class="cbh-card">
                <div class="cbh-card-header">
                    <div>
                        <h2 class="cbh-card-title">Website Details</h2>
                        <p class="cbh-card-subtitle">Core information about this website.</p>
                    </div>
                </div>
                <div class="cbh-card-body">
                    {{-- Website Name --}}
                    <div class="cbh-form-group">
                        <label for="website_name" class="cbh-label">
                            Website Name <span class="required">*</span>
                        </label>
                        <input type="text"
                               id="website_name"
                               name="website_name"
                               value="{{ old('website_name') }}"
                               class="cbh-input {{ $errors->has('website_name') ? 'is-invalid' : '' }}"
                               placeholder="e.g. Acme Corporate Site"
                               required
                               autocomplete="off">
                        @error('website_name')
                            <p class="cbh-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Domain --}}
                    <div class="cbh-form-group">
                        <label for="domain" class="cbh-label">
                            Primary Domain <span class="required">*</span>
                        </label>
                        <input type="text"
                               id="domain"
                               name="domain"
                               value="{{ old('domain') }}"
                               class="cbh-input {{ $errors->has('domain') ? 'is-invalid' : '' }}"
                               placeholder="e.g. acme.com"
                               required>
                        <p class="cbh-field-hint">Enter the bare domain without http:// or www.</p>
                        @error('domain')
                            <p class="cbh-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="cbh-form-group">
                        <label for="description" class="cbh-label">Description</label>
                        <textarea id="description"
                                  name="description"
                                  class="cbh-textarea {{ $errors->has('description') ? 'is-invalid' : '' }}"
                                  placeholder="Brief description of this website's purpose…"
                                  rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="cbh-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Notes --}}
                    <div class="cbh-form-group" style="margin-bottom:0;">
                        <label for="notes" class="cbh-label">Internal Notes</label>
                        <textarea id="notes"
                                  name="notes"
                                  class="cbh-textarea {{ $errors->has('notes') ? 'is-invalid' : '' }}"
                                  placeholder="Technical notes, deployment info, caveats…"
                                  rows="3">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="cbh-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Side Panel --}}
        <div class="col-12 col-lg-4">

            {{-- Provider & Customer --}}
            <div class="cbh-card mb-4">
                <div class="cbh-card-header">
                    <h2 class="cbh-card-title">Configuration</h2>
                </div>
                <div class="cbh-card-body">
                    {{-- Provider --}}
                    <div class="cbh-form-group">
                        <label for="provider" class="cbh-label">
                            Hosting Provider <span class="required">*</span>
                        </label>
                        <select id="provider"
                                name="provider"
                                class="cbh-select {{ $errors->has('provider') ? 'is-invalid' : '' }}"
                                required>
                            <option value="">Select provider…</option>
                            @foreach(['cloud', 'aws', 'forge', 'digitalocean', 'vercel', 'netlify', 'other'] as $pv)
                                <option value="{{ $pv }}" {{ old('provider') === $pv ? 'selected' : '' }}>
                                    {{ ucfirst($pv) }}
                                </option>
                            @endforeach
                        </select>
                        @error('provider')
                            <p class="cbh-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Customer --}}
                    <div class="cbh-form-group">
                        <label for="customer_id" class="cbh-label">Customer Organization</label>
                        <select id="customer_id"
                                name="customer_id"
                                class="cbh-select {{ $errors->has('customer_id') ? 'is-invalid' : '' }}">
                            <option value="">No customer assigned</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}"
                                        {{ old('customer_id', $selectedCustomerId ?? '') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <p class="cbh-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Theme Selection --}}
                    <div class="cbh-form-group" style="margin-bottom:0;">
                        <label for="theme_id" class="cbh-label">Design Theme</label>
                        <select id="theme_id"
                                name="theme_id"
                                class="cbh-select {{ $errors->has('theme_id') ? 'is-invalid' : '' }}">
                            <option value="">Select theme (optional)…</option>
                            @foreach ($themes as $theme)
                                <option value="{{ $theme->id }}" {{ old('theme_id') == $theme->id ? 'selected' : '' }}>
                                    {{ $theme->theme_name }} (v{{ $theme->version ?? '1.0' }})
                                </option>
                            @endforeach
                        </select>
                        <p class="cbh-field-hint">Stores theme pairing in website_theme_settings.</p>
                        @error('theme_id')
                            <p class="cbh-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Status --}}
            <div class="cbh-card mb-4">
                <div class="cbh-card-header">
                    <h2 class="cbh-card-title">Status</h2>
                </div>
                <div class="cbh-card-body">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <div style="font-size:0.875rem;font-weight:600;color:var(--cbh-neutral-700);">Active</div>
                            <div style="font-size:0.78rem;color:var(--cbh-neutral-500);">Website is live and visible</div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input"
                                   type="checkbox"
                                   role="switch"
                                   id="statusToggle"
                                   name="status"
                                   value="1"
                                   {{ old('status', '1') === '1' ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div style="display:flex;flex-direction:column;gap:0.5rem;">
                <button type="submit" class="cbh-btn cbh-btn-primary" style="justify-content:center;">
                    <i class="bi bi-check-lg"></i>
                    Create Website
                </button>
                <a href="{{ route('websites.index') }}" class="cbh-btn cbh-btn-secondary" style="justify-content:center;">
                    Cancel
                </a>
            </div>
        </div>
    </div>
</form>
@endsection
