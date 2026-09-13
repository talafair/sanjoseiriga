<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#151515">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <title>@yield('title', 'TalaFair') · TalaFair</title>
    <link rel="icon" href="{{ asset('images/talafair-logo.png') }}" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>

<nav class="navbar navbar-expand-lg yg-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('/') }}">
            @include('partials.logo')
            <span class="talafair-wordmark">TalaFair<span class="text-yg">.</span></span>
        </a>
        <div class="navbar-actions d-flex align-items-center gap-2">
            @auth
                @php
                    $headerNotifications = auth()->user()->appNotifications()
                        ->with(['announcement', 'survey'])
                        ->limit(6)
                        ->get();
                    $unreadNotifications = auth()->user()->unreadAppNotifications()->count();
                @endphp
            @endauth
            @auth
                <a class="mobile-notification notification-toggle" href="{{ route('notifications.index') }}"
                   aria-label="Notifications">
                    <i class="bi bi-bell"></i>
                    @if ($unreadNotifications)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            {{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}
                        </span>
                    @endif
                </a>
            @endauth
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                    aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 mt-3 mt-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}">
                        <i class="bi bi-house-door me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('announcements') ? 'active' : '' }}" href="{{ route('announcements') }}">
                        <i class="bi bi-megaphone me-1"></i>Announcements
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('leaderboard') ? 'active' : '' }}" href="{{ route('leaderboard') }}">
                        <i class="bi bi-trophy me-1"></i>Leaderboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('badges') ? 'active' : '' }}" href="{{ route('badges.catalog') }}">
                        <i class="bi bi-award me-1"></i>Badges
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('games') ? 'active' : '' }}" href="{{ route('games.index') }}">
                        <i class="bi bi-controller me-1"></i>Games
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('surveys') ? 'active' : '' }}" href="{{ route('surveys.index') }}">
                        <i class="bi bi-bar-chart-line me-1"></i>Surveys
                    </a>
                </li>
                @auth
                    @if (auth()->user()->is_verified && ! auth()->user()->isOfficial())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('spin') ? 'active' : '' }}" href="{{ route('spin') }}">
                                <i class="bi bi-stars me-1"></i>Spin the Wheel
                            </a>
                        </li>
                    @endif
                @endauth
                @auth
                    <li class="nav-item desktop-notification">
                        <a class="nav-link notification-nav-link {{ request()->is('notifications') ? 'active' : '' }}"
                           href="{{ route('notifications.index') }}" aria-label="Notifications">
                            <i class="bi bi-bell me-1"></i>Notifications
                            @if ($unreadNotifications)
                                <span class="badge rounded-pill bg-danger ms-1">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
                            @endif
                        </a>
                    </li>
                @endauth
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('account') ? 'active' : '' }}" href="{{ route('account') }}">
                        <i class="bi bi-person-circle me-1"></i>Account
                    </a>
                </li>
                @auth
                    @if (auth()->user()->isOfficial())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->is('prizes') || request()->is('users') || request()->is('manage/badges') || request()->is('audit-logs') || request()->is('manage/games') ? 'active' : '' }}"
                               href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-sliders me-1"></i>Manage
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
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="nav-link text-logout border-0 bg-transparent">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </button>
                        </form>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
<div class="mobile-nav-backdrop" id="mobileNavBackdrop" aria-hidden="true"></div>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (() => {
        const drawer = document.getElementById('mainNav');
        const backdrop = document.getElementById('mobileNavBackdrop');
        const toggler = document.querySelector('.navbar-toggler');
        const closeButton = document.querySelector('.mobile-drawer-close');
        if (!drawer || !backdrop || !toggler) return;

        const setBackdrop = (isOpen) => {
            backdrop.classList.toggle('is-visible', isOpen);
            backdrop.setAttribute('aria-hidden', String(!isOpen));
        };

        drawer.addEventListener('show.bs.collapse', () => setBackdrop(true));
        drawer.addEventListener('hidden.bs.collapse', () => setBackdrop(false));
        closeButton?.addEventListener('click', () => bootstrap.Collapse.getOrCreateInstance(drawer).hide());
        backdrop.addEventListener('click', () => bootstrap.Collapse.getOrCreateInstance(drawer).hide());
        drawer.querySelectorAll('a:not(.dropdown-toggle)').forEach((link) => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) bootstrap.Collapse.getOrCreateInstance(drawer).hide();
            });
        });
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
