@extends('layouts.app')

@section('title', $role === 'official' ? 'Official Registration' : ($role === 'guest' ? 'Guest Registration' : 'Register'))

@section('content')

  {{-- Header --}}
  <div class="page-header p-4 p-lg-5 mb-4">
    <div class="row align-items-center position-relative" style="z-index: 1;">
      <div class="col">
        <h2 class="fw-bold mb-1"><i class="bi bi-person-plus-fill me-2"></i>{{ $role === 'official' ? 'Official Registration' : ($role === 'guest' ? 'Guest Registration' : 'Create your account') }}</h2>
        <p class="mb-0 opacity-75">For {{ $role === 'official' ? 'barangay officials and personnel' : ($role === 'guest' ? 'event guests' : 'residents') }} of Barangay San Jose, Iriga City</p>
      </div>
    </div>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger rounded-3" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-1"></i>
      <span class="fw-semibold">Please fix the following:</span>
      <ul class="mb-0 ps-3 mt-1">
        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
      </ul>
    </div>
  @endif

  @if ($role === 'official')
    <div class="alert alert-info rounded-3" role="status">
      <i class="bi bi-info-circle-fill me-1"></i>
      Official accounts must be verified by a Barangay Administrator before official features can be used.
    </div>
  @endif

  {{-- FIXED: was route('register'), which is the GET route --}}
  <form method="POST" action="{{ route('register.store') }}">
    @csrf
    <input type="hidden" name="role" value="{{ $role }}">

    @if ($role === 'official')
      <div class="card yg-card mb-4">
        <div class="card-body p-4">
          <h6 class="fw-bold text-uppercase text-secondary mb-3">Official position</h6>
          <div class="row g-3">
            <div class="col-md-6">
              <label for="official_group" class="form-label">Group</label>
              <select id="official_group" name="official_group" class="form-select" required>
                <option value="">Select a group</option>
                @foreach (config('talafair.official_positions') as $groupKey => $group)
                  <option value="{{ $groupKey }}" @selected(old('official_group') === $groupKey)>{{ $group['label'] }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label for="official_position" class="form-label">Position</label>
              <select id="official_position" name="official_position" class="form-select" required>
                <option value="">Select a position</option>
                @foreach (config('talafair.official_positions') as $groupKey => $group)
                  @foreach ($group['positions'] as $positionKey => $positionLabel)
                    <option value="{{ $positionKey }}" data-group="{{ $groupKey }}" @selected(old('official_position') === $positionKey)>{{ $positionLabel }}</option>
                  @endforeach
                @endforeach
              </select>
            </div>
          </div>
        </div>
      </div>
    @endif

    {{-- Full name --}}
    <div class="card yg-card mb-4">
      <div class="card-body p-4">
        <h6 class="fw-bold text-uppercase text-secondary mb-3">Full name</h6>
        <div class="row g-3">
          <div class="col-md-3">
            <label for="first_name" class="form-label">First name</label>
            <input id="first_name" name="first_name" type="text" required
                   value="{{ old('first_name') }}" class="form-control" data-title-case>
          </div>
          <div class="col-md-3">
                 <label for="middle_name" class="form-label">Middle name <span class="text-secondary fw-normal">(optional)</span></label>
            <input id="middle_name" name="middle_name" type="text"
                   value="{{ old('middle_name') }}" class="form-control" data-title-case>
          </div>
          <div class="col-md-3">
            <label for="last_name" class="form-label">Last name</label>
            <input id="last_name" name="last_name" type="text" required
                   value="{{ old('last_name') }}" class="form-control" data-title-case>
          </div>
          @if ($role !== 'guest')
            <div class="col-md-3">
              <label for="suffix" class="form-label">
                Suffix <span class="text-secondary fw-normal">(optional)</span>
              </label>
              <input id="suffix" name="suffix" type="text" placeholder="Jr., Sr., III"
                value="{{ old('suffix') }}" class="form-control" data-title-case>
            </div>
          @endif
        </div>
      </div>
    </div>

    {{-- Personal details --}}
    <div class="card yg-card mb-4">
      <div class="card-body p-4">
        <h6 class="fw-bold text-uppercase text-secondary mb-3">Personal details</h6>
        <div class="row g-3">

          @if ($role === 'guest')
            <div class="col-md-3">
              <label for="birthdate" class="form-label">Birthdate</label>
              <input id="birthdate" name="birthdate" type="date" required max="{{ now()->toDateString() }}" value="{{ old('birthdate') }}" class="form-control">
              <div class="form-text">Age is calculated from your birthdate.</div>
            </div>
            <div class="col-md-3">
              <label for="gender" class="form-label">Gender</label>
              <select id="gender" name="gender" class="form-select" required onchange="document.getElementById('guest_gender_other_wrap').hidden = this.value !== 'others'">
                <option value="">Select gender</option>
                @foreach (['female' => 'Female', 'male' => 'Male', 'others' => 'Others'] as $value => $label)
                  <option value="{{ $value }}" @selected(old('gender') === $value)>{{ $label }}</option>
                @endforeach
              </select>
              <div id="guest_gender_other_wrap" class="mt-2" @if(old('gender') !== 'others') hidden @endif>
                <label for="gender_other" class="form-label">Please specify</label>
                <input id="gender_other" name="gender_other" type="text" value="{{ old('gender_other') }}" class="form-control">
              </div>
            </div>
          @else
            <div class="col-md-6">
              <span class="form-label d-block">Sex at Birth</span>
              <div class="d-flex flex-wrap gap-3">
                @foreach (['female' => 'Female', 'male' => 'Male', 'prefer_not_to_say' => 'Prefer not to say'] as $value => $label)
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="sex_at_birth" id="sex_at_birth_{{ $value }}"
                           value="{{ $value }}" required @checked(old('sex_at_birth') === $value)>
                    <label class="form-check-label" for="sex_at_birth_{{ $value }}">{{ $label }}</label>
                  </div>
                @endforeach
              </div>
              <label for="preferred_gender_identity" class="form-label mt-3">Preferred Gender Identity</label>
              <select id="preferred_gender_identity" name="preferred_gender_identity" class="form-select" required onchange="document.getElementById('gender_identity_other_wrap').hidden = this.value !== 'self_describe'">
                @foreach (['woman' => 'Woman', 'man' => 'Man', 'non_binary' => 'Non-binary', 'transgender_woman' => 'Transgender Woman', 'transgender_man' => 'Transgender Man', 'genderqueer' => 'Genderqueer / Gender Non-conforming', 'self_describe' => 'Prefer to self-describe', 'prefer_not_to_say' => 'Prefer not to say'] as $value => $label)
                  <option value="{{ $value }}" @selected(old('preferred_gender_identity') === $value)>{{ $label }}</option>
                @endforeach
              </select>
              <div id="gender_identity_other_wrap" class="mt-3" @if(old('preferred_gender_identity') !== 'self_describe') hidden @endif>
                <label for="gender_identity_other" class="form-label">Please specify your gender identity</label>
                <input id="gender_identity_other" name="gender_identity_other" type="text" value="{{ old('gender_identity_other') }}" class="form-control">
              </div>
              <div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="is_lgbtqia" value="1" id="is_lgbtqia" @checked(old('is_lgbtqia'))><label class="form-check-label" for="is_lgbtqia">I identify as part of the LGBTQIA+ community</label></div>
            </div>
          @endif

          @if ($role !== 'guest')
          <div class="col-md-3">
            <label for="birthdate" class="form-label">Birthdate</label>
            <input id="birthdate" name="birthdate" type="date" required
                   max="{{ now()->toDateString() }}" value="{{ old('birthdate') }}" class="form-control">
          </div>

          <div class="col-md-3">
            <label for="age_display" class="form-label">Age</label>
            <input id="age_display" type="text" readonly placeholder="—"
                   class="form-control bg-body-secondary">
            <div class="form-text">Counted from your birthdate.</div>
          </div>

          <div class="col-md-6">
            <label for="contact_number" class="form-label">
              Contact number <span class="text-secondary fw-normal">(optional)</span>
            </label>
            <input id="contact_number" name="contact_number" type="tel" placeholder="09xx xxx xxxx"
                   value="{{ old('contact_number') }}" class="form-control">
          </div>

          <div class="col-12 col-md-4">
            <label for="occupation" class="form-label">Occupation <span class="text-secondary fw-normal">(optional)</span></label>
            <input id="occupation" name="occupation" type="text" value="{{ old('occupation') }}" class="form-control" data-title-case>
          </div>
          @include('pages.partials.student-fields')
          @endif
        </div>
      </div>
    </div>

    {{-- Address --}}
    <div class="card yg-card mb-4">
      <div class="card-body p-4">
        <h6 class="fw-bold text-uppercase text-secondary mb-1">Address</h6>
        <p class="small text-secondary">{{ $role === 'guest' ? 'Enter your current home address.' : 'Fill in the first three. The rest is fixed for our barangay.' }}</p>

        <div class="row g-3">
          <div class="col-md-4">
            <label for="house_no" class="form-label">House / building no.</label>
            <input id="house_no" name="house_no" type="text" required value="{{ old('house_no') }}" class="form-control">
          </div>
          <div class="col-md-4">
            <label for="street" class="form-label">Street</label>
            <input id="street" name="street" type="text" required value="{{ old('street') }}" class="form-control" data-title-case>
          </div>
          <div class="col-md-4">
            <label for="zone" class="form-label">Zone number</label>
            <input id="zone" name="zone" type="text" inputmode="numeric" required value="{{ old('zone') }}" class="form-control">
            @if ($role === 'guest')
              <div class="form-text">Used for your guest ID, which starts with GZ and is separate from resident IDs.</div>
            @else
              <div class="form-text">Your zone prefixes your resident ID (Zone 2 &rarr; Z2-…).</div>
            @endif
          </div>
          @if ($role === 'guest')
            <div class="col-md-4">
              <label for="barangay" class="form-label">Barangay / village</label>
              <input id="barangay" name="barangay" type="text" required value="{{ old('barangay') }}" class="form-control" data-title-case>
            </div>
          @endif
        </div>

        @if ($role === 'guest')
        <div class="row g-3 mt-1">
          <div class="col-md-4"><label for="city" class="form-label">City / Municipality</label><input id="city" name="city" type="text" required value="{{ old('city') }}" class="form-control" data-title-case></div>
          <div class="col-md-4"><label for="province" class="form-label">Province</label><input id="province" name="province" type="text" required value="{{ old('province') }}" class="form-control" data-title-case></div>
          <div class="col-md-4"><label for="country" class="form-label">Country</label><input id="country" name="country" type="text" required value="{{ old('country', 'Philippines') }}" class="form-control" data-title-case></div>
          <div class="col-md-4"><label for="postal_code" class="form-label">Postal code</label><input id="postal_code" name="postal_code" type="text" required value="{{ old('postal_code') }}" class="form-control"></div>
        </div>
        @else
        <div class="row g-3 mt-1 mx-0 p-3 bg-body-secondary rounded-3">
          @foreach ([
            'Barangay' => 'San Jose',
            'City / Municipality' => 'Iriga City',
            'Province' => 'Camarines Sur',
            'Country' => 'Philippines',
            'Postal code' => '4431',
          ] as $label => $value)
            <div class="col-6 col-md">
              <div class="small text-uppercase text-secondary">{{ $label }}</div>
              <div class="fw-semibold">{{ $value }}</div>
            </div>
          @endforeach
        </div>
        @endif
      </div>
    </div>

    @if ($role === 'resident')
    {{-- Household --}}
    <div class="card yg-card mb-4">
      <div class="card-body p-4">
        <h6 class="fw-bold text-uppercase text-secondary mb-3">Household</h6>

        {{-- FIXED: an unchecked checkbox submits nothing at all.
             This hidden field guarantees is_head_of_family always arrives as 0 or 1,
             which is what required_if:is_head_of_family,0 depends on. --}}
        <input type="hidden" name="is_head_of_family" value="0">

        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_head_of_family" value="1" id="is_head"
                 @checked(old('is_head_of_family'))
                 >
          <label class="form-check-label" for="is_head">
            I am the head of the family
            <span class="d-block small text-secondary">Family heads get their own notifications from the barangay.</span>
          </label>
        </div>

        <div id="head_wrap" class="mt-2" @if(old('is_head_of_family')) hidden @endif>
          @if ($role === 'resident')
            <div class="alert alert-warning py-2 px-3 mb-3 small">
              <div class="fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i>Household Head Required</div>
              <div>Register the Household Head first, then select the registered Household Head below to link this member.</div>
            </div>
            <label for="head_of_family_id" class="form-label">Household Head <span class="text-danger">*</span></label>
            @if ($heads->isEmpty())
              <div class="alert alert-danger py-2 px-3 mb-0 small">No registered Household Heads available. Please register the Household Head first before registering household members.</div>
            @else
              <input id="head_search" type="search" class="form-control form-control-sm mb-2" placeholder="Search registered Household Heads..." aria-label="Search registered Household Heads">
              <select id="head_of_family_id" name="head_of_family_id" class="form-select" required>
                <option value="">Search or select a registered Household Head</option>
                @foreach ($heads as $head)
                  <option value="{{ $head->id }}" @selected(old('head_of_family_id') == $head->id)>
                    {{ $head->full_name }} — {{ $head->house_no }} {{ $head->street }}{{ $head->zone ? ', Zone ' . $head->zone : '' }}
                  </option>
                @endforeach
              </select>
              <div class="form-text">Only registered Household Heads can be selected. Register the Household Head first before adding household members.</div>
            @endif
          @else
            <div class="row g-3">
              <div class="col-md-6">
                <label for="head_of_family_name" class="form-label">Who is the head of your family?</label>
                <input id="head_of_family_name" name="head_of_family_name" type="text"
                       value="{{ old('head_of_family_name') }}" class="form-control">
              </div>
              <div class="col-md-6">
                <label for="head_of_family_id" class="form-label">Link to a registered family head</label>
                <select id="head_of_family_id" name="head_of_family_id" class="form-select">
                  <option value="">Not listed</option>
                  @foreach ($heads as $head)
                    <option value="{{ $head->id }}" @selected(old('head_of_family_id') == $head->id)>{{ $head->full_name }} — {{ $head->house_no }} {{ $head->street }}, Zone {{ $head->zone }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>
    @endif

    {{-- Account --}}
    <div class="card yg-card mb-4">
      <div class="card-body p-4">
        <h6 class="fw-bold text-uppercase text-secondary mb-3">Account</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label for="username" class="form-label">Username</label>
            <input id="username" name="username" type="text" required minlength="3" maxlength="30"
                   value="{{ old('username') }}" class="form-control">
            <div class="form-text">Letters, numbers, dashes and underscores only.</div>
          </div>
          <div class="col-md-6">
            <label for="email" class="form-label">Email <span class="text-secondary fw-normal">(optional)</span></label>
            <input id="email" name="email" type="email"
                   value="{{ old('email') }}" class="form-control">
          </div>
          <div class="col-md-6">
            <label for="password" class="form-label">Password</label>
            <input id="password" name="password" type="password" required
                   autocomplete="new-password" class="form-control">
          </div>
          <div class="col-md-6">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   autocomplete="new-password" class="form-control">
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="save_account" value="1" id="saveAccount"
                     @checked(old('save_account'))>
              <label class="form-check-label small text-secondary" for="saveAccount">
                Remember this account on this device <span class="d-block">Saves your username and display profile, never your password.</span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <a href="{{ route('login') }}" class="link-secondary small">I already have an account</a>
      <button type="submit" class="btn btn-primary fw-semibold px-4">
        <i class="bi bi-person-badge me-1"></i>Register and generate my ID
      </button>
    </div>
  </form>
@endsection

@push('scripts')
<script>
(function () {
  const headCheckbox = document.getElementById('is_head');
  function toggleHeadFields(checkbox) {
    const headWrap = document.getElementById('head_wrap');
    if (!headWrap) return;
    headWrap.hidden = checkbox.checked;
    headWrap.querySelectorAll('input, select').forEach((field) => field.disabled = checkbox.checked);
  }
  if (headCheckbox) {
    headCheckbox.addEventListener('change', () => toggleHeadFields(headCheckbox));
    toggleHeadFields(headCheckbox);
  }

  const genderIdentity = document.getElementById('preferred_gender_identity');
  const genderIdentityOther = document.getElementById('gender_identity_other_wrap');
  if (genderIdentity && genderIdentityOther) genderIdentity.addEventListener('change', () => { genderIdentityOther.hidden = genderIdentity.value !== 'self_describe'; });

  const headSelect = document.getElementById('head_of_family_id');
  const headSearch = document.getElementById('head_search');
  const registrationForm = headSelect?.form;
  const requiresHouseholdHead = @json($role === 'resident');
  if (registrationForm && requiresHouseholdHead) {
    registrationForm.addEventListener('submit', (event) => {
      if (!headCheckbox?.checked && (!headSelect || !headSelect.value)) {
        event.preventDefault();
        headSelect?.focus();
      }
    });
  }

  if (headSearch && headSelect) {
    headSearch.addEventListener('input', () => {
      const term = headSearch.value.trim().toLowerCase();
      Array.from(headSelect.options).forEach((option, index) => {
        option.hidden = index > 0 && !option.textContent.toLowerCase().includes(term);
      });
    });
  }

  const officialGroup = document.getElementById('official_group');
  const officialPosition = document.getElementById('official_position');
  if (officialGroup && officialPosition) {
    function filterPositions() {
      Array.from(officialPosition.options).forEach(function (option) {
        option.hidden = option.value !== '' && option.dataset.group !== officialGroup.value;
      });
      if (officialPosition.selectedOptions[0]?.dataset.group !== officialGroup.value) officialPosition.value = '';
    }
    officialGroup.addEventListener('change', filterPositions);
    filterPositions();
  }

  const birthdate = document.getElementById('birthdate');
  const ageBox    = document.getElementById('age_display');

  if (!birthdate || !ageBox) return;

  function computeAge() {
    if (!birthdate.value) { ageBox.value = ''; return; }
    const dob = new Date(birthdate.value), now = new Date();
    let age = now.getFullYear() - dob.getFullYear();
    const m = now.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && now.getDate() < dob.getDate())) age--;
    ageBox.value = age >= 0 ? age + ' years old' : '';
  }

  birthdate.addEventListener('change', computeAge);
  birthdate.addEventListener('input', computeAge);
  computeAge();
})();
</script>
@endpush