@extends('layouts.app')

@section('title', 'Notification update')

@section('content')
  <div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-8">
      <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 mb-3">
        <i class="bi bi-arrow-left me-1"></i>All notifications
      </a>

      <section class="card yg-card mt-3">
        <div class="card-body p-4 p-md-5">
          <div class="d-flex align-items-start gap-3">
            <i class="bi bi-bell-fill text-yg fs-3 flex-shrink-0" aria-hidden="true"></i>
            <div class="min-w-0">
              <h2 class="h4 fw-bold mb-2">{{ $notification->title }}</h2>
              <time class="small text-secondary"
                    datetime="{{ $notification->created_at->toIso8601String() }}">
                {{ $notification->created_at->format('F j, Y g:i A') }}
              </time>
            </div>
          </div>

          @if (! $available)
            <div class="alert alert-warning mt-4 mb-0" role="status">
              This update is no longer available.
            </div>
          @endif

          @if ($notification->body)
            <p class="mt-4 mb-0 text-break">{{ $notification->body }}</p>
          @elseif ($available)
            <p class="mt-4 mb-0 text-secondary">There are no additional details for this update.</p>
          @endif

          @if ($available && $destination)
            <a href="{{ $destination }}" class="btn btn-yg mt-4">{{ $destinationLabel }}</a>
          @endif
        </div>
      </section>
    </div>
  </div>
@endsection
