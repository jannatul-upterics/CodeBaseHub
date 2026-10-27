@extends('layouts.dashboard')

@section('title', 'Theme Configuration — ' . $website->website_name)
@section('breadcrumb', 'Theme Configuration')

@section('content')
@include('websites._header', ['activeTab' => 'theme'])

<div class="row g-4">
    {{-- Left Column: Active Theme & Switching --}}
    <div class="col-12 col-lg-5">
        <div class="cbh-card mb-4">
            <div class="cbh-card-header">
                <div>
                    <h2 class="cbh-card-title">Active Theme</h2>
                    <p class="cbh-card-subtitle">Visual template powering this website's layout</p>
                </div>
            </div>
            <div class="cbh-card-body p-4">
                @if ($activeTheme)
                    <div class="p-3 rounded border mb-3 bg-light">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h3 class="fw-bold mb-0 text-dark" style="font-size:1.1rem;">{{ $activeTheme->theme_name }}</h3>
                            <span class="cbh-badge cbh-badge-success">
                                <i class="bi bi-check-circle-fill me-1" style="font-size:0.4rem;"></i>
                                Active
                            </span>
                        </div>
                        <div class="font-monospace text-muted small mb-2">v{{ $activeTheme->version ?? '1.0.0' }} &middot; slug: {{ $activeTheme->slug }}</div>
                        <p class="small text-secondary mb-0">{{ $activeTheme->description ?? 'No theme description provided.' }}</p>
                    </div>
                @else
                    <div class="alert alert-warning mb-3">No active theme currently assigned to this website.</div>
                @endif

                {{-- Switch Theme Form --}}
                <form method="POST" action="{{ route('websites.theme.set_active', $website) }}">
                    @csrf
                    <label for="theme_id" class="cbh-form-label">Switch Active Theme</label>
                    <div class="d-flex gap-2">
                        <select id="theme_id" name="theme_id" class="cbh-select flex-grow-1" required>
                            @foreach ($allThemes as $t)
                                <option value="{{ $t->id }}" {{ $activeTheme && $activeTheme->id === $t->id ? 'selected' : '' }}>
                                    {{ $t->theme_name }} (v{{ $t->version ?? '1.0' }})
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="cbh-btn cbh-btn-secondary" style="white-space:nowrap;">
                            Apply
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Stored Custom Theme Settings Readout --}}
        <div class="cbh-card">
            <div class="cbh-card-header">
                <h3 class="cbh-card-title">Stored Theme Parameters</h3>
            </div>
            @if ($themeSettings->count() > 0)
                <div class="cbh-table-responsive">
                    <table class="cbh-table">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($themeSettings as $ts)
                                <tr>
                                    <td class="font-monospace text-secondary" style="font-size:0.8rem;">{{ $ts->setting_key }}</td>
                                    <td>
                                        @if (str_starts_with((string)$ts->setting_value, '#'))
                                            <span class="d-inline-flex align-items-center gap-1 font-monospace" style="font-size:0.82rem;">
                                                <span style="display:inline-block;width:14px;height:14px;border-radius:3px;background-color:{{ $ts->setting_value }};border:1px solid #ccc;"></span>
                                                {{ $ts->setting_value }}
                                            </span>
                                        @else
                                            <span class="font-monospace" style="font-size:0.82rem;">{{ Str::limit($ts->setting_value, 40) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-3 text-muted small text-center">No theme overrides stored yet.</div>
            @endif
        </div>
    </div>

    {{-- Right Column: Theme Settings Customizer Form --}}
    <div class="col-12 col-lg-7">
        <div class="cbh-card">
            <div class="cbh-card-header">
                <div>
                    <h2 class="cbh-card-title">Design &amp; Styling Settings</h2>
                    <p class="cbh-card-subtitle">Tune brand colors, typography, and container layouts</p>
                </div>
            </div>

            @php
                $settingsMap = $themeSettings->pluck('setting_value', 'setting_key')->toArray();
            @endphp

            <form method="POST" action="{{ route('websites.theme.settings.update', $website) }}">
                @csrf
                <input type="hidden" name="theme_id" value="{{ $activeTheme?->id ?? ($allThemes->first()?->id ?? 1) }}">

                <div class="cbh-card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="primary_color" class="cbh-form-label">Primary Brand Color</label>
                            <div class="input-group">
                                <input type="color"
                                       class="form-control form-control-color"
                                       id="primary_color_picker"
                                       value="{{ old('primary_color', $settingsMap['primary_color'] ?? '#2563eb') }}"
                                       title="Choose color"
                                       onchange="document.getElementById('primary_color').value = this.value">
                                <input type="text"
                                       id="primary_color"
                                       name="primary_color"
                                       class="cbh-input font-monospace @error('primary_color') is-invalid @enderror"
                                       value="{{ old('primary_color', $settingsMap['primary_color'] ?? '#2563eb') }}"
                                       placeholder="#2563eb">
                            </div>
                            @error('primary_color')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="accent_color" class="cbh-form-label">Accent Highlight Color</label>
                            <div class="input-group">
                                <input type="color"
                                       class="form-control form-control-color"
                                       id="accent_color_picker"
                                       value="{{ old('accent_color', $settingsMap['accent_color'] ?? '#10b981') }}"
                                       title="Choose color"
                                       onchange="document.getElementById('accent_color').value = this.value">
                                <input type="text"
                                       id="accent_color"
                                       name="accent_color"
                                       class="cbh-input font-monospace @error('accent_color') is-invalid @enderror"
                                       value="{{ old('accent_color', $settingsMap['accent_color'] ?? '#10b981') }}"
                                       placeholder="#10b981">
                            </div>
                            @error('accent_color')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="font_family" class="cbh-form-label">Typography Font Family</label>
                            <select id="font_family" name="font_family" class="cbh-select @error('font_family') is-invalid @enderror">
                                @foreach (['Instrument Sans', 'Inter', 'Roboto', 'Plus Jakarta Sans', 'Open Sans', 'Merriweather', 'Playfair Display'] as $font)
                                    <option value="{{ $font }}" {{ old('font_family', $settingsMap['font_family'] ?? 'Instrument Sans') === $font ? 'selected' : '' }}>
                                        {{ $font }}
                                    </option>
                                @endforeach
                            </select>
                            @error('font_family')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="layout_mode" class="cbh-form-label">Page Container Layout</label>
                            <select id="layout_mode" name="layout_mode" class="cbh-select @error('layout_mode') is-invalid @enderror">
                                <option value="boxed" {{ old('layout_mode', $settingsMap['layout_mode'] ?? 'boxed') === 'boxed' ? 'selected' : '' }}>Boxed (1200px Centered)</option>
                                <option value="fluid" {{ old('layout_mode', $settingsMap['layout_mode'] ?? '') === 'fluid' ? 'selected' : '' }}>Fluid (Full Width 100%)</option>
                                <option value="narrow" {{ old('layout_mode', $settingsMap['layout_mode'] ?? '') === 'narrow' ? 'selected' : '' }}>Narrow Editorial (960px)</option>
                            </select>
                            @error('layout_mode')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="navbar_style" class="cbh-form-label">Navigation Bar Presentation</label>
                            <select id="navbar_style" name="navbar_style" class="cbh-select @error('navbar_style') is-invalid @enderror">
                                <option value="sticky" {{ old('navbar_style', $settingsMap['navbar_style'] ?? 'sticky') === 'sticky' ? 'selected' : '' }}>Sticky (Fixed at top when scrolling)</option>
                                <option value="standard" {{ old('navbar_style', $settingsMap['navbar_style'] ?? '') === 'standard' ? 'selected' : '' }}>Standard Static Navbar</option>
                                <option value="transparent" {{ old('navbar_style', $settingsMap['navbar_style'] ?? '') === 'transparent' ? 'selected' : '' }}>Transparent Overlay Hero</option>
                            </select>
                            @error('navbar_style')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="custom_css" class="cbh-form-label">Custom CSS Overrides</label>
                            <textarea id="custom_css"
                                      name="custom_css"
                                      class="cbh-input font-monospace @error('custom_css') is-invalid @enderror"
                                      rows="4"
                                      placeholder="/* Add targeted CSS styles for this website */">{{ old('custom_css', $settingsMap['custom_css'] ?? '') }}</textarea>
                            @error('custom_css')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="cbh-card-footer bg-light border-top p-3 d-flex justify-content-end">
                    <button type="submit" class="cbh-btn cbh-btn-primary">
                        <i class="bi bi-check-lg"></i>
                        Save Theme Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
