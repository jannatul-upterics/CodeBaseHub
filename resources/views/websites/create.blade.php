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
            <div class="cbh-card mb-4">
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

            {{-- Tenant Database Configuration Section --}}
            <div class="cbh-card" id="tenantDatabaseCard">
                <div class="cbh-card-header">
                    <div>
                        <h2 class="cbh-card-title">
                            <i class="bi bi-database me-2" style="color:var(--cbh-brand-500);"></i>
                            Tenant Database Configuration
                        </h2>
                        <p class="cbh-card-subtitle">Choose whether to automatically provision a new tenant database or connect an existing database.</p>
                    </div>
                </div>
                <div class="cbh-card-body">
                    {{-- Mode Selection Cards --}}
                    <div class="row g-3 mb-4">
                        {{-- Option 1: Create New Database --}}
                        <div class="col-12 col-md-6">
                            <label class="cbh-selection-card h-100 p-3 d-flex align-items-start gap-3 rounded cursor-pointer"
                                   id="cardModeCreate"
                                   for="mode_create"
                                   style="border: 2px solid {{ old('tenant_db_mode', 'create') === 'create' ? 'var(--cbh-brand-500)' : 'var(--cbh-neutral-200)' }}; background: {{ old('tenant_db_mode', 'create') === 'create' ? 'var(--cbh-brand-50)' : '#ffffff' }}; cursor: pointer; transition: all 0.2s ease;">
                                <div class="pt-1">
                                    <input type="radio"
                                           name="tenant_db_mode"
                                           id="mode_create"
                                           value="create"
                                           class="form-check-input"
                                           {{ old('tenant_db_mode', 'create') === 'create' ? 'checked' : '' }}
                                           onchange="cbhToggleTenantDbMode('create')">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-database-add text-primary" style="font-size:1.15rem;"></i>
                                        <strong style="color:var(--cbh-neutral-800);font-size:0.95rem;">Create New Database</strong>
                                    </div>
                                    <p class="mb-0 text-muted" style="font-size:0.8rem;line-height:1.4;">
                                        Automatically creates a new separate database and executes all tenant migrations.
                                    </p>
                                </div>
                            </label>
                        </div>

                        {{-- Option 2: Connect Existing Database --}}
                        <div class="col-12 col-md-6">
                            <label class="cbh-selection-card h-100 p-3 d-flex align-items-start gap-3 rounded cursor-pointer"
                                   id="cardModeConnect"
                                   for="mode_connect"
                                   style="border: 2px solid {{ old('tenant_db_mode') === 'connect' ? 'var(--cbh-brand-500)' : 'var(--cbh-neutral-200)' }}; background: {{ old('tenant_db_mode') === 'connect' ? 'var(--cbh-brand-50)' : '#ffffff' }}; cursor: pointer; transition: all 0.2s ease;">
                                <div class="pt-1">
                                    <input type="radio"
                                           name="tenant_db_mode"
                                           id="mode_connect"
                                           value="connect"
                                           class="form-check-input"
                                           {{ old('tenant_db_mode') === 'connect' ? 'checked' : '' }}
                                           onchange="cbhToggleTenantDbMode('connect')">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="bi bi-database-check text-success" style="font-size:1.15rem;"></i>
                                        <strong style="color:var(--cbh-neutral-800);font-size:0.95rem;">Connect Existing Database</strong>
                                    </div>
                                    <p class="mb-0 text-muted" style="font-size:0.8rem;line-height:1.4;">
                                        Safely attach an existing database without modifying, dropping, or overwriting existing data.
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Form Fields for Option 1: Create New Database --}}
                    <div id="fieldsCreateDatabase" style="display: {{ old('tenant_db_mode', 'create') === 'create' ? 'block' : 'none' }};">
                        <div class="row g-3">
                            {{-- Database Name (optional for create) --}}
                            <div class="col-12">
                                <div class="cbh-form-group mb-0">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label for="create_db_name" class="cbh-label mb-0">Database Name</label>
                                        <span class="cbh-badge cbh-badge-neutral" style="font-size:0.7rem;">Optional (Auto-generated if blank)</span>
                                    </div>
                                    <input type="text"
                                           id="create_db_name"
                                           name="create_db_name"
                                           value="{{ old('create_db_name', old('tenant_db_mode') === 'create' ? old('db_name') : '') }}"
                                           class="cbh-input {{ $errors->has('db_name') && old('tenant_db_mode') === 'create' ? 'is-invalid' : '' }}"
                                           placeholder="e.g. codebasehub_tenant_site (Leave blank to generate automatically)">
                                    <p class="cbh-field-hint">Leave blank to automatically generate a unique, conflict-free database name.</p>
                                    @if(old('tenant_db_mode') === 'create')
                                        @error('db_name')
                                            <p class="cbh-field-error">{{ $message }}</p>
                                        @enderror
                                    @endif
                                </div>
                            </div>

                            {{-- Host & Port --}}
                            <div class="col-12 col-md-8">
                                <div class="cbh-form-group mb-0">
                                    <label for="create_db_host" class="cbh-label">Database Host <span class="required">*</span></label>
                                    <input type="text"
                                           id="create_db_host"
                                           name="create_db_host"
                                           value="{{ old('create_db_host', old('tenant_db_mode') === 'create' ? old('db_host', '127.0.0.1') : '127.0.0.1') }}"
                                           class="cbh-input"
                                           placeholder="127.0.0.1">
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="cbh-form-group mb-0">
                                    <label for="create_db_port" class="cbh-label">Database Port <span class="required">*</span></label>
                                    <input type="number"
                                           id="create_db_port"
                                           name="create_db_port"
                                           value="{{ old('create_db_port', old('tenant_db_mode') === 'create' ? old('db_port', 3306) : 3306) }}"
                                           class="cbh-input"
                                           placeholder="3306">
                                </div>
                            </div>

                            {{-- Username & Password --}}
                            <div class="col-12 col-md-6">
                                <div class="cbh-form-group mb-0">
                                    <label for="create_db_username" class="cbh-label">Database Username <span class="required">*</span></label>
                                    <input type="text"
                                           id="create_db_username"
                                           name="create_db_username"
                                           value="{{ old('create_db_username', old('tenant_db_mode') === 'create' ? old('db_username', 'root') : 'root') }}"
                                           class="cbh-input"
                                           placeholder="root">
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="cbh-form-group mb-0">
                                    <label for="create_db_password" class="cbh-label">Database Password</label>
                                    <div class="input-group">
                                        <input type="password"
                                               id="create_db_password"
                                               name="create_db_password"
                                               value="{{ old('create_db_password', old('tenant_db_mode') === 'create' ? old('db_password') : '') }}"
                                               class="cbh-input"
                                               placeholder="Enter password (if required)">
                                        <button class="btn btn-outline-secondary"
                                                type="button"
                                                onclick="cbhTogglePassword('create_db_password', this)"
                                                title="Toggle visibility">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Form Fields for Option 2: Connect Existing Database --}}
                    <div id="fieldsConnectDatabase" style="display: {{ old('tenant_db_mode') === 'connect' ? 'block' : 'none' }};">
                        <div class="row g-3">
                            {{-- Database Host & Port --}}
                            <div class="col-12 col-md-8">
                                <div class="cbh-form-group mb-0">
                                    <label for="connect_db_host" class="cbh-label">Database Host <span class="required">*</span></label>
                                    <input type="text"
                                           id="connect_db_host"
                                           name="connect_db_host"
                                           value="{{ old('connect_db_host', old('tenant_db_mode') === 'connect' ? old('db_host', '127.0.0.1') : '127.0.0.1') }}"
                                           class="cbh-input"
                                           placeholder="127.0.0.1">
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="cbh-form-group mb-0">
                                    <label for="connect_db_port" class="cbh-label">Database Port <span class="required">*</span></label>
                                    <input type="number"
                                           id="connect_db_port"
                                           name="connect_db_port"
                                           value="{{ old('connect_db_port', old('tenant_db_mode') === 'connect' ? old('db_port', 3306) : 3306) }}"
                                           class="cbh-input"
                                           placeholder="3306">
                                </div>
                            </div>

                            {{-- Database Name (Required for connect) --}}
                            <div class="col-12">
                                <div class="cbh-form-group mb-0">
                                    <label for="connect_db_name" class="cbh-label">
                                        Database Name <span class="required">*</span>
                                    </label>
                                    <input type="text"
                                           id="connect_db_name"
                                           name="connect_db_name"
                                           value="{{ old('connect_db_name', old('tenant_db_mode') === 'connect' ? old('db_name') : '') }}"
                                           class="cbh-input {{ $errors->has('db_name') && old('tenant_db_mode') === 'connect' ? 'is-invalid' : '' }}"
                                           placeholder="e.g. existing_tenant_db">
                                    <p class="cbh-field-hint">Must match an existing database name on the host.</p>
                                    @if(old('tenant_db_mode') === 'connect')
                                        @error('db_name')
                                            <p class="cbh-field-error">{{ $message }}</p>
                                        @enderror
                                    @endif
                                </div>
                            </div>

                            {{-- Username & Password --}}
                            <div class="col-12 col-md-6">
                                <div class="cbh-form-group mb-0">
                                    <label for="connect_db_username" class="cbh-label">Database Username <span class="required">*</span></label>
                                    <input type="text"
                                           id="connect_db_username"
                                           name="connect_db_username"
                                           value="{{ old('connect_db_username', old('tenant_db_mode') === 'connect' ? old('db_username', 'root') : 'root') }}"
                                           class="cbh-input"
                                           placeholder="root">
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="cbh-form-group mb-0">
                                    <label for="connect_db_password" class="cbh-label">Database Password</label>
                                    <div class="input-group">
                                        <input type="password"
                                               id="connect_db_password"
                                               name="connect_db_password"
                                               value="{{ old('connect_db_password', old('tenant_db_mode') === 'connect' ? old('db_password') : '') }}"
                                               class="cbh-input"
                                               placeholder="Enter password (if required)">
                                        <button class="btn btn-outline-secondary"
                                                type="button"
                                                onclick="cbhTogglePassword('connect_db_password', this)"
                                                title="Toggle visibility">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Safe Migration Option --}}
                            <div class="col-12">
                                <div class="form-check p-3 rounded" style="background:var(--cbh-neutral-50);border:1px solid var(--cbh-neutral-200);">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="run_migrations"
                                           value="1"
                                           id="run_migrations"
                                           {{ old('run_migrations', '1') === '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="run_migrations">
                                        <strong style="color:var(--cbh-neutral-800);font-size:0.875rem;">Safely run tenant migrations if required tables are missing</strong>
                                        <div class="text-muted" style="font-size:0.78rem;">
                                            Will safely create missing <code>pages</code> and <code>media</code> tables without modifying or overwriting existing database tables or records.
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Hidden Unified Fields for Request Submission --}}
                    <input type="hidden" name="db_host" id="hidden_db_host" value="{{ old('db_host', '127.0.0.1') }}">
                    <input type="hidden" name="db_port" id="hidden_db_port" value="{{ old('db_port', 3306) }}">
                    <input type="hidden" name="db_name" id="hidden_db_name" value="{{ old('db_name') }}">
                    <input type="hidden" name="db_username" id="hidden_db_username" value="{{ old('db_username', 'root') }}">
                    <input type="hidden" name="db_password" id="hidden_db_password" value="{{ old('db_password') }}">

                    {{-- Test Connection Action & Feedback Bar --}}
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 pt-3 mt-4"
                         style="border-top:1px solid var(--cbh-neutral-200);">
                        <button type="button"
                                id="btnTestConnection"
                                class="cbh-btn cbh-btn-secondary cbh-btn-sm"
                                onclick="cbhRunTestConnection()">
                            <i class="bi bi-plug"></i>
                            Test Connection
                        </button>
                        <div id="testConnectionFeedback" class="flex-grow-1" style="font-size:0.84rem;"></div>
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
                <button type="submit" id="btnSubmitWebsite" class="cbh-btn cbh-btn-primary" style="justify-content:center;">
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

@push('scripts')
<script>
function cbhToggleTenantDbMode(mode) {
    const cardCreate = document.getElementById('cardModeCreate');
    const cardConnect = document.getElementById('cardModeConnect');
    const fieldsCreate = document.getElementById('fieldsCreateDatabase');
    const fieldsConnect = document.getElementById('fieldsConnectDatabase');
    const feedback = document.getElementById('testConnectionFeedback');

    if (feedback) feedback.innerHTML = '';

    if (mode === 'create') {
        document.getElementById('mode_create').checked = true;
        cardCreate.style.borderColor = 'var(--cbh-brand-500)';
        cardCreate.style.background = 'var(--cbh-brand-50)';
        cardConnect.style.borderColor = 'var(--cbh-neutral-200)';
        cardConnect.style.background = '#ffffff';

        fieldsCreate.style.display = 'block';
        fieldsConnect.style.display = 'none';
    } else {
        document.getElementById('mode_connect').checked = true;
        cardConnect.style.borderColor = 'var(--cbh-brand-500)';
        cardConnect.style.background = 'var(--cbh-brand-50)';
        cardCreate.style.borderColor = 'var(--cbh-neutral-200)';
        cardCreate.style.background = '#ffffff';

        fieldsCreate.style.display = 'none';
        fieldsConnect.style.display = 'block';
    }
}

function cbhTogglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

function cbhSyncHiddenFields() {
    const mode = document.querySelector('input[name="tenant_db_mode"]:checked')?.value || 'create';
    const prefix = mode === 'create' ? 'create_' : 'connect_';

    document.getElementById('hidden_db_host').value = document.getElementById(prefix + 'db_host')?.value || '127.0.0.1';
    document.getElementById('hidden_db_port').value = document.getElementById(prefix + 'db_port')?.value || '3306';
    document.getElementById('hidden_db_name').value = document.getElementById(prefix + 'db_name')?.value || '';
    document.getElementById('hidden_db_username').value = document.getElementById(prefix + 'db_username')?.value || 'root';
    document.getElementById('hidden_db_password').value = document.getElementById(prefix + 'db_password')?.value || '';
}

async function cbhRunTestConnection() {
    const mode = document.querySelector('input[name="tenant_db_mode"]:checked')?.value || 'create';
    const prefix = mode === 'create' ? 'create_' : 'connect_';
    const btn = document.getElementById('btnTestConnection');
    const feedback = document.getElementById('testConnectionFeedback');

    const host = document.getElementById(prefix + 'db_host')?.value.trim();
    const port = document.getElementById(prefix + 'db_port')?.value.trim();
    const database = document.getElementById(prefix + 'db_name')?.value.trim();
    const username = document.getElementById(prefix + 'db_username')?.value.trim();
    const password = document.getElementById(prefix + 'db_password')?.value;

    if (mode === 'connect' && !database) {
        feedback.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Database Name is required when connecting an existing database.</span>';
        return;
    }

    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Testing...';
    feedback.innerHTML = '<span class="text-muted"><i class="bi bi-arrow-repeat spin me-1"></i> Testing connection to server...</span>';

    try {
        const response = await fetch("{{ route('websites.test_database') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                mode: mode,
                host: host,
                port: port ? parseInt(port, 10) : 3306,
                database: database || null,
                username: username,
                password: password
            })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            feedback.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> ' + (data.message || 'Connection successful!') + '</span>';
        } else {
            feedback.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i> ' + (data.message || 'Connection failed.') + '</span>';
        }
    } catch (err) {
        feedback.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i> Could not contact test service: ' + err.message + '</span>';
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('createWebsiteForm');
    if (form) {
        form.addEventListener('submit', function () {
            cbhSyncHiddenFields();

            const submitBtn = document.getElementById('btnSubmitWebsite');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Provisioning Website...';
            }
        });
    }
});
</script>
@endpush
@endsection
