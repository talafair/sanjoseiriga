@extends('layouts.app')

@section('title', 'Not checked in — ' . $announcement->title)

@section('content')
  <div class="page-header p-4 p-lg-5 mb-4">
    <div class="row align-items-center position-relative" style="z-index: 1;">
      <div class="col">
        <h2 class="fw-bold mb-1"><i class="bi bi-person-x me-2"></i>Not checked in</h2>
        <p class="mb-0 opacity-75">{{ $announcement->title }}</p>
      </div>
      <div class="col-auto">
        <a href="{{ route('announcements.show', $announcement) }}" class="btn btn-light fw-semibold">
          <i class="bi bi-arrow-left me-1"></i>Back to event
        </a>
      </div>
    </div>
  </div>

  <div class="card yg-card">
    <div class="card-body p-4">
      <p class="text-secondary">Showing event audience members and assigned substitutes who do not have a check-in record.</p>
      @if ($users->isEmpty())
        <div class="alert alert-success mb-0">Everyone eligible for this event has checked in.</div>
      @else
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr><th>Name</th><th>Category</th><th>Unique ID</th></tr>
            </thead>
            <tbody>
              @foreach ($users as $user)
                <tr>
                  <td>{{ $user->full_name }}</td>
                  <td>{{ \App\Models\User::ROLE_LABELS[$user->role] ?? ucfirst($user->role) }}</td>
                  <td>{{ $user->unique_id }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="mt-3">{{ $users->links() }}</div>
      @endif
    </div>
  </div>
@endsection
