<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#151515">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <title>@yield('title', 'TalaFair') · TalaFair</title>
    @include('partials.theme-init')
    <script>
        document.addEventListener('error', (event) => {
            const image = event.target;
            if (!(image instanceof HTMLImageElement) || !image.dataset.imageFallback) return;

            const fallback = image.dataset.imageFallback;
            delete image.dataset.imageFallback;
            image.src = fallback;
        }, true);
    </script>
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
                    @if (! auth()->check() || auth()->user()->isGuest() || (! auth()->user()->is_verified && ! auth()->user()->isSuperadmin()))
                        <span class="nav-link text-secondary disabled" aria-disabled="true" title="{{ ! auth()->check() ? 'Sign in to access badges.' : (auth()->user()->isGuest() ? 'Badges are not available to guest accounts.' : 'Verify your account to access badges.') }}">
                            <i class="bi bi-award nav-icon" aria-hidden="true"></i><span class="nav-label">Badges</span>
                        </span>
                    @else
                        <a class="nav-link {{ request()->is('badges') ? 'active' : '' }}" href="{{ route('badges.catalog') }}" aria-label="Badges" title="Badges">
                            <i class="bi bi-award nav-icon" aria-hidden="true"></i><span class="nav-label">Badges</span>
                        </a>
                    @endif
                </li>
                <li class="nav-item">
                    @if (! auth()->check() || auth()->user()->isGuest() || (! auth()->user()->is_verified && ! auth()->user()->isSuperadmin()))
                        <span class="nav-link text-secondary disabled" aria-disabled="true" title="{{ ! auth()->check() ? 'Sign in to access games.' : (auth()->user()->isGuest() ? 'Games are not available to guest accounts.' : 'Verify your account to access games.') }}">
                            <i class="bi bi-controller nav-icon" aria-hidden="true"></i><span class="nav-label">Games</span>
                        </span>
                    @else
                        <a class="nav-link {{ request()->is('games') ? 'active' : '' }}" href="{{ route('games.index') }}" aria-label="Games" title="Games">
                            <i class="bi bi-controller nav-icon" aria-hidden="true"></i><span class="nav-label">Games</span>
                        </a>
                    @endif
                </li>
                <li class="nav-item">
                    @if (! auth()->check() || (! auth()->user()->isGuest() && ! auth()->user()->is_verified && ! auth()->user()->isSuperadmin()))
                        <span class="nav-link text-secondary disabled" aria-disabled="true" title="{{ ! auth()->check() ? 'Sign in to access surveys.' : 'Verify your account to access surveys.' }}">
                            <i class="bi bi-bar-chart-line nav-icon" aria-hidden="true"></i><span class="nav-label">Surveys</span>
                        </span>
                    @else
                        <a class="nav-link {{ request()->is('surveys') ? 'active' : '' }}" href="{{ route('surveys.index') }}" aria-label="Surveys" title="Surveys">
                            <i class="bi bi-bar-chart-line nav-icon" aria-hidden="true"></i><span class="nav-label">Surveys</span>
                        </a>
                    @endif
                </li>
                @auth
                    @if (auth()->user()->is_verified && (! auth()->user()->isOfficial() || auth()->user()->isGuest()))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('spin') ? 'active' : '' }}" href="{{ route('spin') }}" aria-label="Spin the Wheel" title="Spin the Wheel">
                                <i class="bi bi-stars nav-icon" aria-hidden="true"></i><span class="nav-label">Spin the Wheel</span>
                            </a>
                        </li>
                    @elseif (auth()->user()->role === 'resident')
                        <li class="nav-item">
                            <span class="nav-link text-secondary disabled" aria-disabled="true" title="A Barangay Official must verify your resident account before you can spin.">
                                <i class="bi bi-stars nav-icon" aria-hidden="true"></i><span class="nav-label">Spin the Wheel</span>
                            </span>
                        </li>
                    @elseif (auth()->user()->isGuest())
                        <li class="nav-item">
                            <span class="nav-link text-secondary disabled" aria-disabled="true" title="Verify your guest account to access Spin the Wheel.">
                                <i class="bi bi-stars nav-icon" aria-hidden="true"></i><span class="nav-label">Spin the Wheel</span>
                            </span>
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
                        @php($isUnverifiedOfficial = auth()->user()->isUnverifiedOfficial())
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
                                    <a class="dropdown-item fw-semibold py-2 {{ $isUnverifiedOfficial ? 'text-secondary disabled' : '' }}" href="{{ $isUnverifiedOfficial ? '#' : route('prizes.index') }}" @if ($isUnverifiedOfficial) aria-disabled="true" tabindex="-1" title="Verification is required to access official features." @endif>
                                        <i class="bi bi-gift me-2 text-yg"></i>Prizes
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item fw-semibold py-2 {{ $isUnverifiedOfficial ? 'text-secondary disabled' : '' }}" href="{{ $isUnverifiedOfficial ? '#' : route('users.index') }}" @if ($isUnverifiedOfficial) aria-disabled="true" tabindex="-1" title="Verification is required to access official features." @endif>
                                        <i class="bi bi-people me-2 text-yg"></i>Users
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item fw-semibold py-2 {{ $isUnverifiedOfficial ? 'text-secondary disabled' : '' }}" href="{{ $isUnverifiedOfficial ? '#' : route('badges.index') }}" @if ($isUnverifiedOfficial) aria-disabled="true" tabindex="-1" title="Verification is required to access official features." @endif>
                                        <i class="bi bi-award me-2 text-yg"></i>Badges
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item fw-semibold py-2 {{ $isUnverifiedOfficial ? 'text-secondary disabled' : '' }}" href="{{ $isUnverifiedOfficial ? '#' : route('audit-logs.index') }}" @if ($isUnverifiedOfficial) aria-disabled="true" tabindex="-1" title="Verification is required to access official features." @endif>
                                        <i class="bi bi-clock-history me-2 text-yg"></i>System log
                                    </a>
                                </li>
                                <li>
                                    @if ($isUnverifiedOfficial)
                                        <span class="dropdown-item fw-semibold py-2 text-secondary disabled" aria-disabled="true" title="Verification is required to access games.">
                                            <i class="bi bi-controller me-2 text-yg"></i>Games
                                        </span>
                                    @else
                                        <a class="dropdown-item fw-semibold py-2" href="{{ route('games.index') }}">
                                            <i class="bi bi-controller me-2 text-yg"></i>Games
                                        </a>
                                    @endif
                                </li>
                                <li>
                                    <a class="dropdown-item fw-semibold py-2 {{ $isUnverifiedOfficial ? 'text-secondary disabled' : '' }}" href="{{ $isUnverifiedOfficial ? '#' : route('surveys.manage') }}" @if ($isUnverifiedOfficial) aria-disabled="true" tabindex="-1" title="Verification is required to access official features." @endif>
                                        <i class="bi bi-bar-chart-line me-2 text-yg"></i>Manage surveys
                                    </a>
                                </li>
                                @if ($isUnverifiedOfficial)
                                    <li><span class="dropdown-item-text small text-secondary text-wrap">Verification by a Barangay Administrator is required to use official management features.</span></li>
                                @endif
                            </ul>
                        </li>
                    @endif
                    @include('partials.theme-toggle')
                    <li class="nav-item">
                        <button class="nav-link border-0 bg-transparent switch-account-toggle" id="switchAccountToggle"
                                type="button" aria-label="Switch Account" title="Switch Account"
                                aria-controls="switchAccountPopover" aria-expanded="false">
                            <i class="bi bi-arrow-left-right nav-icon" aria-hidden="true"></i><span class="nav-label">Switch Account</span>
                            <span class="switch-account-avatar-stack" id="switchAccountAvatars" aria-hidden="true"></span>
                        </button>
                        <div class="switch-account-popover" id="switchAccountPopover" hidden>
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <h2 class="h6 fw-bold mb-0">Switch Account</h2>
                                <button type="button" class="btn-close" id="closeSwitchAccount"
                                        aria-label="Close account switcher"></button>
                            </div>
                            <p class="small text-secondary mb-2">Choose a saved profile, then enter its password.</p>
                            <div class="switch-account-list" id="switchAccountList"></div>
                            <div class="switch-account-empty small text-secondary py-2" id="switchAccountEmpty" hidden>
                                No accounts saved on this device yet.
                            </div>
                            <form action="{{ route('saved-accounts.add') }}" method="POST" class="mt-2">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-yg-outline w-100">
                                    <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Add Account
                                </button>
                            </form>
                        </div>
                    </li>
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
        @auth
            @if (auth()->user()->isUnverifiedOfficial())
                <div class="alert alert-warning rounded-3" role="alert">
                    <i class="bi bi-hourglass-split me-1"></i>Your official account is unverified. A Barangay Administrator must verify your account before you can use official management and attendance features.
                </div>
            @elseif (auth()->user()->role === 'resident' && ! auth()->user()->is_verified)
                <div class="alert alert-warning rounded-3" role="alert">
                    <i class="bi bi-hourglass-split me-1"></i>Your resident account is unverified. A Barangay Official must verify your account before you can access badges, games, surveys, attendance scanning, or Spin the Wheel. Your account, announcements, leaderboard, and logout remain available.
                </div>
            @endif
        @endauth
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
                    @if (auth()->user()->isUnverifiedOfficial())
                        <span class="text-secondary" title="Verification is required to access official features." aria-disabled="true"><i class="bi bi-clock-history me-1"></i>View system log</span>
                    @else
                        <a href="{{ route('audit-logs.index') }}" class="text-reset text-decoration-none"><i class="bi bi-clock-history me-1 text-yg"></i>View system log</a>
                    @endif
                @endif
            @endauth
            <span>Made by Team Athena <i class="bi bi-heart-fill text-yg"></i> </span>
        </span>
        </div>
      </footer>
    </div>
</div>

@auth
<div class="modal fade" id="app-setup-modal" tabindex="-1" aria-labelledby="app-setup-title" aria-hidden="true"
     data-user-id="{{ auth()->id() }}"
     data-push-endpoint="{{ route('push-subscriptions.store') }}"
     data-csrf-token="{{ csrf_token() }}"
     data-vapid-public-key="{{ config('webpush.vapid.public_key') }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content talafair-setup-modal border-0">
            <div class="talafair-setup-hero">
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="talafair-setup-brand" aria-hidden="true"><i class="bi bi-stars"></i></div>
                <div class="small fw-semibold text-uppercase talafair-setup-eyebrow">Make it yours</div>
                <h2 class="modal-title fw-bold mt-2 mb-2" id="app-setup-title">Welcome to TalaFair</h2>
                <p class="mb-0 talafair-setup-subtitle">A couple of quick options to make staying connected easier.</p>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex flex-column gap-3">
                    <div class="talafair-setup-option d-flex align-items-center gap-3">
                        <div class="talafair-setup-icon" aria-hidden="true"><i class="bi bi-phone"></i></div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">Install the app</div>
                            <div class="small text-secondary" id="app-install-help">Keep TalaFair close at hand on your home screen.</div>
                        </div>
                        <button type="button" class="btn btn-primary fw-semibold flex-shrink-0" id="app-setup-install">
                            <i class="bi bi-download me-1" aria-hidden="true"></i>Install
                        </button>
                    </div>
                    <div class="talafair-setup-option d-flex align-items-center gap-3">
                        <div class="talafair-setup-icon" aria-hidden="true"><i class="bi bi-bell"></i></div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">Turn on notifications</div>
                            <div class="small text-secondary" id="app-setup-push-status" role="status" aria-live="polite">Get important TalaFair updates on this device.</div>
                        </div>
                        <button type="button" class="btn btn-outline-dark fw-semibold flex-shrink-0" id="app-setup-push">
                            <i class="bi bi-bell me-1" aria-hidden="true"></i>Enable
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer talafair-setup-footer px-4 pb-4 pt-0">
                <div class="small text-secondary"><i class="bi bi-gear me-1" aria-hidden="true"></i>You can manage these options anytime from your Account page.</div>
                <button type="button" class="btn btn-light fw-semibold w-100 mt-3" data-bs-dismiss="modal">Maybe later</button>
            </div>
        </div>
    </div>
</div>
<style>
    .talafair-setup-modal {
        overflow: hidden;
        border-radius: 1.25rem;
        box-shadow: 0 1.5rem 4rem rgba(16, 24, 40, .22);
    }

    .talafair-setup-hero {
        position: relative;
        padding: 1.25rem 1.5rem 1.5rem;
        color: #fff;
        background: linear-gradient(135deg, #181818 0%, #34302a 58%, #80652a 100%);
    }

    .talafair-setup-hero .btn-close {
        display: block;
    }

    .talafair-setup-brand {
        display: grid;
        width: 2.75rem;
        height: 2.75rem;
        margin-top: .25rem;
        margin-bottom: 1rem;
        place-items: center;
        color: #241d0d;
        background: #f2c94c;
        border-radius: .9rem;
        font-size: 1.35rem;
    }

    .talafair-setup-eyebrow {
        color: #f2c94c;
        letter-spacing: .12em;
    }

    .talafair-setup-subtitle {
        max-width: 24rem;
        color: rgba(255, 255, 255, .78);
    }

    .talafair-setup-option {
        min-width: 0;
        padding: 1rem;
        background: var(--bs-tertiary-bg, #f8f9fa);
        border: 1px solid var(--bs-border-color, #dee2e6);
        border-radius: 1rem;
    }

    .talafair-setup-icon {
        display: grid;
        width: 2.75rem;
        height: 2.75rem;
        flex: 0 0 auto;
        place-items: center;
        color: #795d12;
        background: rgba(242, 201, 76, .22);
        border-radius: .85rem;
        font-size: 1.25rem;
    }

    .talafair-setup-footer {
        display: block;
        border-top: 0;
    }

    @media (max-width: 420px) {
        .talafair-setup-option {
            align-items: flex-start !important;
            flex-wrap: wrap;
        }

        .talafair-setup-option > div:nth-child(2) {
            flex: 1 1 calc(100% - 4rem) !important;
        }

        .talafair-setup-option > button {
            width: 100%;
        }
    }
</style>
@endauth

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
@if (session('savedAccountProfile'))
<script>
    (() => {
        const key = 'talafair.savedAccounts.v1';
        const profile = @json(session('savedAccountProfile'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        try {
            const saved = JSON.parse(localStorage.getItem(key) || '[]');
            const accounts = Array.isArray(saved)
                ? saved
                    .filter((account) => account
                        && typeof account.username === 'string'
                        && /^[a-zA-Z0-9_-]{3,30}$/.test(account.username)
                        && typeof account.name === 'string')
                    .filter((account) => account.username !== profile.username)
                    .map((account) => ({
                        username: account.username.slice(0, 30),
                        name: account.name.slice(0, 255),
                        avatar: typeof account.avatar === 'string' ? account.avatar.slice(0, 2048) : '',
                        role: typeof account.role === 'string' ? account.role.slice(0, 64) : 'Account',
                    }))
                : [];
            accounts.unshift(profile);
            localStorage.setItem(key, JSON.stringify(accounts));
        } catch (error) {
            console.error('Unable to save this TalaFair profile on this device.', error);
            window.alert('Your account was signed in, but this browser could not save its profile.');
        }
    })();
</script>
@endif
<script>
    (() => {
        const toggle = document.getElementById('switchAccountToggle');
        const popover = document.getElementById('switchAccountPopover');
        if (!toggle || !popover) return;

        document.body.append(popover);

        const storageKey = 'talafair.savedAccounts.v1';
        const list = document.getElementById('switchAccountList');
        const empty = document.getElementById('switchAccountEmpty');
        const avatarStack = document.getElementById('switchAccountAvatars');
        const close = document.getElementById('closeSwitchAccount');
        const switchUrl = @json(route('switch-account'));
        const csrfToken = @json(csrf_token());
        const fallbackAvatar = @json(asset('images/default-avatar.svg'));

        const readAccounts = () => {
            try {
                const data = JSON.parse(localStorage.getItem(storageKey) || '[]');
                return Array.isArray(data)
                    ? data.filter((account) => account
                        && typeof account.username === 'string'
                        && /^[a-zA-Z0-9_-]{3,30}$/.test(account.username)
                        && typeof account.name === 'string')
                    : [];
            } catch (error) {
                console.error('Unable to read saved TalaFair accounts.', error);
                return [];
            }
        };

        const renderButtonAvatars = (accounts) => {
            avatarStack.replaceChildren(...accounts.slice(0, 3).map((account) => {
                const avatar = document.createElement('img');
                avatar.alt = '';
                avatar.width = 24;
                avatar.height = 24;
                avatar.className = 'switch-account-nav-avatar rounded-circle object-fit-cover';
                avatar.src = fallbackAvatar;
                if (typeof account.avatar === 'string') {
                    try {
                        const candidate = new URL(account.avatar, window.location.href);
                        if (candidate.origin === window.location.origin) avatar.src = candidate.href;
                    } catch (error) {
                        console.error('Unable to load saved account avatar.', error);
                    }
                }
                return avatar;
            }));
        };

        const makeAccount = (account) => {
            const item = document.createElement('div');
            item.className = 'switch-account-item';

            const form = document.createElement('form');
            form.action = switchUrl;
            form.method = 'POST';
            form.className = 'switch-account-select';
            form.addEventListener('submit', (event) => {
                if (!window.confirm(`You will be logged out. Enter the password for ${account.name} to switch accounts.`)) {
                    event.preventDefault();
                    return;
                }
                form.querySelector('button[type="submit"]').disabled = true;
            });

            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_token';
            token.value = csrfToken;
            const username = document.createElement('input');
            username.type = 'hidden';
            username.name = 'saved_login';
            username.value = account.username;

            const select = document.createElement('button');
            select.type = 'submit';
            select.className = 'switch-account-profile';
            const avatar = document.createElement('img');
            avatar.alt = '';
            avatar.width = 38;
            avatar.height = 38;
            avatar.className = 'rounded-circle object-fit-cover flex-shrink-0';
            avatar.src = fallbackAvatar;
            if (typeof account.avatar === 'string') {
                try {
                    const candidate = new URL(account.avatar, window.location.href);
                    if (candidate.origin === window.location.origin) avatar.src = candidate.href;
                } catch (error) {
                    console.error('Unable to load saved profile picture.', error);
                }
            }
            const labels = document.createElement('span');
            labels.className = 'min-w-0 text-start';
            const name = document.createElement('span');
            name.className = 'd-block text-truncate fw-semibold';
            name.textContent = account.name.slice(0, 255);
            const role = document.createElement('span');
            role.className = 'd-block small opacity-75';
            role.textContent = typeof account.role === 'string' ? account.role.slice(0, 64) : 'Account';
            labels.append(name, role);
            select.append(avatar, labels);
            form.append(token, username, select);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-sm btn-outline-light switch-account-remove';
            remove.setAttribute('aria-label', `Remove ${account.name} from this device`);
            remove.title = 'Remove from this device';
            remove.innerHTML = '<i class="bi bi-trash" aria-hidden="true"></i>';
            remove.addEventListener('click', () => {
                try {
                    const remaining = readAccounts().filter((saved) => saved.username !== account.username);
                    localStorage.setItem(storageKey, JSON.stringify(remaining));
                    render();
                } catch (error) {
                    console.error('Unable to remove the saved TalaFair account.', error);
                    window.alert('This browser could not remove the saved account.');
                }
            });

            item.append(form, remove);
            return item;
        };

        const render = () => {
            const accounts = readAccounts();
            list.replaceChildren(...accounts.map(makeAccount));
            empty.hidden = accounts.length > 0;
            renderButtonAvatars(accounts);
        };

        const position = () => {
            if (popover.hidden) return;
            const bounds = toggle.getBoundingClientRect();
            const width = Math.min(288, window.innerWidth - 16);
            popover.style.width = `${width}px`;
            popover.style.left = `${Math.max(8, Math.min(bounds.right + 8, window.innerWidth - width - 8))}px`;
            popover.style.top = `${Math.max(8, Math.min(bounds.top, window.innerHeight - popover.offsetHeight - 8))}px`;
        };

        const closePopover = () => {
            popover.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        };

        toggle.addEventListener('click', () => {
            if (!popover.hidden) {
                closePopover();
                return;
            }
            render();
            popover.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            position();
        });
        close.addEventListener('click', closePopover);
        window.addEventListener('resize', position);
        window.addEventListener('scroll', position, true);
        window.addEventListener('storage', (event) => {
            if (event.key === storageKey) render();
        });
        document.addEventListener('click', (event) => {
            if (!popover.hidden && !popover.contains(event.target) && !toggle.contains(event.target)) closePopover();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !popover.hidden) {
                closePopover();
                toggle.focus();
            }
        });
    })();
</script>
<script>
    (() => {
        let installPrompt = null;
        const installButton = document.getElementById('install-app');
        const setupModal = document.getElementById('app-setup-modal');
        const setupInstallButton = document.getElementById('app-setup-install');
        const installHelp = document.getElementById('app-install-help');
        const setupPushButton = document.getElementById('app-setup-push');
        const setupPushStatus = document.getElementById('app-setup-push-status');
        const accountPushCard = document.getElementById('push-notification-card');
        const accountPushButton = document.getElementById('enable-push-notifications');
        const accountPushStatus = document.getElementById('push-notification-status');

        const isInstalled = () => window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;

        const hideInstallButtons = () => {
            installButton?.classList.add('d-none');
            setupInstallButton?.classList.add('d-none');
        };
        const setPushStatus = (message) => {
            if (setupPushStatus) setupPushStatus.textContent = message;
            if (accountPushStatus) accountPushStatus.textContent = message;
        };
        const pushPublicKey = setupModal?.dataset.vapidPublicKey || accountPushCard?.dataset.vapidPublicKey;
        const setPushButtonsDisabled = (disabled) => {
            if (setupPushButton) setupPushButton.disabled = disabled;
            if (accountPushButton) accountPushButton.disabled = disabled;
        };

        if (isInstalled()) hideInstallButtons();

        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            installPrompt = event;
            if (!isInstalled()) {
                installButton?.classList.remove('d-none');
                setupInstallButton?.classList.remove('d-none');
                if (installHelp) installHelp.textContent = 'Add TalaFair to your device home screen.';
            }
        });

        const installApp = async () => {
            if (!installPrompt) {
                if (installHelp) installHelp.textContent = 'Use your browser menu and choose “Add to Home Screen” or “Install app”.';
                return;
            }

            const prompt = installPrompt;
            installPrompt = null;
            if (installButton) installButton.disabled = true;
            if (setupInstallButton) setupInstallButton.disabled = true;
            try {
                await prompt.prompt();
                const choice = await prompt.userChoice;
                if (choice.outcome === 'accepted') hideInstallButtons();
            } catch (error) {
                console.error('Unable to show the app install prompt.', error);
                if (installHelp) installHelp.textContent = 'Unable to open the install prompt. Use your browser menu to install TalaFair.';
            } finally {
                if (installButton) installButton.disabled = false;
                if (setupInstallButton) setupInstallButton.disabled = false;
            }
        };
        installButton?.addEventListener('click', installApp);
        setupInstallButton?.addEventListener('click', installApp);

        window.addEventListener('appinstalled', () => {
            installPrompt = null;
            hideInstallButtons();
        });

        const decodePublicKey = (key) => {
            const padding = '='.repeat((4 - (key.length % 4)) % 4);
            const base64 = (key + padding).replace(/-/g, '+').replace(/_/g, '/');
            return Uint8Array.from(atob(base64), (character) => character.charCodeAt(0));
        };
        const enablePushNotifications = async () => {
            if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
                setPushStatus('Push notifications are not supported by this browser.');
                setPushButtonsDisabled(true);
                return;
            }
            if (!pushPublicKey) {
                setPushStatus('Push notifications are not configured on this server.');
                setPushButtonsDisabled(true);
                return;
            }

            setPushButtonsDisabled(true);
            setPushStatus('Enabling push notifications…');
            try {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    setPushStatus(permission === 'denied'
                        ? 'Notifications are blocked in your browser settings.'
                        : 'Allow notifications in your browser to enable them.');
                    setPushButtonsDisabled(false);
                    return;
                }

                const registration = await navigator.serviceWorker.ready;
                let subscription = await registration.pushManager.getSubscription();
                if (!subscription) {
                    subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: decodePublicKey(pushPublicKey),
                    });
                }

                const response = await fetch(
                    setupModal?.dataset.pushEndpoint || accountPushCard?.dataset.endpoint,
                    {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': setupModal?.dataset.csrfToken || accountPushCard?.dataset.csrfToken,
                        },
                        body: JSON.stringify(subscription.toJSON()),
                    }
                );
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Unable to enable push notifications.');

                setPushStatus('Push notifications are enabled on this device.');
                if (setupPushButton) {
                    setupPushButton.innerHTML = '<i class="bi bi-check-circle me-1"></i>Enabled';
                }
                if (accountPushButton) {
                    accountPushButton.innerHTML = '<i class="bi bi-check-circle me-1"></i>Notifications enabled';
                }
            } catch (error) {
                console.error('Unable to enable push notifications.', error);
                setPushStatus(error instanceof Error ? error.message : 'Unable to enable push notifications.');
                setPushButtonsDisabled(false);
            }
        };
        setupPushButton?.addEventListener('click', enablePushNotifications);
        accountPushButton?.addEventListener('click', enablePushNotifications);

        if (setupModal) {
            const storageKey = `talafair-app-setup-dismissed-${setupModal.dataset.userId}`;
            const hasDismissed = () => {
                try {
                    return window.localStorage.getItem(storageKey) === 'true';
                } catch (error) {
                    console.error('Unable to read app setup preference.', error);
                    return false;
                }
            };
            const rememberDismissal = () => {
                try {
                    window.localStorage.setItem(storageKey, 'true');
                } catch (error) {
                    console.error('Unable to save app setup preference.', error);
                }
            };
            setupModal.addEventListener('hidden.bs.modal', rememberDismissal);

            if ('Notification' in window && Notification.permission === 'granted') {
                setupPushButton.disabled = true;
                setupPushButton.innerHTML = '<i class="bi bi-check-circle me-1"></i>Enabled';
                setPushStatus('Notifications are allowed in your browser.');
            }
            if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
                setupPushButton.disabled = true;
                setPushStatus('Push notifications are not supported by this browser.');
            } else if (!pushPublicKey) {
                setupPushButton.disabled = true;
                setPushStatus('Push notifications are not configured on this server.');
            }

            window.addEventListener('load', () => {
                window.setTimeout(() => {
                    if (hasDismissed()) return;
                    if (isInstalled()) setupInstallButton?.classList.add('d-none');
                    else if (!installPrompt) setupInstallButton?.classList.remove('d-none');

                    const installNeeded = !isInstalled();
                    const pushNeeded = 'Notification' in window && Notification.permission !== 'granted';
                    if (installNeeded || pushNeeded) {
                        bootstrap.Modal.getOrCreateInstance(setupModal).show();
                    }
                }, 900);
            }, { once: true });
        }

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('{{ asset('sw.js') }}').catch((error) => {
                    console.error('Unable to register the service worker.', error);
                });
            });
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
