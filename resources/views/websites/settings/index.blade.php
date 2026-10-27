@extends('layouts.dashboard')

@section('title', 'Site Settings — ' . $website->website_name)
@section('breadcrumb', 'Site Settings')

@section('content')
@include('websites._header', ['activeTab' => 'settings'])

{{-- Add Setting & Quick Presets Row --}}
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-7">
        <div class="cbh-card">
            <div class="cbh-card-header">
                <div>
                    <h2 class="cbh-card-title">Add Website Setting</h2>
                    <p class="cbh-card-subtitle">Key/value configurations and environment variables for this website</p>
                </div>
            </div>
            <form method="POST" action="{{ route('websites.settings.store', $website) }}" novalidate>
                @csrf
                <div class="cbh-card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="setting_key" class="cbh-form-label">
                                Setting Key <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   id="setting_key"
                                   name="setting_key"
                                   value="{{ old('setting_key') }}"
                                   class="cbh-input font-monospace @error('setting_key') is-invalid @enderror"
                                   placeholder="e.g. contact_email"
                                   required>
                            <div class="form-text" style="font-size:0.75rem;">Letters, numbers, dots, hyphens, and underscores only.</div>
                            @error('setting_key')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="preset_picker" class="cbh-form-label">Or Choose Standard Preset</label>
                            <select id="preset_picker" class="cbh-select" onchange="if(this.value){document.getElementById('setting_key').value=this.value;}">
                                <option value="">Select a common preset...</option>
                                @foreach ($presetKeys as $pkey => $plabel)
                                    <option value="{{ $pkey }}">{{ $plabel }} ({{ $pkey }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="setting_value" class="cbh-form-label">
                                Setting Value <span class="text-danger">*</span>
                            </label>
                            <textarea id="setting_value"
                                      name="setting_value"
                                      class="cbh-input font-monospace @error('setting_value') is-invalid @enderror"
                                      rows="2"
                                      placeholder="Value string, JSON payload, or config text..."
                                      required>{{ old('setting_value') }}</textarea>
                            @error('setting_value')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="cbh-card-footer bg-light border-top p-3 d-flex justify-content-end">
                    <button type="submit" class="cbh-btn cbh-btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        Save Setting
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Presets Quick Guide --}}
    <div class="col-12 col-lg-5">
        <div class="cbh-card h-100">
            <div class="cbh-card-header">
                <h3 class="cbh-card-title">Configuration Guidelines</h3>
            </div>
            <div class="cbh-card-body p-4" style="font-size:0.835rem;">
                <p class="text-secondary mb-3">Settings configured here are loaded in website runtime contexts and templates. Recommended keys include:</p>
                <div class="list-group list-group-flush mb-0" style="font-size:0.8rem;">
                    <div class="list-group-item px-0 py-1 bg-transparent d-flex justify-content-between">
                        <code class="text-primary">site_name</code>
                        <span class="text-muted">Display brand name</span>
                    </div>
                    <div class="list-group-item px-0 py-1 bg-transparent d-flex justify-content-between">
                        <code class="text-primary">contact_email</code>
                        <span class="text-muted">Inbound lead email</span>
                    </div>
                    <div class="list-group-item px-0 py-1 bg-transparent d-flex justify-content-between">
                        <code class="text-primary">google_analytics_id</code>
                        <span class="text-muted">G-XXXXX telemetry ID</span>
                    </div>
                    <div class="list-group-item px-0 py-1 bg-transparent d-flex justify-content-between">
                        <code class="text-primary">timezone</code>
                        <span class="text-muted">e.g. UTC, America/New_York</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Current Settings Table --}}
<div class="cbh-card">
    <div class="cbh-card-header">
        <div>
            <h2 class="cbh-card-title">Stored Settings</h2>
            <p class="cbh-card-subtitle">{{ $settings->count() }} {{ Str::plural('setting', $settings->count()) }} configured for {{ $website->website_name }}</p>
        </div>
        @if ($settings->count() > 0)
            <form method="GET" action="{{ route('websites.settings.index', $website) }}" class="cbh-toolbar">
                <div class="cbh-search-wrap">
                    <i class="bi bi-search cbh-search-icon"></i>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           class="cbh-input cbh-search-input"
                           placeholder="Filter settings…"
                           style="min-width:180px;">
                </div>
                @if ($search !== '')
                    <a href="{{ route('websites.settings.index', $website) }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm">
                        Clear
                    </a>
                @endif
            </form>
        @endif
    </div>

    <div class="cbh-table-responsive">
        <table class="cbh-table">
            <thead>
                <tr>
                    <th style="min-width: 200px;">Key</th>
                    <th>Value</th>
                    <th>Last Updated</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($settings as $s)
                    <tr>
                        <td>
                            <code class="fw-bold text-primary font-monospace" style="font-size:0.86rem;">
                                {{ $s->setting_key }}
                            </code>
                        </td>
                        <td>
                            <div class="font-monospace text-dark text-break" style="font-size:0.82rem; max-width: 480px;">
                                {{ $s->setting_value }}
                            </div>
                        </td>
                        <td class="text-muted small" style="font-size:0.8rem;">
                            {{ $s->updated_at?->diffForHumans() ?? '—' }}
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <button type="button"
                                        class="cbh-btn cbh-btn-secondary cbh-btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editSettingModal-{{ $s->id }}"
                                        title="Edit Setting">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button"
                                        class="cbh-btn cbh-btn-danger cbh-btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteSettingModal-{{ $s->id }}"
                                        title="Delete Setting">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>

                            {{-- Edit Setting Modal --}}
                            <div class="modal fade" id="editSettingModal-{{ $s->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content" style="text-align:left; border-radius:12px;">
                                        <div class="modal-header border-bottom">
                                            <h5 class="modal-title fw-bold">Edit Setting: {{ $s->setting_key }}</h5>
                                            <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="{{ route('websites.settings.update', [$website, $s]) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body p-4">
                                                <div class="mb-3">
                                                    <label class="cbh-form-label">Key Name</label>
                                                    <input type="text" class="cbh-input font-monospace bg-light" value="{{ $s->setting_key }}" readonly disabled>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="val-{{ $s->id }}" class="cbh-form-label">Setting Value <span class="text-danger">*</span></label>
                                                    <textarea id="val-{{ $s->id }}" name="setting_value" class="cbh-input font-monospace" rows="3" required>{{ old('setting_value', $s->setting_value) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top">
                                                <button type="button" class="cbh-btn cbh-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="cbh-btn cbh-btn-primary">
                                                    <i class="bi bi-check-lg"></i>
                                                    Update Value
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- Delete Setting Modal --}}
                            <div class="modal fade" id="deleteSettingModal-{{ $s->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content" style="text-align:left; border-radius:12px;">
                                        <div class="modal-header border-bottom">
                                            <h5 class="modal-title fw-bold text-danger">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                Delete Setting
                                            </h5>
                                            <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="{{ route('websites.settings.destroy', [$website, $s]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <div class="modal-body py-3">
                                                Are you sure you want to remove setting <code>{{ $s->setting_key }}</code>?
                                            </div>
                                            <div class="modal-footer border-top">
                                                <button type="button" class="cbh-btn cbh-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="cbh-btn cbh-btn-primary" style="background:#dc2626;border-color:#dc2626;">
                                                    <i class="bi bi-trash"></i>
                                                    Delete Setting
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
                        <td colspan="4">
                            <x-empty-state
                                icon="bi-sliders"
                                title="No custom settings stored"
                                description="Configure brand details, tracking IDs, or contact variables for this website."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
