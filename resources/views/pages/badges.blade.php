@extends('layouts.app')

@section('title', 'Badges')

@section('content')

  @php
    $conditionOptions = [
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
    $conditionsByCategory = [
      'attendance' => ['first_event', 'event_attendance', 'early_arrival', 'early_bird', 'attendance_count'],
      'participation' => ['participation_count'],
      'streaks' => ['streak'],
      'milestones' => ['attendance_count', 'points'],
      'games' => ['best_player_week', 'best_player_month', 'best_player_all_time'],
      'competition' => ['manual'],
      'special' => ['manual'],
    ];
  @endphp

  <div class="page-header p-3 p-lg-4 mb-3">
    <div class="row align-items-center position-relative" style="z-index: 1;">
      <div class="col">
        <h2 class="h4 fw-bold mb-1"><i class="bi bi-award-fill me-2"></i>Badges</h2>
        <p class="small mb-0 opacity-75">Create automatic achievements and official event awards.</p>
      </div>
      <div class="col-auto">
        <span class="badge bg-white text-yg rounded-pill px-2 py-1">
          {{ $badges->count() }} {{ Str::plural('badge', $badges->count()) }}
        </span>
      </div>
    </div>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger rounded-3" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $errors->first() }}
    </div>
  @endif

  <div class="card yg-card mb-4">
    <div class="card-body p-4">
      <form method="POST" action="{{ route('badges.store') }}" enctype="multipart/form-data" class="row g-3">
        @csrf
        <div class="col-md-6">
          <label class="form-label">Badge name</label>
          <input name="name" required value="{{ old('name') }}" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label badge-parameter-label">Condition parameter</label>
          <input type="number" name="points_required" min="0" required
                 value="{{ old('points_required', 0) }}" class="form-control">
        </div>
        <div class="col-md-4"><label class="form-label">Category</label><select name="category" class="form-select badge-category-select" required>@foreach(['attendance'=>'Attendance','participation'=>'Participation','streaks'=>'Streaks','milestones'=>'Milestones','competition'=>'Competition','games'=>'Games','special'=>'Special recognition'] as $value => $label)<option value="{{ $value }}" @selected(old('category', 'attendance') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Rarity</label><select name="rarity" class="form-select" required>@foreach(['common'=>'Common','uncommon'=>'Uncommon','rare'=>'Rare','epic'=>'Epic','legendary'=>'Legendary'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Award method</label><select name="award_method" id="awardMethod" class="form-select" required><option value="automatic">Automatic</option><option value="official">Official award</option></select></div>
        <div class="col-md-6"><label class="form-label">Condition</label><select name="condition_key" class="form-select badge-condition-select" required>@foreach($conditionsByCategory[old('category', 'attendance')] as $condition)<option value="{{ $condition }}" @selected(old('condition_key', $conditionsByCategory[old('category', 'attendance')][0]) === $condition)>{{ $conditionOptions[$condition] }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Limited total <span class="text-secondary fw-normal">(optional)</span></label><input type="number" name="limited_total" min="1" class="form-control" placeholder="20"></div>
        <div class="col-md-3"><label class="form-label">Event <span class="text-secondary fw-normal">(optional)</span></label><select name="announcement_id" class="form-select"><option value="">All events</option>@foreach($events as $event)<option value="{{ $event->id }}">{{ $event->title }}</option>@endforeach</select></div>
        <div class="col-12">
          <label class="form-label">Description</label>
          <input name="description" value="{{ old('description') }}" class="form-control">
        </div>
        <div class="col-12"><label class="form-label">Recipients <span class="text-secondary fw-normal">(for official awards)</span></label><select name="recipients[]" class="form-select" multiple size="4">@foreach($residents as $resident)<option value="{{ $resident->id }}">{{ $resident->name }}</option>@endforeach</select></div>
        <div class="col-12">
          <label class="form-label">Badge image</label>
          <input type="file" name="image" accept="image/*" class="form-control">
          <div class="form-text">Optional. Officials can upload badge artwork later.</div>
        </div>
        <div class="col-12">
          <button class="btn btn-primary fw-semibold px-4"><i class="bi bi-plus-lg me-1"></i>Add badge</button>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-3">
    @forelse ($badges as $badge)
      <div class="col-6 col-md-4 col-lg-3">
        <div class="card yg-card h-100 text-center">
          <div class="card-body">
            @if ($badge->image_path)<img src="{{ $badge->image_url }}" data-image-fallback="{{ asset('images/image-placeholder.svg') }}" alt="" style="height:5rem;" class="object-fit-contain">@else<div class="text-secondary" style="height:5rem; padding-top:1.5rem;"><i class="bi bi-image fs-2"></i></div>@endif
            <div class="fw-semibold mt-2">{{ $badge->name }}</div>
            <div class="small text-secondary">{{ $conditionOptions[$badge->condition_key] ?? $badge->condition_key }}@if ($badge->points_required) · {{ $badge->points_required }} @endif</div>
            <div class="small text-secondary text-capitalize">{{ $badge->category }} · {{ $badge->rarity }} · {{ $badge->award_method }}</div>
            @if ($badge->announcement)<div class="small text-secondary">{{ $badge->announcement->title }}</div>@endif
            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" data-bs-toggle="modal" data-bs-target="#editBadge{{ $badge->id }}">
              <i class="bi bi-pencil me-1"></i>Edit
            </button>
            <form method="POST" action="{{ route('badges.destroy', $badge) }}" class="mt-2"
                  onsubmit="return confirm('Remove this badge?')">
              @csrf @method('DELETE')
              <button class="btn btn-link btn-sm text-danger p-0">Remove</button>
            </form>
          </div>
        </div>
      </div>
      <div class="modal fade" id="editBadge{{ $badge->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content border-0" style="border-radius:20px;">
            <form method="POST" action="{{ route('badges.update', $badge) }}" enctype="multipart/form-data">
              @csrf @method('PUT')
              <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Edit badge</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label class="form-label">Badge name</label>
                  <input name="name" value="{{ $badge->name }}" required class="form-control">
                </div>
                <div class="mb-3">
                  <label class="form-label badge-parameter-label">Condition parameter</label>
                  <input type="number" name="points_required" value="{{ $badge->points_required }}" min="0" required class="form-control">
                </div>
                <div class="row g-3 mb-3">
                  <div class="col-6"><label class="form-label">Category</label><select name="category" class="form-select badge-category-select" required>@foreach(['attendance'=>'Attendance','participation'=>'Participation','streaks'=>'Streaks','milestones'=>'Milestones','competition'=>'Competition','games'=>'Games','special'=>'Special recognition'] as $value => $label)<option value="{{ $value }}" @selected($badge->category === $value)>{{ $label }}</option>@endforeach</select></div>
                  <div class="col-6"><label class="form-label">Rarity</label><select name="rarity" class="form-select" required>@foreach(['common'=>'Common','uncommon'=>'Uncommon','rare'=>'Rare','epic'=>'Epic','legendary'=>'Legendary'] as $value => $label)<option value="{{ $value }}" @selected($badge->rarity === $value)>{{ $label }}</option>@endforeach</select></div>
                  <div class="col-6"><label class="form-label">Award method</label><select name="award_method" class="form-select" required><option value="automatic" @selected($badge->award_method === 'automatic')>Automatic</option><option value="official" @selected($badge->award_method === 'official')>Official award</option></select></div>
                  <div class="col-6"><label class="form-label">Condition</label><select name="condition_key" class="form-select badge-condition-select" required>@foreach($conditionsByCategory[$badge->category] ?? [] as $condition)<option value="{{ $condition }}" @selected($badge->condition_key === $condition)>{{ $conditionOptions[$condition] }}</option>@endforeach</select></div>
                  <div class="col-6"><label class="form-label">Limited total</label><input type="number" name="limited_total" min="1" value="{{ $badge->limited_total }}" class="form-control"></div>
                  <div class="col-6"><label class="form-label">Event</label><select name="announcement_id" class="form-select"><option value="">All events</option>@foreach($events as $event)<option value="{{ $event->id }}" @selected($badge->announcement_id === $event->id)>{{ $event->title }}</option>@endforeach</select></div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Description</label>
                  <input name="description" value="{{ $badge->description }}" class="form-control">
                </div>
                <div>
                  <label class="form-label">Replace image <span class="text-secondary fw-normal">(optional)</span></label>
                  <input type="file" name="image" accept="image/*" class="form-control">
                </div>
              </div>
              <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Save changes</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12">
        <div class="card yg-card">
          <div class="card-body text-center text-secondary py-5">
            <i class="bi bi-award fs-2 d-block mb-2"></i>
            No badges yet. Upload the first one above.
          </div>
        </div>
      </div>
    @endforelse
  </div>
@endsection

@push('scripts')
  <script>
    const conditionsByCategory = @json($conditionsByCategory);
    const conditionOptions = @json($conditionOptions);
    const conditionParameterLabels = {
      points: 'Points required',
      attendance_count: 'Events required',
      participation_count: 'Participations required',
      streak: 'Events in streak required',
      early_bird: 'Limited attendees',
      best_player_week: 'Condition parameter (leave 0)',
      best_player_month: 'Condition parameter (leave 0)',
      best_player_all_time: 'Condition parameter (leave 0)',
    };

    document.querySelectorAll('.badge-category-select').forEach((categorySelect) => {
      const form = categorySelect.closest('form');
      const conditionSelect = form.querySelector('.badge-condition-select');
      const parameterLabel = form.querySelector('.badge-parameter-label');
      const parameterInput = form.querySelector('[name="points_required"]');
      const initialCondition = conditionSelect.value;

      const updateConditions = () => {
        const conditions = conditionsByCategory[categorySelect.value] || [];
        const selectedCondition = conditions.includes(conditionSelect.value) ? conditionSelect.value : (conditions.includes(initialCondition) ? initialCondition : conditions[0]);
        conditionSelect.replaceChildren(...conditions.map((condition) => new Option(conditionOptions[condition], condition)));
        conditionSelect.value = selectedCondition;
        if (parameterLabel) parameterLabel.textContent = conditionParameterLabels[conditionSelect.value] || 'Condition parameter';
      };

      categorySelect.addEventListener('change', updateConditions);
      conditionSelect.addEventListener('change', updateConditions);
      updateConditions();
    });
  </script>
@endpush

@push('scripts')
  <script>
    const conditionsByCategory = @json($conditionsByCategory);
    const conditionOptions = @json($conditionOptions);

    document.querySelectorAll('.badge-category-select').forEach((categorySelect) => {
      const form = categorySelect.closest('form');
      const conditionSelect = form.querySelector('select[name="condition_key"]');
      const selectedCondition = conditionSelect.value;

      const updateConditions = () => {
        const conditions = conditionsByCategory[categorySelect.value] || [];
        conditionSelect.replaceChildren(...conditions.map((condition) => {
          const option = new Option(conditionOptions[condition], condition);
          option.selected = condition === selectedCondition && conditions.includes(selectedCondition);
          return option;
        }));
        if (!conditionSelect.value && conditions.length) conditionSelect.value = conditions[0];
      };

      categorySelect.addEventListener('change', updateConditions);
      updateConditions();
    });
  </script>
@endpush
