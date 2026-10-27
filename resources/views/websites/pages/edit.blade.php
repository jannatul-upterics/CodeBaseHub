@extends('layouts.dashboard')

@section('title', 'Edit Page — ' . $page->title)
@section('breadcrumb', 'Edit Page')

@section('content')
@include('websites._header', ['activeTab' => 'pages'])

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="cbh-card">
            <div class="cbh-card-header">
                <div>
                    <h2 class="cbh-card-title">Edit Page</h2>
                    <p class="cbh-card-subtitle">Editing /{{ $page->slug }} under {{ $website->website_name }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('websites.pages.show', [$website, $page]) }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm">
                        <i class="bi bi-layers"></i>
                        View Sections
                    </a>
                    <a href="{{ route('websites.pages.index', $website) }}" class="cbh-btn cbh-btn-secondary cbh-btn-sm">
                        <i class="bi bi-arrow-left"></i>
                        Back to Pages
                    </a>
                </div>
            </div>

            <form method="POST" action="{{ route('websites.pages.update', [$website, $page]) }}" novalidate>
                @csrf
                @method('PUT')
                <div class="cbh-card-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="title" class="cbh-form-label">
                                Page Title <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   id="title"
                                   name="title"
                                   value="{{ old('title', $page->title) }}"
                                   class="cbh-input @error('title') is-invalid @enderror"
                                   required>
                            @error('title')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="slug" class="cbh-form-label">
                                URL Slug <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0 font-monospace" style="font-size:0.85rem;">/</span>
                                <input type="text"
                                       id="slug"
                                       name="slug"
                                       value="{{ old('slug', $page->slug) }}"
                                       class="cbh-input font-monospace @error('slug') is-invalid @enderror"
                                       required>
                            </div>
                            <div class="form-text" style="font-size:0.75rem;">Lowercase letters, numbers, and dashes only.</div>
                            @error('slug')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="page_type" class="cbh-form-label">
                                Page Type <span class="text-danger">*</span>
                            </label>
                            <select id="page_type" name="page_type" class="cbh-select @error('page_type') is-invalid @enderror" required>
                                <option value="standard" {{ old('page_type', $page->page_type) === 'standard' ? 'selected' : '' }}>Standard Page</option>
                                <option value="landing" {{ old('page_type', $page->page_type) === 'landing' ? 'selected' : '' }}>Landing Page</option>
                                <option value="contact" {{ old('page_type', $page->page_type) === 'contact' ? 'selected' : '' }}>Contact Page</option>
                                <option value="blog" {{ old('page_type', $page->page_type) === 'blog' ? 'selected' : '' }}>Blog / Articles</option>
                                <option value="custom" {{ old('page_type', $page->page_type) === 'custom' ? 'selected' : '' }}>Custom Page</option>
                            </select>
                            @error('page_type')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="status" class="cbh-form-label">Publishing Status</label>
                            <select id="status" name="status" class="cbh-select @error('status') is-invalid @enderror">
                                <option value="1" {{ old('status', (string)$page->status) === '1' ? 'selected' : '' }}>Published (Active)</option>
                                <option value="0" {{ old('status', (string)$page->status) === '0' ? 'selected' : '' }}>Draft (Offline)</option>
                            </select>
                            @error('status')
                                <div class="cbh-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6 d-flex align-items-center">
                            <div class="form-check form-switch pt-3">
                                <input class="form-check-input"
                                       type="checkbox"
                                       role="switch"
                                       id="is_homepage"
                                       name="is_homepage"
                                       value="1"
                                       {{ old('is_homepage', $page->is_homepage) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_homepage">
                                    Set as Website Homepage
                                </label>
                                <div class="form-text" style="font-size:0.75rem;">
                                    @if ($currentHomepage && $currentHomepage->id !== $page->id)
                                        Currently '{{ $currentHomepage->title }}' is homepage. Checking this will unseat it.
                                    @elseif ($page->is_homepage)
                                        This page is currently the designated homepage.
                                    @else
                                        No homepage is configured.
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cbh-card-footer bg-light border-top p-3 d-flex justify-content-end gap-2">
                    <a href="{{ route('websites.pages.index', $website) }}" class="cbh-btn cbh-btn-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="cbh-btn cbh-btn-primary">
                        <i class="bi bi-check-lg"></i>
                        Update Page
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
