<div class="cbh-page-header mb-3">
    <div style="display:flex;align-items:center;gap:0.75rem;">
        <a href="{{ route('websites.index') }}"
           class="cbh-btn cbh-btn-secondary cbh-btn-icon"
           title="Back to Websites">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                <h1 class="cbh-page-title">{{ $website->website_name }}</h1>
                @if ($website->status === 1)
                    <span class="cbh-badge cbh-badge-success">
                        <i class="bi bi-circle-fill" style="font-size:0.4rem;"></i>
                        Active
                    </span>
                @else
                    <span class="cbh-badge cbh-badge-neutral">Inactive</span>
                @endif
                <span class="cbh-badge cbh-badge-neutral">#CBH-{{ str_pad($website->id, 4, '0', STR_PAD_LEFT) }}</span>
            </div>
            <p class="cbh-page-subtitle">
                <span class="cbh-domain">{{ $website->domain }}</span>
                &middot;
                <span class="cbh-provider">
                    <i class="bi bi-cloud"></i>
                    {{ ucfirst($website->provider) }}
                </span>
                @if ($website->customer)
                    &middot;
                    <span class="text-secondary">
                        <i class="bi bi-person"></i> {{ $website->customer->name }}
                    </span>
                @endif
            </p>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:0.5rem;">
        <a href="https://{{ $website->domain }}" target="_blank" rel="noopener noreferrer" class="cbh-btn cbh-btn-secondary">
            <i class="bi bi-box-arrow-up-right"></i>
            Visit Site
        </a>
        <a href="{{ route('websites.edit', $website) }}" class="cbh-btn cbh-btn-secondary">
            <i class="bi bi-pencil"></i>
            Edit Details
        </a>
    </div>
</div>

{{-- Workspace Subnavigation Tabs --}}
<nav class="cbh-subnav" aria-label="Website Workspace Navigation">
    <a href="{{ route('websites.show', $website) }}"
       class="cbh-subnav-item {{ ($activeTab ?? '') === 'overview' ? 'active' : '' }}">
        <i class="bi bi-info-circle"></i>
        <span>Overview</span>
    </a>
    <a href="{{ route('websites.pages.index', $website) }}"
       class="cbh-subnav-item {{ ($activeTab ?? '') === 'pages' ? 'active' : '' }}">
        <i class="bi bi-file-earmark-text"></i>
        <span>Pages &amp; Sections</span>
    </a>
    <a href="{{ route('websites.theme.index', $website) }}"
       class="cbh-subnav-item {{ ($activeTab ?? '') === 'theme' ? 'active' : '' }}">
        <i class="bi bi-palette"></i>
        <span>Theme Settings</span>
    </a>
    <a href="{{ route('websites.domains.index', $website) }}"
       class="cbh-subnav-item {{ ($activeTab ?? '') === 'domains' ? 'active' : '' }}">
        <i class="bi bi-hdd-network"></i>
        <span>Domains &amp; SSL</span>
    </a>
    <a href="{{ route('websites.media.index', $website) }}"
       class="cbh-subnav-item {{ ($activeTab ?? '') === 'media' ? 'active' : '' }}">
        <i class="bi bi-images"></i>
        <span>Media Library</span>
    </a>
    <a href="{{ route('websites.settings.index', $website) }}"
       class="cbh-subnav-item {{ ($activeTab ?? '') === 'settings' ? 'active' : '' }}">
        <i class="bi bi-sliders"></i>
        <span>Site Settings</span>
    </a>
    <a href="{{ route('websites.seo.index', $website) }}"
       class="cbh-subnav-item {{ ($activeTab ?? '') === 'seo' ? 'active' : '' }}">
        <i class="bi bi-search-heart"></i>
        <span>SEO &amp; Metadata</span>
    </a>
</nav>
