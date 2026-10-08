@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
  <div class="notifications-page">
    <div class="page-header p-4 p-lg-5 mb-4">
      <div class="row align-items-center position-relative" style="z-index: 1;">
        <div class="col">
          <h2 class="fw-bold mb-1"><i class="bi bi-bell-fill me-2"></i>Notifications</h2>
          <p class="mb-0 opacity-75">Updates concerning your account and community activities.</p>
        </div>
        <div class="col-auto mt-3 mt-md-0">
          @if ($unreadCount)
            <form method="POST" action="{{ route('notifications.read-all') }}">
              @csrf
              <button class="btn btn-light fw-semibold"><i class="bi bi-check2-all me-1"></i>Mark all as read</button>
            </form>
          @endif
        </div>
      </div>
    </div>

    <div class="card yg-card">
      <div class="list-group list-group-flush">
        @forelse ($notifications as $notification)
          <div class="list-group-item px-4 py-3 {{ $notification->read_at ? '' : 'bg-light' }}">
            <div class="d-flex gap-3">
              <div class="text-yg fs-4 flex-shrink-0"><i class="bi bi-bell{{ $notification->read_at ? '' : '-fill' }}"></i></div>
              <div class="flex-grow-1">
                <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2">
                  <h6 class="mb-1 fw-bold">{{ $notification->title }}</h6>
                  <time class="small text-secondary" datetime="{{ $notification->created_at->toIso8601String() }}">
                    {{ $notification->created_at->diffForHumans() }}
                  </time>
                </div>
                @if ($notification->body)
                  <p class="mb-2 text-secondary">{{ $notification->body }}</p>
                @endif
                <a href="{{ route('notifications.open', $notification) }}" class="small fw-semibold text-yg text-decoration-none">
                  {{ $notification->announcement ? 'View related announcement' : ($notification->survey ? 'Answer survey' : 'View update') }}
                  <i class="bi bi-arrow-right"></i>
                </a>
              </div>
              @if (! $notification->read_at)
                <span class="badge badge-yg align-self-start">New</span>
              @endif
            </div>
          </div>
        @empty
          <div class="notifications-empty text-center py-5 px-4">
            <i class="bi bi-bell fs-1 d-block mb-2"></i>
            <p class="mb-0">You do not have any notifications yet.</p>
          </div>
        @endforelse
      </div>
    </div>

    @if ($notifications->hasPages())
      <div class="mt-4">{{ $notifications->links() }}</div>
    @endif
  </div>
@endsection
