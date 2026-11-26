@extends('layouts.app')

@section('title', 'Event QR')

@section('content')
<div class="event-qr-page">
  <header class="event-qr-print-header">
    @include('partials.logo')
    <span class="talafair-wordmark">TalaFair<span class="text-yg">.</span></span>
  </header>
  <div class="row justify-content-center text-center">
  <div class="col-md-6 col-lg-5">
    <h4 class="fw-bold mb-1">{{ $announcement->title }}</h4>
    <p class="text-secondary">
      {{ $announcement->event_start_at?->format('M j, Y g:i A') }} &middot;
      {{ $announcement->venue_name ?: 'Barangay San Jose' }}
    </p>

    <div class="card yg-card d-inline-block">
      <div class="card-body p-4">
        {!! $svg !!}
        <div class="font-monospace small text-secondary mt-3">{{ $announcement->qr_token }}</div>
      </div>
    </div>

    <ul class="list-group list-group-flush text-start mt-4 mx-auto" style="max-width:20rem;">
      <li class="list-group-item d-flex justify-content-between">
        <span class="text-secondary">Scanning opens</span>
        <span class="fw-semibold">{{ $announcement->scanOpensAt()?->format('M j, g:i A') }}</span>
      </li>
      <li class="list-group-item d-flex justify-content-between">
        <span class="text-secondary">QR expires</span>
        <span class="fw-semibold">{{ $announcement->qr_expires_at?->format('M j, g:i A') }}</span>
      </li>
      <li class="list-group-item d-flex justify-content-between">
        <span class="text-secondary">Allowed radius</span>
        <span class="fw-semibold">{{ $announcement->geofence_radius }} m</span>
      </li>
      <li class="list-group-item d-flex justify-content-between">
        <span class="text-secondary">Base points</span>
        <span class="fw-semibold">{{ $announcement->base_points }} (+10% until 15 min before start)</span>
      </li>
    </ul>

    <button onclick="window.print()" class="btn btn-dark fw-semibold mt-4 px-4 event-qr-print-button">
      <i class="bi bi-printer me-1"></i>Print QR
    </button>
  </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .event-qr-print-header { display: none; }

  @media print {
    @page { margin: 1.5cm; }

    .yg-navbar,
    .yg-footer,
    .event-qr-print-button { display: none !important; }

    main { padding: 0 !important; }
    main > .container { max-width: none; padding: 0; }

    .event-qr-print-header {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .6rem;
      margin-bottom: 2rem;
      color: #343a40;
    }

    .event-qr-print-header .talafair-logo {
      width: 1.5rem;
      height: 1.5rem;
      object-fit: contain;
    }

    .event-qr-print-header .talafair-wordmark {
      font-size: 1.75rem;
      font-weight: 700;
    }

    .event-qr-page .row { margin: 0; }
    .event-qr-page .col-md-6 { width: 100%; max-width: none; }
    .event-qr-page .card { box-shadow: none !important; }
  }
</style>
@endpush

