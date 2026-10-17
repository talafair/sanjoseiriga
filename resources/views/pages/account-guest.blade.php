@extends('layouts.app')

@section('title', 'My Account')

@section('content')
<div class="page-header p-4 p-lg-5 mb-4">
    <h2 class="fw-bold mb-2">Your TalaFair account</h2>
    <p class="mb-0 opacity-75">Sign in or create an account to manage your profile, points, and notifications.</p>
</div>

<div class="card yg-card">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('login') }}" class="btn btn-primary fw-semibold">
                <i class="bi bi-box-arrow-in-right me-1"></i>Sign in
            </a>
            <a href="{{ route('register') }}" class="btn btn-outline-secondary fw-semibold">
                <i class="bi bi-person-plus me-1"></i>Create an account
            </a>
        </div>
    </div>
</div>
@endsection
