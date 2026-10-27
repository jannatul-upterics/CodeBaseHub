<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — CodeBaseHub Platform</title>

    {{-- Fonts: Instrument Sans via Bunny CDN --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet">

    {{-- Bootstrap 5.3 Framework --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- CodeBaseHub Core Design System Stylesheet --}}
    <link rel="stylesheet" href="{{ asset('css/cbh-design-system.css') }}">

    @stack('styles')
</head>
<body>
    {{-- Mobile Sidebar Backdrop --}}
    <div id="cbhBackdrop" class="cbh-sidebar-backdrop" onclick="cbhCloseSidebar()" aria-hidden="true"></div>

    <div class="cbh-layout">
        {{-- =========================================================
             SIDEBAR NAVIGATION
             ========================================================= --}}
        <aside id="cbhSidebar" class="cbh-sidebar" aria-label="Main Navigation">

            {{-- Brand Identity --}}
            <a href="{{ route('dashboard') }}" class="cbh-brand" title="CodeBaseHub Management Console">
                <div class="cbh-brand-mark">
                    <i class="bi bi-layers-fill"></i>
                </div>
                <div class="cbh-brand-info">
                    <div class="cbh-brand-text">CodeBase<span style="color: #60a5fa;">Hub</span></div>
                    <div class="cbh-brand-sub">Builder &amp; Fleet Platform</div>
                </div>
            </a>

            {{-- Navigation Categories --}}
            <nav class="cbh-nav" aria-label="Sidebar Menu">

                {{-- 1. OVERVIEW --}}
                <div class="cbh-nav-section">
                    <div class="cbh-nav-label">Overview</div>
                    <a href="{{ route('dashboard') }}"
                       class="cbh-nav-link {{ request()->routeIs('dashboard', 'home') ? 'active' : '' }}"
                       aria-current="{{ request()->routeIs('dashboard', 'home') ? 'page' : 'false' }}">
                        <i class="bi bi-grid-1x2-fill nav-icon"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                {{-- 2. PLATFORM FLEET --}}
                <div class="cbh-nav-section">
                    <div class="cbh-nav-label">Fleet Management</div>

                    <a href="{{ route('websites.index') }}"
                       class="cbh-nav-link {{ request()->routeIs('websites*') ? 'active' : '' }}"
                       aria-current="{{ request()->routeIs('websites*') ? 'page' : 'false' }}">
                        <i class="bi bi-globe2 nav-icon"></i>
                        <span>Websites</span>
                    </a>

                    <a href="{{ route('customers.index') }}"
                       class="cbh-nav-link {{ request()->routeIs('customers*') ? 'active' : '' }}"
                       aria-current="{{ request()->routeIs('customers*') ? 'page' : 'false' }}"
                       title="Manage customer organizations and tenant accounts">
                        <i class="bi bi-people-fill nav-icon"></i>
                        <span>Customers</span>
                    </a>

                    <a href="{{ route('domains.index') }}"
                       class="cbh-nav-link {{ request()->routeIs('domains*') ? 'active' : '' }}"
                       aria-current="{{ request()->routeIs('domains*') ? 'page' : 'false' }}"
                       title="DNS, SSL and custom domain mappings">
                        <i class="bi bi-hdd-network-fill nav-icon"></i>
                        <span>Domains</span>
                    </a>
                </div>

                {{-- 3. DESIGN & TEMPLATES --}}
                <div class="cbh-nav-section">
                    <div class="cbh-nav-label">Design &amp; Styling</div>

                    <a href="{{ route('themes.index') }}"
                       class="cbh-nav-link {{ request()->routeIs('themes*') ? 'active' : '' }}"
                       aria-current="{{ request()->routeIs('themes*') ? 'page' : 'false' }}"
                       title="Global and site themes">
                        <i class="bi bi-palette-fill nav-icon"></i>
                        <span>Themes</span>
                    </a>

                    <a href="{{ route('templates.index') }}"
                       class="cbh-nav-link {{ request()->routeIs('templates*') ? 'active' : '' }}"
                       aria-current="{{ request()->routeIs('templates*') ? 'page' : 'false' }}"
                       title="Page layout templates and wireframes">
                        <i class="bi bi-layout-text-window-reverse nav-icon"></i>
                        <span>Templates</span>
                    </a>
                </div>

                {{-- 4. BUILDER & CONTENT --}}
                <div class="cbh-nav-section">
                    <div class="cbh-nav-label">Builder &amp; Content</div>

                    <a href="{{ route('pages.index') }}"
                       class="cbh-nav-link {{ request()->routeIs('pages*') ? 'active' : '' }}"
                       aria-current="{{ request()->routeIs('pages*') ? 'page' : 'false' }}"
                       title="Manage website pages and dynamic sections">
                        <i class="bi bi-file-earmark-text-fill nav-icon"></i>
                        <span>Pages</span>
                    </a>

                    <a href="{{ route('media.index') }}"
                       class="cbh-nav-link {{ request()->routeIs('media*') ? 'active' : '' }}"
                       aria-current="{{ request()->routeIs('media*') ? 'page' : 'false' }}"
                       title="Manage uploaded images, videos, and documents">
                        <i class="bi bi-images nav-icon"></i>
                        <span>Media Library</span>
                    </a>

                    <a href="{{ route('metadata.index') }}"
                       class="cbh-nav-link {{ request()->routeIs('metadata*') ? 'active' : '' }}"
                       aria-current="{{ request()->routeIs('metadata*') ? 'page' : 'false' }}"
                       title="Meta tags, canonical URLs, robots, and OpenGraph tags">
                        <i class="bi bi-search-heart-fill nav-icon"></i>
                        <span>SEO &amp; Metadata</span>
                    </a>
                </div>

                {{-- 5. SETTINGS --}}
                <div class="cbh-nav-section">
                    <div class="cbh-nav-label">Settings</div>

                    <a href="{{ route('settings.index') }}"
                       class="cbh-nav-link {{ request()->routeIs('settings*') ? 'active' : '' }}"
                       aria-current="{{ request()->routeIs('settings*') ? 'page' : 'false' }}"
                       title="Website key-value parameters and configurations">
                        <i class="bi bi-sliders nav-icon"></i>
                        <span>Settings</span>
                    </a>
                </div>
            </nav>

            {{-- Sidebar Footer with User Details --}}
            <div class="cbh-sidebar-footer">
                <div class="cbh-user-row">
                    <div class="cbh-user-avatar">
                        {{ strtoupper(substr(auth()->user()?->name ?? 'AD', 0, 2)) }}
                    </div>
                    <div class="overflow-hidden">
                        <div class="cbh-user-name">{{ auth()->user()?->name ?? 'Admin User' }}</div>
                        <div class="cbh-user-role">Administrator</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="cbh-logout-btn">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Sign Out</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- =========================================================
             MAIN VIEWPORT & TOPBAR
             ========================================================= --}}
        <div class="cbh-main">

            {{-- Top Navigation Bar --}}
            <header class="cbh-topbar" aria-label="Top Navigation">
                <div class="d-flex align-items-center gap-2">
                    <button class="cbh-hamburger" onclick="cbhToggleSidebar()" aria-label="Toggle navigation drawer">
                        <i class="bi bi-list"></i>
                    </button>

                    <nav class="cbh-breadcrumb" aria-label="Breadcrumb">
                        <a href="{{ route('dashboard') }}">CodeBaseHub</a>
                        <span class="sep"><i class="bi bi-chevron-right"></i></span>
                        <span class="current">@yield('breadcrumb', 'Dashboard')</span>
                    </nav>
                </div>

                <div class="cbh-topbar-actions">
                    {{-- Environment / Status Tag --}}
                    <span class="cbh-badge cbh-badge-primary d-none d-md-inline-flex">
                        <i class="bi bi-circle-fill" style="font-size:0.4rem;"></i>
                        SaaS Platform
                    </span>

                    {{-- User Profile Menu --}}
                    <div class="dropdown">
                        <button class="cbh-user-dropdown-btn dropdown-toggle"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">
                            <div class="cbh-topbar-avatar">
                                {{ strtoupper(substr(auth()->user()?->name ?? 'AD', 0, 2)) }}
                            </div>
                            <span class="d-none d-lg-block fw-semibold text-truncate" style="font-size:0.835rem; color:var(--cbh-text-primary); max-width: 140px;">
                                {{ auth()->user()?->name ?? 'Admin' }}
                            </span>
                            <i class="bi bi-chevron-down d-none d-lg-block" style="font-size:0.65rem; color:var(--cbh-text-muted);"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border mt-1" style="min-width:220px; border-color:var(--cbh-border)!important;">
                            <li class="px-3 py-2" style="border-bottom:1px solid var(--cbh-border);">
                                <div class="fw-semibold text-truncate" style="font-size:0.84rem; color:var(--cbh-text-primary);">
                                    {{ auth()->user()?->name ?? 'Admin User' }}
                                </div>
                                <div class="text-truncate" style="font-size:0.75rem; color:var(--cbh-text-muted);">
                                    {{ auth()->user()?->email ?? 'admin@codebasehub.com' }}
                                </div>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('dashboard') }}" style="font-size:0.84rem;">
                                    <i class="bi bi-speedometer2 text-muted"></i>
                                    Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('websites.index') }}" style="font-size:0.84rem;">
                                    <i class="bi bi-globe2 text-muted"></i>
                                    Websites Directory
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger" style="font-size:0.84rem;">
                                        <i class="bi bi-box-arrow-right"></i>
                                        Sign Out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            {{-- Flash Messages Banner --}}
            <div class="px-4 pt-3" aria-live="polite">
                @if (session('success'))
                    <div class="cbh-alert cbh-alert-success" role="alert" id="flash-success">
                        <i class="bi bi-check-circle-fill cbh-alert-icon"></i>
                        <span>{{ session('success') }}</span>
                        <button class="cbh-alert-close" onclick="this.closest('.cbh-alert').remove()" aria-label="Dismiss">
                            <i class="bi bi-x-lg" style="font-size:0.75rem;"></i>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="cbh-alert cbh-alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle-fill cbh-alert-icon"></i>
                        <span>{{ session('error') }}</span>
                        <button class="cbh-alert-close" onclick="this.closest('.cbh-alert').remove()" aria-label="Dismiss">
                            <i class="bi bi-x-lg" style="font-size:0.75rem;"></i>
                        </button>
                    </div>
                @endif
            </div>

            {{-- Main Dynamic Content --}}
            <main class="cbh-content">
                @yield('content')
            </main>

            {{-- Application Footer --}}
            <footer class="cbh-footer">
                <span>&copy; {{ date('Y') }} <strong style="color:var(--cbh-text-primary);">CodeBaseHub</strong> &mdash; Modern Website Management Platform.</span>
                <div class="d-flex align-items-center gap-2">
                    <span class="cbh-badge cbh-badge-neutral">Laravel {{ app()->version() }}</span>
                    <span class="cbh-badge cbh-badge-neutral">PHP {{ PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION }}</span>
                    <span class="cbh-badge cbh-badge-neutral">Design System v1.0</span>
                </div>
            </footer>
        </div>
    </div>

    {{-- Bootstrap 5.3 JavaScript Bundle --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        // Responsive sidebar drawer toggle
        function cbhToggleSidebar() {
            document.getElementById('cbhSidebar').classList.toggle('is-open');
            document.getElementById('cbhBackdrop').classList.toggle('is-open');
        }

        function cbhCloseSidebar() {
            document.getElementById('cbhSidebar').classList.remove('is-open');
            document.getElementById('cbhBackdrop').classList.remove('is-open');
        }

        // Auto-fade success alerts after 5 seconds
        const flashSuccess = document.getElementById('flash-success');
        if (flashSuccess) {
            setTimeout(() => {
                flashSuccess.style.transition = 'opacity 0.4s ease';
                flashSuccess.style.opacity = '0';
                setTimeout(() => flashSuccess.remove(), 400);
            }, 5000);
        }
    </script>

    @stack('scripts')
</body>
</html>
