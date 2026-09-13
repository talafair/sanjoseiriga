<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password · TalaFair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
</head>
<body>
<div class="container d-flex align-items-center justify-content-center min-vh-100 py-5">
    <div class="card login-card p-2">
        <div class="card-body p-4 p-sm-5">
            <div class="text-center mb-4">
                <div class="login-logo mb-3">@include('partials.logo')</div>
                <h2 class="fw-bold mt-3 mb-1">Reset your password</h2>
                <p class="text-secondary mb-0">Enter your email and we will send you a reset link.</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success rounded-3 py-2 small">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger rounded-3 py-2 small">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('password.email') }}" method="POST">
                @csrf
                <label for="email" class="form-label fw-semibold">Email address</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                       class="form-control yg-input mb-3 @error('email') is-invalid @enderror"
                       autocomplete="email" required autofocus>
                <button type="submit" class="btn btn-yg w-100 py-2 mb-3">
                    <i class="bi bi-envelope me-2"></i>Send reset link
                </button>
            </form>
            <div class="text-center">
                <a href="{{ route('login') }}" class="small fw-semibold text-yg text-decoration-none">Back to sign in</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>