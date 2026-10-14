<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#151515">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <title>@yield('title', 'TalaFair') · TalaFair</title>
    @include('partials.theme-init')
    <link rel="icon" href="{{ asset('images/talafair-logo.png') }}" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}?v={{ filemtime(public_path('css/theme.css')) }}" rel="stylesheet">
    @stack('styles')
</head>
<body>

<header class="yg-topbar sticky-top">
    <div class="container-fluid d-flex align-items-center gap-2">
        <button class="sidebar-toggle" id="navToggle" type="button"
                aria-controls="mainNav" aria-expanded="true" aria-label="Collapse navigation">
            <i class="bi bi-layout-sidebar-inset sidebar-toggle-desktop-icon" aria-hidden="true"></i>
            <i class="bi bi-list sidebar-toggle-mobile-icon" aria-hidden="true"></i>
        </button>
        <a class="navbar-brand d-flex align-items-center gap-2 mb-0" href="{{ url('/') }}">
            @include('partials.logo')
            <span class="talafair-wordmark">TalaFair<span class="text-yg">.</span></span>
        </a>
        <div class="navbar-actions d-flex align-items-center gap-2">
            @auth
                @php
                    $unreadNotifications = auth()->user()->unreadAppNotifications()->count();
                @endphp
            @endauth
            @auth
                <a class="mobile-notification notification-toggle" href="{{ route('notifications.index') }}"
                   aria-label="Notifications">
                    <i class="bi bi-bell"></i>
                    @if ($unreadNotifications)
                        <span class="notification-badge">
                            {{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}
                        </span>
                    @endif
                </a>
            @endauth
        </div>
    </div>
</header>

<div class="app-layout" id="appLayout">
    <aside class="yg-navbar" id="mainNav" aria-label="Main navigation">
        <div class="mobile-drawer-header">
            <span class="mobile-drawer-title">Navigation</span>
            <button class="mobile-drawer-close" type="button" aria-label="Close navigation">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        <nav>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}" aria-label="Home" title="Home">
                        <i class="bi bi-house-door nav-icon" aria-hidden="true"></i><span class="nav-label">Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('announcements') ? 'active' : '' }}" href="{{ route('announcements') }}" aria-label="Announcements" title="Announcements">
                        <i class="bi bi-megaphone nav-icon" aria-hidden="true"></i><span class="nav-label">Announcements</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('leaderboard') ? 'active' : '' }}" href="{{ route('leaderboard') }}" aria-label="Leaderboard" title="Leaderboard">
                        <i class="bi bi-trophy nav-icon" aria-hidden="true"></i><span class="nav-label">Leaderboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('badges') ? 'active' : '' }}" href="{{ route('badges.catalog') }}" aria-label="Badges" title="Badges">
                        <i class="bi bi-award nav-icon" aria-hidden="true"></i><span class="nav-label">Badges</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('games') ? 'active' : '' }}" href="{{ route('games.index') }}" aria-label="Games" title="Games">
                        <i class="bi bi-controller nav-icon" aria-hidden="true"></i><span class="nav-label">Games</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('surveys') ? 'active' : '' }}" href="{{ route('surveys.index') }}" aria-label="Surveys" title="Surveys">
                        <i class="bi bi-bar-chart-line nav-icon" aria-hidden="true"></i><span class="nav-label">Surveys</span>
                    </a>
                </li>
                @auth
                    @if (auth()->user()->is_verified && ! auth()->user()->isOfficial())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('spin') ? 'active' : '' }}" href="{{ route('spin') }}" aria-label="Spin the Wheel" title="Spin the Wheel">
                                <i class="bi bi-stars nav-icon" aria-hidden="true"></i><span class="nav-label">Spin the Wheel</span>
                            </a>
                        </li>
                    @endif
                @endauth
                @auth
                    <li class="nav-item desktop-notification">
                        <a class="nav-link notification-nav-link {{ request()->is('notifications') ? 'active' : '' }}"
                           href="{{ route('notifications.index') }}" aria-label="Notifications" title="Notifications">
                            <i class="bi bi-bell nav-icon" aria-hidden="true"></i><span class="nav-label">Notifications</span>
                            @if ($unreadNotifications)
                                <span class="badge rounded-pill bg-danger ms-1">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
                            @endif
                        </a>
                    </li>
                @endauth
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('account') ? 'active' : '' }}" href="{{ route('account') }}" aria-label="Account" title="Account">
                        <i class="bi bi-person-circle nav-icon" aria-hidden="true"></i><span class="nav-label">Account</span>
                    </a>
                </li>
                @auth
                    @if (auth()->user()->isOfficial())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->is('prizes') || request()->is('users') || request()->is('manage/badges') || request()->is('audit-logs') || request()->is('manage/games') ? 'active' : '' }}"
                               href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Manage" title="Manage">
                                <i class="bi bi-sliders nav-icon" aria-hidden="true"></i><span class="nav-label">Manage</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3">
                                <li>
                                    <a class="dropdown-item fw-semibold py-2" href="{{ route('announcements') }}">
                                        <i class="bi bi-megaphone me-2 text-yg"></i>Announcements
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item fw-semibold py-2" href="{{ route('prizes.index') }}">
                                        <i class="bi bi-gift me-2 text-yg"></i>Prizes
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item fw-semibold py-2" href="{{ route('users.index') }}">
                                        <i class="bi bi-people me-2 text-yg"></i>Users
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item fw-semibold py-2" href="{{ route('badges.index') }}">
                                        <i class="bi bi-award me-2 text-yg"></i>Badges
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item fw-semibold py-2" href="{{ route('audit-logs.index') }}">
                                        <i class="bi bi-clock-history me-2 text-yg"></i>System log
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item fw-semibold py-2" href="{{ route('games.index') }}">
                                        <i class="bi bi-controller me-2 text-yg"></i>Games
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item fw-semibold py-2" href="{{ route('surveys.manage') }}">
                                        <i class="bi bi-bar-chart-line me-2 text-yg"></i>Manage surveys
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                    @include('partials.theme-toggle')
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="nav-link text-logout border-0 bg-transparent">
                                <i class="bi bi-box-arrow-right nav-icon" aria-hidden="true"></i><span class="nav-label">Logout</span>
                            </button>
                        </form>
                    </li>
                @endauth
            </ul>
        </nav>
    </aside>
    <div class="mobile-nav-backdrop" id="mobileNavBackdrop" aria-hidden="true"></div>

    <div class="app-content">
      <main class="py-4 py-lg-5">
        <div class="container">
        @if (session('success'))
            <div class="alert alert-success rounded-3 alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger rounded-3 alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
            @yield('content')
        </div>
      </main>

      <footer class="yg-footer py-3 mt-auto">
        <div class="container d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 small">
        <span>© {{ date('Y') }} TalaFair. All rights reserved.</span>
        <span class="d-flex align-items-center gap-3">
            @auth
                @if (auth()->user()->isOfficial())
                    <a href="{{ route('audit-logs.index') }}" class="text-reset text-decoration-none"><i class="bi bi-clock-history me-1 text-yg"></i>View system log</a>
                @endif
            @endauth
            <span>Made by Team Athena <i class="bi bi-heart-fill text-yg"></i> </span>
        </span>
        </div>
      </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (() => {
        const layout = document.getElementById('appLayout');
        const drawer = document.getElementById('mainNav');
        const backdrop = document.getElementById('mobileNavBackdrop');
        const toggler = document.getElementById('navToggle');
        const closeButton = document.querySelector('.mobile-drawer-close');
        const desktop = window.matchMedia('(min-width: 992px)');
        if (!layout || !drawer || !backdrop || !toggler) return;

        const setMobileDrawer = (isOpen, returnFocus = false) => {
            layout.classList.toggle('is-drawer-open', isOpen);
            backdrop.classList.toggle('is-visible', isOpen);
            backdrop.setAttribute('aria-hidden', String(!isOpen));
            document.body.classList.toggle('nav-drawer-open', isOpen);
            toggler.setAttribute('aria-expanded', String(isOpen));
            toggler.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
            if (isOpen) closeButton?.focus();
            else if (returnFocus) toggler.focus();
        };

        const syncViewport = () => {
            if (desktop.matches) {
                setMobileDrawer(false);
                toggler.setAttribute('aria-expanded', String(!layout.classList.contains('is-sidebar-collapsed')));
                toggler.setAttribute('aria-label', layout.classList.contains('is-sidebar-collapsed') ? 'Expand navigation' : 'Collapse navigation');
            } else {
                layout.classList.remove('is-sidebar-collapsed');
                setMobileDrawer(false);
            }
        };

        toggler.addEventListener('click', () => {
            if (desktop.matches) {
                const isCollapsed = layout.classList.toggle('is-sidebar-collapsed');
                toggler.setAttribute('aria-expanded', String(!isCollapsed));
                toggler.setAttribute('aria-label', isCollapsed ? 'Expand navigation' : 'Collapse navigation');
                return;
            }
            setMobileDrawer(!layout.classList.contains('is-drawer-open'));
        });

        closeButton?.addEventListener('click', () => setMobileDrawer(false, true));
        backdrop.addEventListener('click', () => setMobileDrawer(false, true));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && layout.classList.contains('is-drawer-open')) {
                setMobileDrawer(false, true);
            }
        });
        drawer.querySelectorAll('a:not(.dropdown-toggle)').forEach((link) => {
            link.addEventListener('click', () => {
                if (!desktop.matches) setMobileDrawer(false);
            });
        });

        drawer.querySelectorAll('.dropdown-toggle').forEach((link) => {
            link.addEventListener('click', () => {
                if (desktop.matches && layout.classList.contains('is-sidebar-collapsed')) {
                    layout.classList.remove('is-sidebar-collapsed');
                    toggler.setAttribute('aria-expanded', 'true');
                    toggler.setAttribute('aria-label', 'Collapse navigation');
                }
            });
        });

        desktop.addEventListener('change', syncViewport);
        syncViewport();
    })();
</script>
<script>
    (() => {
        let installPrompt = null;
        const installButton = document.getElementById('install-app');

        const isInstalled = () => window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;

        const hideInstallButton = () => installButton?.classList.add('d-none');
        if (isInstalled()) hideInstallButton();

        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            installPrompt = event;
            if (!isInstalled()) installButton?.classList.remove('d-none');
        });

        installButton?.addEventListener('click', async () => {
            if (!installPrompt) return;
            installButton.disabled = true;
            installPrompt.prompt();
            const choice = await installPrompt.userChoice;
            if (choice.outcome === 'accepted') hideInstallButton();
            installPrompt = null;
            installButton.disabled = false;
        });

        window.addEventListener('appinstalled', () => {
            installPrompt = null;
            hideInstallButton();
        });

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('{{ asset('sw.js') }}'));
        }
    })();
</script>
<script>
    document.querySelectorAll('[data-title-case]').forEach((field) => {
        const titleCase = () => {
            field.value = field.value
                .toLowerCase()
                .replace(/(^|[\s'-])([a-z])/g, (_, separator, letter) => separator + letter.toUpperCase());
        };

        field.addEventListener('blur', titleCase);
        field.form?.addEventListener('submit', titleCase);
    });
</script>
@stack('scripts')
</body>
</html>
