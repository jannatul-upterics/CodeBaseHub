@extends('layouts.dashboard')

@section('title', 'Website Settings')
@section('breadcrumb', 'Settings')

@section('content')
<div class="cbh-page-header">
    <div>
        <h1 class="cbh-page-title">
            Settings
            <span class="cbh-badge cbh-badge-primary ms-1" style="vertical-align: middle;">Configuration</span>
        </h1>
        <p class="cbh-page-subtitle">Site-specific parameters, analytics tracking IDs, notification targets, and key-value records.</p>
    </div>
</div>

{{-- Filter Toolbar --}}
<div class="cbh-card mb-4">
    <div class="cbh-card-body p-3">
        <form method="GET" action="{{ route('settings.index') }}" class="cbh-toolbar">
            <div class="cbh-search-wrap flex-grow-1" style="min-width: 220px;">
                <i class="bi bi-search cbh-search-icon"></i>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       class="cbh-input cbh-search-input"
                       placeholder="Search setting key or value…"
                       aria-label="Search settings">
            </div>

            <select name="website_id" class="cbh-select" style="min-width: 200px; max-width: 260px;" onchange="this.form.submit()">
                <option value="">All Websites</option>
                @foreach ($websites as $w)
                    <option value="{{ $w->id }}" {{ (string)$websiteId === (string)$w->id ? 'selected' : '' }}>
                        {{ $w->website_name }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="cbh-btn cbh-btn-primary">
                <i class="bi bi-search"></i>
                Filter
            </button>

            @if ($search !== '' || $websiteId !== '')
                <a href="{{ route('settings.index') }}" class="cbh-btn cbh-btn-secondary">
                    <i class="bi bi-x-circle"></i>
                    Clear
                </a>
            @endif
        </form>
    </div>
</div>

<div class="cbh-card">
    <div class="cbh-card-header">
        <div>
            <h2 class="cbh-card-title">Configuration Parameters</h2>
            <p class="cbh-card-subtitle">
                Showing {{ $settings->firstItem() ?? 0 }}–{{ $settings->lastItem() ?? 0 }} of {{ $settings->total() }} {{ Str::plural('setting', $settings->total()) }}
            </p>
        </div>
    </div>

    <div class="cbh-table-responsive">
        <table class="cbh-table">
            <thead>
                <tr>
                    <th>Website Property</th>
                    <th>Setting Key</th>
                    <th>Configured Value</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($settings as $setting)
                    <tr>
                        <td>
                            @if ($setting->website)
                                <a href="{{ route('websites.show', $setting->website) }}" class="fw-semibold text-decoration-none" style="color: var(--cbh-brand-600);">
                                    {{ $setting->website->website_name }}
                                </a>
                            @else
                                <span class="text-muted">Global / Unlinked</span>
                            @endif
                        </td>
                        <td>
                            <code class="text-primary fw-bold" style="font-size: 0.84rem;">{{ $setting->setting_key }}</code>
                        </td>
                        <td>
                            <span class="font-monospace text-dark text-break" style="font-size: 0.82rem;">
                                {{ Str::limit($setting->setting_value, 70) }}
                            </span>
                        </td>
                        <td>
                            <span class="text-muted" style="font-size: 0.8rem;">
                                {{ $setting->updated_at?->diffForHumans() ?? 'N/A' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <x-empty-state
                                icon="bi-sliders"
                                title="No settings found"
                                description="No key-value settings match the filter criteria."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($settings->hasPages())
        <div class="cbh-card-footer">
            {{ $settings->links() }}
        </div>
    @endif
</div>
@endsection
