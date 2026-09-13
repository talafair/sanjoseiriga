<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In · TalaFair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
</head>
<body>

<div class="container-fluid login-wrap p-0">
    <div class="row g-0 min-vh-100">

        {{-- Illustration / hero panel --}}
        <div class="col-lg-6 d-none d-lg-flex login-hero align-items-center justify-content-center">
            <span class="blob b1"></span>
            <span class="blob b2"></span>
            <span class="blob b3"></span>
            <span class="blob b4"></span>
            <div class="text-center px-5 position-relative">
                <div class="display-1 mb-2">@include('partials.logo')</div>
                <div class="talafair-wordmark login-wordmark mb-4">TalaFair</div>
                <p class="fs-5 mb-4" style="max-width: 26rem; margin-inline: auto;">
                    Join the community, climb the leaderboard and collect rewards.
                </p>
                <div class="d-flex justify-content-center gap-4 fw-semibold">
                    <span><i class="bi bi-trophy-fill me-2"></i>Rankings</span>
                    <span><i class="bi bi-stars me-2"></i>Prize Wheel</span>
                    <span><i class="bi bi-coin me-2"></i>Rewards</span>
                </div>
            </div>
        </div>

        {{-- Login form panel --}}
        <div class="col-lg-6 d-flex align-items-center justify-content-center py-5 px-3">
            <div class="card login-card p-2">
                <div class="card-body p-4 p-sm-5">

                    <div class="text-center mb-4">
                        <div class="login-logo mb-3">@include('partials.logo')</div>
                        <h2 class="fw-bold mt-3 mb-1">Welcome back!</h2>
                        <p class="text-secondary mb-0">Sign in to continue your journey</p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success rounded-3 py-2 small" role="alert">
                            <i class="bi bi-check-circle-fill me-1"></i>{{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger rounded-3 py-2 small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $errors->first() }}
                        </div>
                    @endif

                    <form action="{{ route('login.attempt') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="loginField" class="form-label fw-semibold">Username, Email, or Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text yg-addon"><i class="bi bi-person"></i></span>
                                <input type="text" name="login" value="{{ old('login') }}"
                                       class="form-control yg-input @error('login') is-invalid @enderror" id="loginField"
                                       placeholder="Username, email, or 09171234567" autocomplete="username" required autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="loginPassword" class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text yg-addon"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" class="form-control yg-input" id="loginPassword"
                                       placeholder="••••••••" autocomplete="current-password" required>
                                <button class="btn btn-outline-secondary border-0 bg-transparent text-secondary" type="button"
                                        id="togglePassword" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
                                <label class="form-check-label small fw-semibold text-secondary" for="rememberMe">Remember me</label>
                            </div>
                            <a href="{{ route('password.request') }}" class="small fw-semibold text-yg text-decoration-none">Forgot password?</a>
                        </div>

                        <button type="submit" class="btn btn-yg w-100 py-2 mb-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                        </button>
                    </form>

                    <div class="position-relative text-center my-4">
                        <hr>
                        <span class="position-absolute top-50 start-50 translate-middle bg-white px-3 small text-secondary">
                            New here? Sign up as
                        </span>
                    </div>

                    <div class="row g-2 login-signup-row">
                        <div class="col-4">
                            <a href="{{ route('register', ['role' => 'resident']) }}" class="btn btn-yg-outline w-100 login-signup-button">
                                <i class="bi bi-house-heart me-1"></i>Resident
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="{{ route('register', ['role' => 'guest']) }}" class="btn btn-yg-outline w-100 login-signup-button">
                                <i class="bi bi-ticket-perforated me-1"></i>Guest
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="{{ route('register', ['role' => 'official']) }}" class="btn btn-yg-outline w-100 login-signup-button">
                                <i class="bi bi-person-badge me-1"></i>Official
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input = document.getElementById('loginPassword');
        const icon = this.querySelector('i');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.classList.toggle('bi-eye', !show);
        icon.classList.toggle('bi-eye-slash', show);
        this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
</script>
</body>
</html>
