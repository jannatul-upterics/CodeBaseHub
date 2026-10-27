@extends('layouts.dashboard')

@section('title', 'Edit Theme')
@section('breadcrumb', 'Edit Theme')

@section('content')
<div class="cbh-page-header">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('themes.show', $theme) }}" class="cbh-btn cbh-btn-secondary cbh-btn-icon" title="Back to Theme">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="cbh-page-title">Edit Theme: {{ $theme->theme_name }}</h1>
            <p class="cbh-page-subtitle">Update styling specifications, slug identifiers, or version numbering.</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="cbh-card">
            <div class="cbh-card-header">
                <h3 class="cbh-card-title">Theme Details</h3>
            </div>

            <div class="cbh-card-body">
                <form method="POST" action="{{ route('themes.update', $theme) }}">
                    @csrf
                    @method('PUT')

                    {{-- Theme Name --}}
                    <div class="mb-3">
                        <label for="theme_name" class="cbh-label">Theme Name <span class="text-danger">*</span></label>
                        <input type="text"
                               id="theme_name"
                               name="theme_name"
                               value="{{ old('theme_name', $theme->theme_name) }}"
                               class="cbh-input @error('theme_name') is-invalid @enderror"
                               placeholder="e.g. Modern Agency Dark"
                               required
                               autofocus>
                        @error('theme_name')
                            <div class="cbh-form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Slug --}}
                    <div class="mb-3">
                        <label for="slug" class="cbh-label">Unique Slug <span class="text-danger">*</span></label>
                        <input type="text"
                               id="slug"
                               name="slug"
                               value="{{ old('slug', $theme->slug) }}"
                               class="cbh-input font-monospace @error('slug') is-invalid @enderror"
                               placeholder="e.g. modern-agency-dark"
                               required>
                        <div class="cbh-form-hint">Unique system slug identifier for theme settings.</div>
                        @error('slug')
                            <div class="cbh-form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Version & Status in a row --}}
                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="version" class="cbh-label">Version</label>
                            <input type="text"
                                   id="version"
                                   name="version"
                                   value="{{ old('version', $theme->version) }}"
                                   class="cbh-input @error('version') is-invalid @enderror"
                                   placeholder="e.g. 1.0.0">
                            @error('version')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-6">
                            <label for="status" class="cbh-label">Status</label>
                            <select id="status" name="status" class="cbh-select @error('status') is-invalid @enderror">
                                <option value="1" {{ old('status', (string) $theme->status) === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('status', (string) $theme->status) === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Preview Image URL --}}
                    <div class="mb-3">
                        <label for="preview_image" class="cbh-label">Preview Image URL</label>
                        <input type="text"
                               id="preview_image"
                               name="preview_image"
                               value="{{ old('preview_image', $theme->preview_image) }}"
                               class="cbh-input @error('preview_image') is-invalid @enderror"
                               placeholder="e.g. https://images.unsplash.com/... or /images/themes/agency.jpg">
                        @error('preview_image')
                            <div class="cbh-form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="mb-4">
                        <label for="description" class="cbh-label">Description</label>
                        <textarea id="description"
                                  name="description"
                                  class="cbh-textarea @error('description') is-invalid @enderror"
                                  rows="3"
                                  placeholder="Describe the typography, palettes, and intended website categories…">{{ old('description', $theme->description) }}</textarea>
                        @error('description')
                            <div class="cbh-form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex align-items-center gap-2 pt-2 border-top">
                        <button type="submit" class="cbh-btn cbh-btn-primary">
                            <i class="bi bi-check-lg"></i>
                            Update Theme
                        </button>
                        <a href="{{ route('themes.show', $theme) }}" class="cbh-btn cbh-btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
