@extends('layouts.app')

@section('title', 'Badges')

@section('content')
  <div class="page-header p-3 p-lg-4 mb-3">
    <div class="row align-items-center position-relative" style="z-index: 1;">
      <div class="col">
        <h2 class="h4 fw-bold mb-1"><i class="bi bi-award-fill me-2"></i>Badges to earn</h2>
        <p class="small mb-0 opacity-75">Collect achievements from verified participation and community moments.</p>
      </div>
      <div class="col-auto">
        <span class="badge bg-white text-yg rounded-pill px-2 py-1">{{ $earnedBadges->count() }} / {{ $badges->count() }} earned</span>
      </div>
    </div>
  </div>

  @php
    $conditionLabels = [
      'points' => 'Points milestone',
      'first_event' => 'First event',
      'event_attendance' => 'Event attendee',
      'early_arrival' => 'Early arrival',
      'early_bird' => 'First limited attendees',
      'participation_count' => 'Participation count',
      'streak' => 'Event streak',
      'attendance_count' => 'Attendance count',
      'best_player_week' => 'Best game player this week',
      'best_player_month' => 'Best game player this month',
      'best_player_all_time' => 'Best game player all time',
      'manual' => 'Official selection',
    ];
    $badgeLevels = [
      1 => ['label' => 'Level 1', 'min' => 0, 'max' => 99],
      2 => ['label' => 'Level 2', 'min' => 100, 'max' => 249],
      3 => ['label' => 'Level 3', 'min' => 250, 'max' => 499],
      4 => ['label' => 'Level 4', 'min' => 500, 'max' => 999],
      5 => ['label' => 'Level 5+', 'min' => 1000, 'max' => null],
    ];
  @endphp

  <div class="card yg-card p-3 mb-4">
    <div class="row g-3 align-items-end">
      <div class="col-md-4">
        <label for="badgeCategoryFilter" class="form-label fw-semibold">Category</label>
        <select id="badgeCategoryFilter" class="form-select">
          <option value="">All categories</option>
          @foreach ($badges->pluck('category')->unique()->sort() as $category)
            <option value="{{ $category }}">{{ ucfirst($category) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <label for="badgeLevelFilter" class="form-label fw-semibold">Minimum level</label>
        <select id="badgeLevelFilter" class="form-select">
          <option value="">Any level</option>
          @foreach ($badgeLevels as $level => $range)
            <option value="{{ $level }}">{{ $range['label'] }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <button type="button" id="clearBadgeFilters" class="btn btn-outline-secondary w-100">Clear filters</button>
      </div>
    </div>
  </div>

  <div class="row g-3">
    @forelse ($badges as $badge)
      @php($earnedBadge = $earnedBadges->get($badge->id))
      @php($earned = $earnedBadge !== null)
      @php($badgeLevel = collect($badgeLevels)->filter(fn ($range) => $badge->points_required >= $range['min'] && ($range['max'] === null || $badge->points_required <= $range['max']))->keys()->last() ?? 1)
      <div class="col-6 col-md-4 col-lg-3 badge-catalog-item" data-category="{{ $badge->category }}" data-level="{{ $badgeLevel }}">
        <div class="card yg-card h-100 text-center {{ $earned ? '' : 'opacity-50' }}">
          <div class="card-body p-3">
            @if ($badge->image_path)<img src="{{ $badge->image_url }}" data-image-fallback="{{ asset('images/image-placeholder.svg') }}" alt="{{ $badge->name }}" style="height:6rem;" class="object-fit-contain {{ $earned ? '' : 'grayscale' }}">@else<div class="text-secondary" style="height:6rem; padding-top:2rem;"><i class="bi bi-image fs-1"></i></div>@endif
            <div class="fw-semibold mt-2">{{ $badge->name }}</div>
            <div class="small text-secondary">{{ $conditionLabels[$badge->condition_key] ?? $badge->condition_key }}@if ($badge->points_required) · {{ $badge->points_required }} @endif</div>
            <div class="mt-2"><span class="badge badge-soft text-capitalize">{{ $badge->category }}</span> <span class="badge rarity-{{ $badge->rarity }} text-capitalize">{{ $badge->rarity }}</span></div>
            @if ($badge->description)<div class="small text-secondary mt-1">{{ $badge->description }}</div>@endif
            @if ($badge->announcement)<div class="small text-secondary mt-1"><i class="bi bi-calendar-event me-1"></i>{{ $badge->announcement->title }}</div>@endif
            <span class="badge {{ $earned ? 'badge-yg' : 'bg-secondary-subtle text-secondary' }} rounded-pill mt-2">
              <i class="bi {{ $earned ? 'bi-check-circle' : 'bi-lock' }} me-1"></i>{{ $earned ? 'Acquired' : 'Not acquired' }}
            </span>
            @if ($earned)<div class="small text-secondary mt-2">Earned {{ $earnedBadge->pivot->created_at?->format('M j, Y') }}@if ($earnedBadge->pivot->award_rank) · #{{ $earnedBadge->pivot->award_rank }}/{{ $badge->limited_total ?: 20 }}@endif</div>@endif
            <button type="button" class="btn btn-link btn-sm text-yg text-decoration-none mt-1" data-bs-toggle="modal" data-bs-target="#badgeDetails{{ $badge->id }}">View details</button>
          </div>
        </div>
      </div>
      <div class="modal fade" id="badgeDetails{{ $badge->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content border-0" style="border-radius:20px;">
            <div class="modal-header border-0">
              <h5 class="modal-title fw-bold">{{ $badge->name }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
              @if ($badge->image_path)<img src="{{ $badge->image_url }}" data-image-fallback="{{ asset('images/image-placeholder.svg') }}" alt="{{ $badge->name }}" style="height:8rem;" class="object-fit-contain mb-3">@else<div class="text-secondary mb-3" style="height:8rem; padding-top:2.5rem;"><i class="bi bi-image fs-1"></i></div>@endif
              <p class="text-secondary">{{ $badge->description ?: 'A TalaFair community achievement.' }}</p>
              <p class="small text-secondary mb-3"><strong>Condition:</strong> {{ $conditionLabels[$badge->condition_key] ?? $badge->condition_key }}@if ($badge->points_required) · {{ $badge->points_required }} @endif</p>
              <div class="d-flex flex-wrap justify-content-center gap-2">
                <span class="badge badge-soft text-capitalize">{{ $badge->category }}</span>
                <span class="badge rarity-{{ $badge->rarity }} text-capitalize">{{ $badge->rarity }}</span>
                <span class="badge bg-light text-secondary">{{ $badge->award_method === 'automatic' ? 'System awarded' : 'Official awarded' }}</span>
              </div>
              @if ($badge->announcement)
                <div class="small text-secondary mt-3">Event: {{ $badge->announcement->title }}</div>
              @endif
              @if ($earned)
                <div class="small text-secondary mt-1">Earned: {{ $earnedBadge->pivot->created_at?->format('F j, Y') }}</div>
                @if ($earnedBadge->pivot->award_rank)<div class="small text-secondary mt-1">Limited award position: #{{ $earnedBadge->pivot->award_rank }}/{{ $badge->limited_total ?: 20 }}</div>@endif
              @endif
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12"><div class="card yg-card"><div class="card-body text-center text-secondary py-5">No badges are available yet.</div></div></div>
    @endforelse
  </div>
@endsection

@push('scripts')
<script>
  (() => {
    const category = document.getElementById('badgeCategoryFilter');
    const level = document.getElementById('badgeLevelFilter');
    const clear = document.getElementById('clearBadgeFilters');
    const items = document.querySelectorAll('.badge-catalog-item');

    function filterBadges() {
      items.forEach(item => {
        const matchesCategory = !category.value || item.dataset.category === category.value;
        const matchesLevel = !level.value || Number(item.dataset.level) >= Number(level.value);
        item.classList.toggle('d-none', !(matchesCategory && matchesLevel));
      });
    }

    category.addEventListener('change', filterBadges);
    level.addEventListener('change', filterBadges);
    clear.addEventListener('click', () => {
      category.value = '';
      level.value = '';
      filterBadges();
    });
  })();
</script>
@endpush

@push('styles')
<style>
  .rarity-common { background: #e9ecef; color: #495057; }
  .rarity-uncommon { background: #d8f3dc; color: #27743a; }
  .rarity-rare { background: #d7efff; color: #1769aa; }
  .rarity-epic { background: #f1ddff; color: #713f96; }
  .rarity-legendary { background: #ffe8a1; color: #805b00; }
</style>
@endpush