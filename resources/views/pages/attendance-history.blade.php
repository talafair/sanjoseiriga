@extends('layouts.app')

@section('title', 'My Attendance')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-8">
    <h4 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-yg"></i>Your attendance</h4>

    <div class="card yg-card overflow-hidden">
      <div class="list-group list-group-flush">
        @forelse ($attendances as $row)
          <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
            <div>
              <div class="fw-semibold">{{ $row->announcement->title }}</div>
              <div class="small text-secondary">
                {{ $row->scanned_at->format('M j, Y g:i A') }}
                @if ($row->is_early)<span class="badge text-bg-warning ms-1">Early</span>@endif
              </div>
            </div>
            <span class="fw-bold text-yg">+{{ $row->points_awarded }}</span>
          </div>
        @empty
          <div class="list-group-item text-center text-secondary py-5">
            You have not checked in to an event yet.
          </div>
        @endforelse
      </div>
    </div>

    <div class="mt-3">{{ $attendances->links() }}</div>
  </div>
</div>
@endsection
