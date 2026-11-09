@extends('layouts.dashboard')

@section('title', 'Theme Configuration — ' . $website->website_name)
@section('breadcrumb', 'Theme Configuration')

@section('content')
@include('websites._header', ['activeTab' => 'theme'])

@php
    $settingsMap = $themeSettings->pluck('setting_value', 'setting_key')->toArray();
    $primaryColor = old('primary_color', $settingsMap['primary_color'] ?? $defaultAppearance['primary_color'] ?? '#7c3aed');
    $accentColor = old('accent_color', $settingsMap['accent_color'] ?? $defaultAppearance['accent_color'] ?? '#a855f7');
    $backgroundColor = old('background_color', $settingsMap['background_color'] ?? $defaultAppearance['background_color'] ?? '#070313');
    $textColor = old('text_color', $settingsMap['text_color'] ?? $defaultAppearance['text_color'] ?? '#ffffff');
    $fontFamily = old('font_family', $settingsMap['font_family'] ?? $defaultAppearance['font_family'] ?? 'Plus Jakarta Sans');
    $logo = old('logo', $settingsMap['logo'] ?? $defaultAppearance['logo'] ?? 'images/logo2.webp');
    $buttonStyle = old('button_style', $settingsMap['button_style'] ?? $defaultAppearance['button_style'] ?? 'pill');
    $layoutMode = old('layout_mode', $settingsMap['layout_mode'] ?? $defaultAppearance['layout_mode'] ?? 'boxed');
    $navbarStyle = old('navbar_style', $settingsMap['navbar_style'] ?? $defaultAppearance['navbar_style'] ?? 'sticky');
    $customCss = old('custom_css', $settingsMap['custom_css'] ?? $defaultAppearance['custom_css'] ?? '');
@endphp

@if ($isVocabot)
    <div class="cbh-card mb-4" style="border-left: 4px solid #7c3aed; background: linear-gradient(90deg, rgba(124, 58, 237, 0.08) 0%, rgba(168, 85, 247, 0.03) 100%);">
        <div class="cbh-card-body d-flex align-items-center justify-content-between flex-wrap gap-3 p-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 44px; height: 44px; border-radius: 8px; background: #7c3aed; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.25rem;">
                    <i class="bi bi-robot"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h2 style="font-size: 1rem; font-weight: 700; color: var(--cbh-neutral-800); margin-bottom: 0;">Vocabot Theme Management</h2>
                        <span class="cbh-badge cbh-badge-primary" style="background:#7c3aed;border-color:#7c3aed;">Vocabot Website</span>
                    </div>
                    <p class="text-secondary small mb-0 mt-1">Configure brand colors, typography, logo, and button styling specifically for Vocabot. Settings are isolated to this website.</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ $isVocabot ? 'http://127.0.0.1:8001' : (str_starts_with($website->domain, 'http') ? $website->domain : 'https://'.$website->domain) }}" target="_blank" rel="noopener noreferrer" class="cbh-btn cbh-btn-secondary cbh-btn-sm">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Preview Vocabot Website
                </a>
            </div>
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="cbh-alert cbh-alert-danger mb-4">
        <i class="bi bi-exclamation-triangle-fill cbh-alert-icon"></i>
        <div>
            <strong>Validation Errors Encountered:</strong>
            <ul class="mb-0 mt-1 ps-3" style="font-size:0.875rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button class="cbh-alert-close" onclick="this.closest('.cbh-alert').remove()" aria-label="Dismiss">
            <i class="bi bi-x-lg" style="font-size:0.75rem;"></i>
        </button>
    </div>
@endif

{{-- Notification banner for client-side reset --}}
<div id="reset-alert-banner" class="cbh-alert cbh-alert-info mb-4 d-none" role="alert">
    <i class="bi bi-info-circle-fill cbh-alert-icon"></i>
    <div class="flex-grow-1">
        <strong>Default values loaded into form.</strong> Remember to click <strong>Save Changes</strong> to apply and persist these settings.
    </div>
    <button class="cbh-alert-close" onclick="document.getElementById('reset-alert-banner').classList.add('d-none')" aria-label="Dismiss">
        <i class="bi bi-x-lg" style="font-size:0.75rem;"></i>
    </button>
</div>

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
                    <label for="theme_id_select" class="cbh-form-label">Switch Active Theme</label>
                    <div class="d-flex gap-2">
                        <select id="theme_id_select" name="theme_id" class="cbh-select flex-grow-1" required>
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
        <div class="cbh-card mb-4">
            <div class="cbh-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="cbh-card-title mb-0">Stored Theme Settings</h3>
                    <p class="cbh-card-subtitle mb-0">Currently saved configuration in database</p>
                </div>
                <button type="button" class="cbh-btn cbh-btn-ghost text-danger cbh-btn-sm" data-bs-toggle="modal" data-bs-target="#confirmResetModal" title="Reset stored database values to defaults">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset Stored
                </button>
            </div>
            @if ($themeSettings->count() > 0)
                <div class="cbh-table-responsive">
                    <table class="cbh-table">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Saved Value</th>
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
                                            <span class="font-monospace" style="font-size:0.82rem;">{{ Str::limit($ts->setting_value, 36) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-3 text-muted small text-center">No theme overrides stored yet. Default template styling is active.</div>
            @endif
        </div>
    </div>

    {{-- Right Column: Theme Settings Customizer Form --}}
    <div class="col-12 col-lg-7">
        <div class="cbh-card">
            <div class="cbh-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h2 class="cbh-card-title">Theme Settings Editor</h2>
                    <p class="cbh-card-subtitle">Update colors, typography, brand logo, and button styling</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="cbh-btn cbh-btn-secondary cbh-btn-sm" id="btnResetForm" onclick="resetFormToDefaults()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset to Default
                    </button>
                </div>
            </div>

            <form id="themeForm" method="POST" action="{{ route('websites.theme.settings.update', $website) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="theme_id" value="{{ $activeTheme?->id ?? ($allThemes->first()?->id ?? 1) }}">

                <div class="cbh-card-body p-4">
                    <div class="row g-3">

                        {{-- 1. Primary Color --}}
                        <div class="col-12 col-md-6">
                            <label for="primary_color" class="cbh-form-label">
                                Primary Color <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="color"
                                       class="form-control form-control-color"
                                       id="primary_color_picker"
                                       value="{{ $primaryColor }}"
                                       title="Choose primary color"
                                       oninput="syncColorInput('primary_color_picker', 'primary_color')">
                                <input type="text"
                                       id="primary_color"
                                       name="primary_color"
                                       class="cbh-input font-monospace @error('primary_color') is-invalid @enderror"
                                       value="{{ $primaryColor }}"
                                       placeholder="#7c3aed"
                                       oninput="syncColorPicker('primary_color', 'primary_color_picker')">
                            </div>
                            <div class="cbh-form-hint">Main brand and call-to-action color.</div>
                            @error('primary_color')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- 2. Secondary / Accent Color --}}
                        <div class="col-12 col-md-6">
                            <label for="accent_color" class="cbh-form-label">
                                Secondary / Accent Color <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="color"
                                       class="form-control form-control-color"
                                       id="accent_color_picker"
                                       value="{{ $accentColor }}"
                                       title="Choose secondary/accent color"
                                       oninput="syncColorInput('accent_color_picker', 'accent_color')">
                                <input type="text"
                                       id="accent_color"
                                       name="accent_color"
                                       class="cbh-input font-monospace @error('accent_color') is-invalid @enderror"
                                       value="{{ $accentColor }}"
                                       placeholder="#a855f7"
                                       oninput="syncColorPicker('accent_color', 'accent_color_picker')">
                            </div>
                            <div class="cbh-form-hint">Gradient end, highlight accents, and subtle borders.</div>
                            @error('accent_color')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- 3. Background Color --}}
                        <div class="col-12 col-md-6">
                            <label for="background_color" class="cbh-form-label">
                                Background Color <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="color"
                                       class="form-control form-control-color"
                                       id="background_color_picker"
                                       value="{{ $backgroundColor }}"
                                       title="Choose background color"
                                       oninput="syncColorInput('background_color_picker', 'background_color')">
                                <input type="text"
                                       id="background_color"
                                       name="background_color"
                                       class="cbh-input font-monospace @error('background_color') is-invalid @enderror"
                                       value="{{ $backgroundColor }}"
                                       placeholder="#070313"
                                       oninput="syncColorPicker('background_color', 'background_color_picker')">
                            </div>
                            <div class="cbh-form-hint">Page container &amp; body background.</div>
                            @error('background_color')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- 4. Text Color --}}
                        <div class="col-12 col-md-6">
                            <label for="text_color" class="cbh-form-label">
                                Text Color <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="color"
                                       class="form-control form-control-color"
                                       id="text_color_picker"
                                       value="{{ $textColor }}"
                                       title="Choose text color"
                                       oninput="syncColorInput('text_color_picker', 'text_color')">
                                <input type="text"
                                       id="text_color"
                                       name="text_color"
                                       class="cbh-input font-monospace @error('text_color') is-invalid @enderror"
                                       value="{{ $textColor }}"
                                       placeholder="#ffffff"
                                       oninput="syncColorPicker('text_color', 'text_color_picker')">
                            </div>
                            <div class="cbh-form-hint">Headings, typography, and body text.</div>
                            @error('text_color')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- 5. Typography Font Family --}}
                        <div class="col-12 col-md-6">
                            <label for="font_family" class="cbh-form-label">Typography Font Family</label>
                            <select id="font_family" name="font_family" class="cbh-select @error('font_family') is-invalid @enderror" onchange="updateLivePreview()">
                                @foreach (['Plus Jakarta Sans' => 'Plus Jakarta Sans (Vocabot Default)', 'Instrument Sans' => 'Instrument Sans', 'Inter' => 'Inter', 'Roboto' => 'Roboto', 'Outfit' => 'Outfit', 'Poppins' => 'Poppins', 'Open Sans' => 'Open Sans', 'Merriweather' => 'Merriweather', 'Playfair Display' => 'Playfair Display'] as $fontVal => $fontLabel)
                                    <option value="{{ $fontVal }}" {{ $fontFamily === $fontVal ? 'selected' : '' }}>
                                        {{ $fontLabel }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="cbh-form-hint">Applied to headers, navigation, and paragraphs.</div>
                            @error('font_family')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- 6. Button Styling --}}
                        <div class="col-12 col-md-6">
                            <label for="button_style" class="cbh-form-label">Button Styling</label>
                            <select id="button_style" name="button_style" class="cbh-select @error('button_style') is-invalid @enderror" onchange="updateLivePreview()">
                                <option value="pill" {{ $buttonStyle === 'pill' ? 'selected' : '' }}>Pill (Rounded 999px - Vocabot Default)</option>
                                <option value="rounded" {{ $buttonStyle === 'rounded' ? 'selected' : '' }}>Rounded (8px Corner Radius)</option>
                                <option value="square" {{ $buttonStyle === 'square' ? 'selected' : '' }}>Square (0px Sharp Edges)</option>
                            </select>
                            <div class="cbh-form-hint">Border radius shaping on primary and action buttons.</div>
                            @error('button_style')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- 7. Brand Logo --}}
                        <div class="col-12">
                            <label for="logo" class="cbh-form-label">Brand Logo</label>
                            <div class="row g-2 align-items-center">
                                <div class="col-auto">
                                    <div id="logo_thumbnail_container" style="width: 52px; height: 52px; border-radius: 8px; border: 1px solid var(--cbh-border); background: var(--cbh-neutral-100); display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 4px;">
                                        <img id="logo_preview_thumb"
                                             src="{{ str_starts_with($logo, 'http') ? $logo : asset($logo) }}"
                                             alt="Logo Preview"
                                             style="max-width: 100%; max-height: 100%; object-fit: contain;"
                                             onerror="this.style.display='none'; document.getElementById('logo_fallback_icon').style.display='block';">
                                        <i id="logo_fallback_icon" class="bi bi-image text-muted" style="font-size: 1.5rem; display: none;"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="input-group">
                                        <input type="text"
                                               id="logo"
                                               name="logo"
                                               class="cbh-input font-monospace @error('logo') is-invalid @enderror"
                                               value="{{ $logo }}"
                                               placeholder="images/logo2.webp"
                                               oninput="onLogoPathChanged(this.value)">
                                        @if ($mediaAssets->count() > 0)
                                            <button type="button" class="cbh-btn cbh-btn-secondary" data-bs-toggle="modal" data-bs-target="#mediaPickerModal" title="Select image from website media library">
                                                <i class="bi bi-images me-1"></i> Library
                                            </button>
                                        @endif
                                    </div>
                                    <div class="cbh-form-hint mt-1">Direct path, URL, or upload a new file below.</div>
                                </div>
                            </div>
                            @error('logo')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror

                            {{-- File Upload for Logo --}}
                            <div class="mt-2 p-2 rounded bg-light border">
                                <label for="logo_file" class="cbh-form-label mb-1" style="font-size: 0.8rem;">
                                    <i class="bi bi-cloud-arrow-up me-1"></i> Upload New Logo File
                                </label>
                                <input type="file"
                                       id="logo_file"
                                       name="logo_file"
                                       class="form-control form-control-sm @error('logo_file') is-invalid @enderror"
                                       accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif"
                                       onchange="handleLogoFileUpload(this)">
                                <div class="text-muted small mt-1" style="font-size: 0.75rem;">Supported formats: PNG, WEBP, SVG, JPG. Automatically saved to website media library.</div>
                                @error('logo_file')
                                    <div class="cbh-form-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Additional Layout Settings --}}
                        <div class="col-12 col-md-6">
                            <label for="layout_mode" class="cbh-form-label">Page Container Layout</label>
                            <select id="layout_mode" name="layout_mode" class="cbh-select @error('layout_mode') is-invalid @enderror">
                                <option value="boxed" {{ $layoutMode === 'boxed' ? 'selected' : '' }}>Boxed (1200px Centered)</option>
                                <option value="fluid" {{ $layoutMode === 'fluid' ? 'selected' : '' }}>Fluid (Full Width 100%)</option>
                                <option value="narrow" {{ $layoutMode === 'narrow' ? 'selected' : '' }}>Narrow Editorial (960px)</option>
                            </select>
                            @error('layout_mode')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="navbar_style" class="cbh-form-label">Navigation Bar Presentation</label>
                            <select id="navbar_style" name="navbar_style" class="cbh-select @error('navbar_style') is-invalid @enderror">
                                <option value="sticky" {{ $navbarStyle === 'sticky' ? 'selected' : '' }}>Sticky (Fixed at top when scrolling)</option>
                                <option value="standard" {{ $navbarStyle === 'standard' ? 'selected' : '' }}>Standard Static Navbar</option>
                                <option value="transparent" {{ $navbarStyle === 'transparent' ? 'selected' : '' }}>Transparent Overlay Hero</option>
                            </select>
                            @error('navbar_style')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Custom CSS --}}
                        <div class="col-12">
                            <label for="custom_css" class="cbh-form-label">Custom CSS Overrides</label>
                            <textarea id="custom_css"
                                      name="custom_css"
                                      class="cbh-input font-monospace @error('custom_css') is-invalid @enderror"
                                      rows="3"
                                      placeholder="/* Add targeted CSS styles for this website */">{{ $customCss }}</textarea>
                            @error('custom_css')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>

                <div class="cbh-card-footer bg-light border-top p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <button type="button" class="cbh-btn cbh-btn-secondary" onclick="resetFormToDefaults()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset to Default
                    </button>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small d-none d-sm-inline">Changes only take effect after saving</span>
                        <button type="submit" class="cbh-btn cbh-btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Live Visual Preview Card --}}
        <div class="cbh-card mt-4">
            <div class="cbh-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="cbh-card-title mb-0">Live Theme Preview</h3>
                    <p class="cbh-card-subtitle mb-0">Interactive simulation of brand colors, logo, typography, and buttons</p>
                </div>
                <span class="cbh-badge cbh-badge-neutral">
                    <i class="bi bi-eye me-1"></i> Real-time preview
                </span>
            </div>
            <div class="cbh-card-body p-4">
                <div id="theme_preview_container"
                     class="p-4 rounded border shadow-sm transition-all"
                     style="background-color: {{ $backgroundColor }}; color: {{ $textColor }}; font-family: '{{ $fontFamily }}', sans-serif;">
                    
                    {{-- Preview Navbar --}}
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom border-secondary border-opacity-25">
                        <div class="d-flex align-items-center gap-2">
                            <img id="preview_logo_img"
                                 src="{{ str_starts_with($logo, 'http') ? $logo : asset($logo) }}"
                                 alt="Logo"
                                 style="max-height: 28px; width: auto; object-fit: contain;"
                                 onerror="this.style.display='none'; document.getElementById('preview_logo_fallback').style.display='inline-flex';">
                            <span id="preview_logo_fallback"
                                  style="display:none;width:32px;height:32px;border-radius:6px;background:{{ $primaryColor }};align-items:center;justify-content:center;color:#fff;font-weight:bold;">
                                V
                            </span>
                            <span class="fw-bold" id="preview_site_name" style="font-size:1.15rem;color:{{ $textColor }};">
                                {{ $website->website_name }}
                            </span>
                        </div>
                        <span id="preview_accent_badge" class="badge" style="background-color:{{ $accentColor }};color:#fff;font-size:0.75rem;">
                            Live Simulation
                        </span>
                    </div>

                    {{-- Preview Content --}}
                    <h4 id="preview_heading" class="fw-bold mb-2" style="color:{{ $textColor }};">24/7 AI Voice Receptionist</h4>
                    <p class="mb-4 small text-secondary" style="max-width:500px;opacity:0.85;">
                        Automate inbound calls, schedule reservations, and deliver natural conversational intelligence with Vocabot.
                    </p>

                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <button type="button"
                                id="preview_primary_btn"
                                class="btn px-4 py-2 fw-semibold"
                                style="background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $accentColor }} 100%); color: #fff; border: 1px solid rgba(255,255,255,0.15); border-radius: {{ $buttonStyle === 'pill' ? '999px' : ($buttonStyle === 'rounded' ? '8px' : '0px') }};">
                            Book a Demo
                        </button>
                        <button type="button"
                                id="preview_secondary_btn"
                                class="btn px-4 py-2 fw-semibold"
                                style="background: transparent; color: {{ $textColor }}; border: 1px solid {{ $accentColor }}; border-radius: {{ $buttonStyle === 'pill' ? '999px' : ($buttonStyle === 'rounded' ? '8px' : '0px') }};">
                            Learn More
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Media Picker Modal (when media assets exist) --}}
@if ($mediaAssets->count() > 0)
    <div class="modal fade" id="mediaPickerModal" tabindex="-1" aria-labelledby="mediaPickerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius:12px;border-color:var(--cbh-border);">
                <div class="modal-header" style="border-bottom:1px solid var(--cbh-border);padding:1.25rem 1.5rem 1rem;">
                    <h5 class="modal-title" id="mediaPickerModalLabel" style="font-size:1rem;font-weight:700;">
                        <i class="bi bi-images me-1 text-primary"></i>
                        Select Logo from Media Library
                    </h5>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding:1.25rem 1.5rem;">
                    <p class="text-muted small mb-3">Click on any image asset to select it as the brand logo for this theme.</p>
                    <div class="row g-3">
                        @foreach ($mediaAssets as $mediaItem)
                            <div class="col-4 col-sm-3 col-md-2">
                                <div class="media-picker-card p-1 rounded border text-center"
                                     style="cursor: pointer; transition: all 0.15s ease;"
                                     onclick="selectMediaLogo('{{ $mediaItem->file_path }}', '{{ route('media.file', $mediaItem) }}')">
                                    <div style="height: 70px; display: flex; align-items: center; justify-content: center; background: var(--cbh-neutral-50); border-radius: 6px; overflow: hidden;">
                                        <img src="{{ route('media.file', $mediaItem) }}" alt="{{ $mediaItem->file_name }}" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                    </div>
                                    <div class="small font-monospace text-truncate mt-1" style="font-size: 0.7rem;" title="{{ $mediaItem->file_name }}">
                                        {{ $mediaItem->file_name }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--cbh-border);padding:0.75rem 1.5rem;">
                    <button type="button" class="cbh-btn cbh-btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Reset to Default Confirmation Modal (Server-side reset) --}}
<div class="modal fade" id="confirmResetModal" tabindex="-1" aria-labelledby="confirmResetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px;border-color:var(--cbh-border);">
            <div class="modal-header" style="border-bottom:1px solid var(--cbh-border);padding:1.25rem 1.5rem 1rem;">
                <h5 class="modal-title" id="confirmResetModalLabel" style="font-size:1rem;font-weight:700;color:#b91c1c;display:flex;align-items:center;gap:0.5rem;">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    Reset Stored Theme Settings
                </h5>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('websites.theme.settings.reset', $website) }}">
                @csrf
                <input type="hidden" name="theme_id" value="{{ $activeTheme?->id ?? ($allThemes->first()?->id ?? 1) }}">
                <div class="modal-body" style="padding:1rem 1.5rem;">
                    <p style="margin:0 0 0.5rem;font-size:0.9rem;">
                        Are you sure you want to reset all stored theme settings for <strong>{{ $website->website_name }}</strong> to default values?
                    </p>
                    <p style="font-size:0.82rem;color:var(--cbh-neutral-500);margin:0;">
                        This will overwrite stored custom colors, typography, logo, and button styles in the database with default {{ $isVocabot ? 'Vocabot' : 'theme' }} values.
                    </p>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--cbh-border);padding:1rem 1.5rem;gap:0.5rem;">
                    <button type="button" class="cbh-btn cbh-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="cbh-btn cbh-btn-primary" style="background:#dc2626;border-color:#dc2626;">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>
                        Confirm Reset
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const defaultSettings = @json($defaultAppearance);

    function syncColorInput(pickerId, textId) {
        const picker = document.getElementById(pickerId);
        const text = document.getElementById(textId);
        if (picker && text) {
            text.value = picker.value;
            updateLivePreview();
        }
    }

    function syncColorPicker(textId, pickerId) {
        const text = document.getElementById(textId);
        const picker = document.getElementById(pickerId);
        if (text && picker && /^#([a-fA-F0-9]{3}){1,2}$/.test(text.value)) {
            picker.value = text.value;
            updateLivePreview();
        }
    }

    function onLogoPathChanged(value) {
        const thumb = document.getElementById('logo_preview_thumb');
        const fallback = document.getElementById('logo_fallback_icon');
        const previewImg = document.getElementById('preview_logo_img');
        const previewFallback = document.getElementById('preview_logo_fallback');

        if (value && value.trim() !== '') {
            const url = value.startsWith('http') || value.startsWith('/') ? value : '{{ asset('') }}' + value;
            if (thumb) {
                thumb.src = url;
                thumb.style.display = 'block';
                if (fallback) fallback.style.display = 'none';
            }
            if (previewImg) {
                previewImg.src = url;
                previewImg.style.display = 'inline-block';
                if (previewFallback) previewFallback.style.display = 'none';
            }
        }
    }

    function handleLogoFileUpload(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const thumb = document.getElementById('logo_preview_thumb');
                const fallback = document.getElementById('logo_fallback_icon');
                const previewImg = document.getElementById('preview_logo_img');
                const previewFallback = document.getElementById('preview_logo_fallback');

                if (thumb) {
                    thumb.src = e.target.result;
                    thumb.style.display = 'block';
                    if (fallback) fallback.style.display = 'none';
                }
                if (previewImg) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'inline-block';
                    if (previewFallback) previewFallback.style.display = 'none';
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function selectMediaLogo(filePath, fileUrl) {
        const logoInput = document.getElementById('logo');
        if (logoInput) {
            logoInput.value = filePath;
            onLogoPathChanged(fileUrl);
        }
        const modal = bootstrap.Modal.getInstance(document.getElementById('mediaPickerModal'));
        if (modal) {
            modal.hide();
        }
    }

    function resetFormToDefaults() {
        if (!defaultSettings) return;

        // Set inputs to defaults
        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el && val !== undefined) el.value = val;
        };

        setVal('primary_color', defaultSettings.primary_color || '#7c3aed');
        setVal('primary_color_picker', defaultSettings.primary_color || '#7c3aed');

        setVal('accent_color', defaultSettings.accent_color || '#a855f7');
        setVal('accent_color_picker', defaultSettings.accent_color || '#a855f7');

        setVal('background_color', defaultSettings.background_color || '#070313');
        setVal('background_color_picker', defaultSettings.background_color || '#070313');

        setVal('text_color', defaultSettings.text_color || '#ffffff');
        setVal('text_color_picker', defaultSettings.text_color || '#ffffff');

        setVal('font_family', defaultSettings.font_family || 'Plus Jakarta Sans');
        setVal('button_style', defaultSettings.button_style || 'pill');
        setVal('layout_mode', defaultSettings.layout_mode || 'boxed');
        setVal('navbar_style', defaultSettings.navbar_style || 'sticky');
        setVal('custom_css', defaultSettings.custom_css || '');
        setVal('logo', defaultSettings.logo || 'images/logo2.webp');

        // Clear file input
        const fileInput = document.getElementById('logo_file');
        if (fileInput) fileInput.value = '';

        onLogoPathChanged(defaultSettings.logo || 'images/logo2.webp');
        updateLivePreview();

        // Show notice banner
        const banner = document.getElementById('reset-alert-banner');
        if (banner) {
            banner.classList.remove('d-none');
            banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function updateLivePreview() {
        const primary = document.getElementById('primary_color')?.value || '#7c3aed';
        const accent = document.getElementById('accent_color')?.value || '#a855f7';
        const bg = document.getElementById('background_color')?.value || '#070313';
        const text = document.getElementById('text_color')?.value || '#ffffff';
        const font = document.getElementById('font_family')?.value || 'Plus Jakarta Sans';
        const btnStyle = document.getElementById('button_style')?.value || 'pill';

        const container = document.getElementById('theme_preview_container');
        if (container) {
            container.style.backgroundColor = bg;
            container.style.color = text;
            container.style.fontFamily = `'${font}', sans-serif`;
        }

        const heading = document.getElementById('preview_heading');
        if (heading) heading.style.color = text;

        const siteName = document.getElementById('preview_site_name');
        if (siteName) siteName.style.color = text;

        const badge = document.getElementById('preview_accent_badge');
        if (badge) badge.style.backgroundColor = accent;

        const radius = btnStyle === 'pill' ? '999px' : (btnStyle === 'rounded' ? '8px' : '0px');

        const primaryBtn = document.getElementById('preview_primary_btn');
        if (primaryBtn) {
            primaryBtn.style.background = `linear-gradient(135deg, ${primary} 0%, ${accent} 100%)`;
            primaryBtn.style.borderRadius = radius;
        }

        const secBtn = document.getElementById('preview_secondary_btn');
        if (secBtn) {
            secBtn.style.borderColor = accent;
            secBtn.style.color = text;
            secBtn.style.borderRadius = radius;
        }
    }
</script>
@endsection