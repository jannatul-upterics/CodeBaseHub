@extends('layouts.dashboard')

@section('title', 'Edit Template')
@section('breadcrumb', 'Edit Template')

@section('content')
<div class="cbh-page-header">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('templates.show', $template) }}" class="cbh-btn cbh-btn-secondary cbh-btn-icon" title="Back to Template">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="cbh-page-title">Edit Template: {{ $template->template_name }}</h1>
            <p class="cbh-page-subtitle">Update layout blueprint specifications and metadata.</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="cbh-card">
            <div class="cbh-card-header">
                <h3 class="cbh-card-title">Template Specifications</h3>
            </div>

            <div class="cbh-card-body">
                <form method="POST" action="{{ route('templates.update', $template) }}">
                    @csrf
                    @method('PUT')

                    {{-- Template Name --}}
                    <div class="mb-3">
                        <label for="template_name" class="cbh-label">Template Name <span class="text-danger">*</span></label>
                        <input type="text"
                               id="template_name"
                               name="template_name"
                               value="{{ old('template_name', $template->template_name) }}"
                               class="cbh-input @error('template_name') is-invalid @enderror"
                               placeholder="e.g. SaaS Landing Page Pro"
                               required
                               autofocus>
                        @error('template_name')
                            <div class="cbh-form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Slug --}}
                    <div class="mb-3">
                        <label for="slug" class="cbh-label">Unique Slug <span class="text-danger">*</span></label>
                        <input type="text"
                               id="slug"
                               name="slug"
                               value="{{ old('slug', $template->slug) }}"
                               class="cbh-input font-monospace @error('slug') is-invalid @enderror"
                               placeholder="e.g. saas-landing-page-pro"
                               required>
                        <div class="cbh-form-hint">Unique system slug identifier.</div>
                        @error('slug')
                            <div class="cbh-form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Version & Status --}}
                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="version" class="cbh-label">Version</label>
                            <input type="text"
                                   id="version"
                                   name="version"
                                   value="{{ old('version', $template->version) }}"
                                   class="cbh-input @error('version') is-invalid @enderror"
                                   placeholder="e.g. 1.0.0">
                            @error('version')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-6">
                            <label for="status" class="cbh-label">Status</label>
                            <select id="status" name="status" class="cbh-select @error('status') is-invalid @enderror">
                                <option value="1" {{ old('status', (string) $template->status) === '1' ? 'selected' : '' }}>Ready to Deploy</option>
                                <option value="0" {{ old('status', (string) $template->status) === '0' ? 'selected' : '' }}>Draft</option>
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
                               value="{{ old('preview_image', $template->preview_image) }}"
                               class="cbh-input @error('preview_image') is-invalid @enderror"
                               placeholder="e.g. https://images.unsplash.com/... or /images/templates/saas.png">
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
                                  placeholder="Describe the layout blueprint…">{{ old('description', $template->description) }}</textarea>
                        @error('description')
                            <div class="cbh-form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex align-items-center gap-2 pt-2 border-top">
                        <button type="submit" class="cbh-btn cbh-btn-primary">
                            <i class="bi bi-check-lg"></i>
                            Update Template
                        </button>
                        <a href="{{ route('templates.show', $template) }}" class="cbh-btn cbh-btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
