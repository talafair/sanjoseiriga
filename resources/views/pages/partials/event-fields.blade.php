@php
    $a         = $announcement ?? null;
    $sfx       = $suffix ?? 'x';
    $audiences = \App\Models\Announcement::AUDIENCES;
@endphp

  @php
    $assignedFacilitatorIds = old('facilitator_ids', $a?->facilitatorUsers?->pluck('id')->all() ?? []);
  @endphp
  <div class="mt-4 mb-4">
      <label class="form-label fw-semibold">Event Facilitators / Assigned Personnel <span class="text-secondary fw-normal">(optional)</span></label>
      <div class="form-text mb-2">You may leave this empty. Assign verified officials or authorized personnel to this announcement.</div>
      <div class="row g-2 align-items-center" data-facilitator-picker="{{ $sfx }}">
        <div class="col-md-5">
          <label class="form-label small text-secondary mb-1" for="facilitator-available{{ $sfx }}">Available users</label>
          <select id="facilitator-available{{ $sfx }}" class="form-select" multiple size="5">
            @foreach (($facilitators ?? collect()) as $facilitator)
              @unless (in_array($facilitator->id, $assignedFacilitatorIds))
                <option value="{{ $facilitator->id }}">
                  {{ $facilitator->full_name }}{{ $facilitator->official_position ? ' - ' . ucfirst(str_replace('_', ' ', $facilitator->official_position)) : '' }}
                </option>
              @endunless
            @endforeach
          </select>
          <button type="button" class="btn btn-primary btn-sm mt-2 js-add-facilitator" title="Add selected facilitators"><i class="bi bi-plus-lg me-1"></i>Add to Facilitate</button>
        </div>
        <div class="col-md-5">
          <label class="form-label small text-secondary mb-1" for="facilitator-assigned{{ $sfx }}">Assigned facilitators</label>
          <select id="facilitator-assigned{{ $sfx }}" name="facilitator_ids[]" class="form-select" multiple size="5">
            @foreach (($facilitators ?? collect()) as $facilitator)
              @if (in_array($facilitator->id, $assignedFacilitatorIds))
                <option value="{{ $facilitator->id }}" selected>
                  {{ $facilitator->full_name }}{{ $facilitator->official_position ? ' - ' . ucfirst(str_replace('_', ' ', $facilitator->official_position)) : '' }}
                </option>
              @endif
            @endforeach
          </select>
          <button type="button" class="btn btn-danger btn-sm mt-2 js-remove-facilitator" title="Remove selected facilitators"><i class="bi bi-dash-lg me-1"></i>Remove as Facilitator</button>
        </div>
      </div>
      @if (($facilitators ?? collect())->isEmpty())
        <div class="small text-secondary mt-2">No verified officials or personnel are available to assign yet.</div>
      @endif
  </div>

  <div class="border-top pt-4 mt-4">
  <div class="form-check">
    <input class="form-check-input" type="checkbox" name="is_event" value="1"
           id="is_event{{ $sfx }}"
           @checked(old('is_event', $a?->is_event))
           onchange="document.getElementById('event_fields{{ $sfx }}').hidden = !this.checked">
    <label class="form-check-label fw-bold" for="is_event{{ $sfx }}">
      <i class="bi bi-calendar-event me-1 text-yg"></i>This is an event or meeting with attendance
    </label>
  </div>
  
  <div id="event_fields{{ $sfx }}" class="mt-4" @if(! old('is_event', $a?->is_event)) hidden @endif>
    <div id="activity_notice{{ $sfx }}" class="alert alert-info d-none">
      <i class="bi bi-info-circle me-1"></i>
      This activity is managed by officials. Residents do not scan for this announcement.
    </div>

    <div class="form-check border rounded-3 px-3 py-2 ps-5 mb-4">
      <input class="form-check-input" type="checkbox" name="allow_guest_scanning" value="1"
             id="allow_guest_scanning{{ $sfx }}" @checked(old('allow_guest_scanning', $a?->allow_guest_scanning))>
      <label class="form-check-label" for="allow_guest_scanning{{ $sfx }}">
        <span class="fw-semibold">Allow guests to scan this event QR</span>
        <span class="d-block small text-secondary">Guests can attend only when this is enabled.</span>
      </label>
    </div>

    <div class="form-check border rounded-3 px-3 py-2 ps-5 mb-4">
      <input class="form-check-input" type="checkbox" name="raffle_enabled" value="1"
             id="raffle_enabled{{ $sfx }}" @checked(old('raffle_enabled', $a?->raffle_enabled))>
      <label class="form-check-label" for="raffle_enabled{{ $sfx }}">
        <span class="fw-semibold">Enable event raffle</span>
        <span class="d-block small text-secondary">Every attendee is entered automatically. Early check-ins receive 10% more raffle weight.</span>
      </label>
    </div>

    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label" for="event_start_at{{ $sfx }}">Event starts</label>
        <input type="datetime-local" name="event_start_at" id="event_start_at{{ $sfx }}" class="form-control"
               value="{{ old('event_start_at', $a?->event_start_at?->format('Y-m-d\TH:i')) }}">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="event_end_at{{ $sfx }}">Event ends</label>
        <input type="datetime-local" name="event_end_at" id="event_end_at{{ $sfx }}" class="form-control"
               value="{{ old('event_end_at', $a?->event_end_at?->format('Y-m-d\TH:i')) }}">
        <div class="form-text">The QR expires at this time.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="rsvp_due_at{{ $sfx }}">Confirmation due</label>
        <input type="datetime-local" name="rsvp_due_at" id="rsvp_due_at{{ $sfx }}" class="form-control"
               value="{{ old('rsvp_due_at', $a?->rsvp_due_at?->format('Y-m-d\TH:i')) }}">
        <div class="form-text">You can extend this later.</div>
      </div>
    </div>

    <hr class="my-4">

    <label class="form-label fw-semibold">Audience to notify</label>
    <div class="form-text mb-2">Matched automatically from each resident's age and household role.</div>
    <div class="row g-2">
      @foreach ($audiences as $key => $label)
        <div class="col-md-6">
          <div class="form-check border rounded-3 px-3 py-2 ps-5">
            <input class="form-check-input" type="checkbox" name="audiences[]" value="{{ $key }}"
                   id="aud_{{ $key }}_{{ $sfx }}"
                   @if ($key === 'public') data-audience-all="{{ $sfx }}" @else data-audience-group="{{ $sfx }}" @endif
                   @checked(in_array($key, old('audiences', $a?->audiences ?? []), true))>
            <label class="form-check-label" for="aud_{{ $key }}_{{ $sfx }}">{{ $label }}</label>
          </div>
        </div>
      @endforeach
    </div>

    <hr class="my-4">

    <div class="row g-3">
      <div class="col-md-6" id="base_points_wrap{{ $sfx }}">
        <label class="form-label" for="base_points{{ $sfx }}">Base Points</label>
        <input type="number" name="base_points" id="base_points{{ $sfx }}" min="0" class="form-control"
               value="{{ old('base_points', $a?->base_points ?? 50) }}">
        <div class="form-text">Points awarded for successfully attending the event. Early scanners receive an additional 10%.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="confirmation_points{{ $sfx }}">Additional points for confirming attendance</label>
        <input type="number" name="confirmation_points" id="confirmation_points{{ $sfx }}" min="0" max="100000" class="form-control"
               value="{{ old('confirmation_points', $a?->confirmation_points ?? 0) }}">
        <div class="form-text">Awarded once, only after the resident confirms attendance and successfully checks in.</div>
      </div>
      <div class="col-md-6 d-none" id="participation_points_wrap{{ $sfx }}">
        <label class="form-label" for="participation_points{{ $sfx }}">Participation weight</label>
        <input type="number" name="participation_points" id="participation_points{{ $sfx }}" min="0" class="form-control"
               value="{{ old('participation_points', $a?->participation_points ?? 10) }}">
        <div class="form-text">Points awarded once per resident when an official scans their ID after participating.</div>
      </div>
    </div>

    <hr class="my-4">

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label" for="venue_name{{ $sfx }}">Venue</label>
        <input name="venue_name" id="venue_name{{ $sfx }}" placeholder="Barangay Hall" class="form-control"
               value="{{ old('venue_name', $a?->venue_name) }}">
      </div>
      <div class="col-md-3">
        <label class="form-label" for="venue_lat{{ $sfx }}">Latitude</label>
        <input name="venue_lat" id="venue_lat{{ $sfx }}" class="form-control"
               value="{{ old('venue_lat', $a?->venue_lat ?? config('talafair.barangay_lat')) }}">
      </div>
      <div class="col-md-3">
        <label class="form-label" for="venue_lng{{ $sfx }}">Longitude</label>
        <input name="venue_lng" id="venue_lng{{ $sfx }}" class="form-control"
               value="{{ old('venue_lng', $a?->venue_lng ?? config('talafair.barangay_lng')) }}">
      </div>
      <div class="col-md-6">
        <label class="form-label" for="geofence_radius{{ $sfx }}">Allowed radius (metres)</label>
        <input type="number" name="geofence_radius" id="geofence_radius{{ $sfx }}" min="20" max="5000"
               class="form-control"
               value="{{ old('geofence_radius', $a?->geofence_radius ?? 300) }}">
      </div>
      <div class="col-md-6 d-flex align-items-end">
        <button type="button" class="btn btn-outline-secondary js-use-my-location"
                data-lat="venue_lat{{ $sfx }}" data-lng="venue_lng{{ $sfx }}">
          <i class="bi bi-geo-alt me-1"></i>Use my current location
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const category = document.getElementById('annCategory{{ $sfx }}');
  const baseWrap = document.getElementById('base_points_wrap{{ $sfx }}');
  const activityWrap = document.getElementById('participation_points_wrap{{ $sfx }}');
  const baseInput = document.getElementById('base_points{{ $sfx }}');
  const activityInput = document.getElementById('participation_points{{ $sfx }}');
  const eventToggle = document.getElementById('is_event{{ $sfx }}');
  const eventFields = document.getElementById('event_fields{{ $sfx }}');
  const audienceAll = eventFields.querySelector('[data-audience-all="{{ $sfx }}"]');
  const audienceGroups = Array.from(eventFields.querySelectorAll('[data-audience-group="{{ $sfx }}"]'));
  const activities = ['ice_breaker', 'q_and_a', 'game', 'intermission'];

  function syncAudienceSelection() {
    if (audienceAll.checked) {
      audienceGroups.forEach(checkbox => checkbox.checked = true);
    } else if (audienceGroups.some(checkbox => !checkbox.checked)) {
      audienceAll.checked = false;
    } else {
      audienceAll.checked = true;
    }
  }

  audienceAll.addEventListener('change', () => {
    audienceGroups.forEach(checkbox => checkbox.checked = audienceAll.checked);
  });
  audienceGroups.forEach(checkbox => checkbox.addEventListener('change', () => {
    if (!checkbox.checked) audienceAll.checked = false;
    else if (audienceGroups.every(group => group.checked)) audienceAll.checked = true;
  }));
  syncAudienceSelection();

  function togglePoints() {
    const isActivity = activities.includes(category.value);
    const isEvent = eventToggle.checked;
    eventFields.hidden = !isEvent;
    baseWrap.classList.toggle('d-none', isActivity);
    activityWrap.classList.toggle('d-none', !isActivity);
    document.getElementById('activity_notice{{ $sfx }}').classList.toggle('d-none', !isActivity);
    baseInput.required = isEvent && !isActivity;
    activityInput.required = isEvent && isActivity;
  }

  category.addEventListener('change', togglePoints);
  eventToggle.addEventListener('change', togglePoints);
  togglePoints();

  const picker = document.querySelector('[data-facilitator-picker="{{ $sfx }}"]');
  const available = document.getElementById('facilitator-available{{ $sfx }}');
  const assigned = document.getElementById('facilitator-assigned{{ $sfx }}');

  function moveSelected(from, to) {
    Array.from(from.selectedOptions).forEach(option => {
      option.selected = false;
      to.appendChild(option);
    });
    Array.from(to.options).forEach(option => option.selected = true);
  }

  picker?.querySelector('.js-add-facilitator')?.addEventListener('click', () => moveSelected(available, assigned));
  picker?.querySelector('.js-remove-facilitator')?.addEventListener('click', () => moveSelected(assigned, available));
})();
</script>