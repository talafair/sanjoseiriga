@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
  <div class="page-header p-4 p-lg-5 mb-4">
    <div class="row align-items-center position-relative" style="z-index: 1;">
      <div class="col">
        <h2 class="fw-bold mb-1"><i class="bi bi-person-gear me-2"></i>Edit User</h2>
        <p class="mb-0 opacity-75">Update {{ $user->full_name }}'s account information</p>
      </div>
      <div class="col-auto"><a href="{{ route('users.index') }}" class="btn btn-light fw-semibold"><i class="bi bi-arrow-left me-1"></i>Back to Manage Users</a></div>
    </div>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger rounded-3" role="alert"><i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('users.update', $user) }}">
    @csrf @method('PUT')

    <div class="card yg-card mb-4">
      <div class="card-body p-4">
        <h6 class="fw-bold text-uppercase text-secondary mb-3">Profile picture</h6>
        <div class="d-flex align-items-center gap-3">
          <img src="{{ $user->avatar_url }}" alt="{{ $user->full_name }}" width="76" height="76" class="rounded-circle object-fit-cover border">
          <div class="small text-secondary">Profile picture management remains available from the user's Account page.</div>
        </div>
      </div>
    </div>

    <div class="card yg-card mb-4">
      <div class="card-body p-4">
        <h6 class="fw-bold text-uppercase text-secondary mb-3">Category and status</h6>
        <div class="row g-3">
          <div class="col-md-4"><label for="role" class="form-label">Category</label><select name="role" id="role" class="form-select" required>@foreach (\App\Models\User::CATEGORIES as $value => $label)@if ($value !== 'official' || auth()->user()->isSuperadmin() || $user->role === 'official')<option value="{{ $value }}" @selected(old('role', $user->role ?? 'resident') === $value)>{{ $label }}</option>@endif @endforeach</select><div class="form-text">Only the Superadmin can assign or change Official status.</div></div>
          <div class="col-md-6 official-fields"><label for="official_group" class="form-label">Official group</label><select name="official_group" id="official_group" class="form-select"><option value="">Select a group</option>@foreach (config('talafair.official_positions') as $groupKey => $group)<option value="{{ $groupKey }}" @selected($user->official_group === $groupKey)>{{ $group['label'] }}</option>@endforeach</select></div>
          <div class="col-md-6 official-fields"><label for="official_position" class="form-label">Official position</label><select name="official_position" id="official_position" class="form-select"><option value="">Select a position</option>@foreach (config('talafair.official_positions') as $groupKey => $group) @foreach ($group['positions'] as $positionKey => $positionLabel)<option value="{{ $positionKey }}" data-group="{{ $groupKey }}" @selected($user->official_position === $positionKey)>{{ $positionLabel }}</option>@endforeach @endforeach</select></div>
        </div>
      </div>
    </div>

    <div class="card yg-card mb-4">
      <div class="card-body p-4">
        <h6 class="fw-bold text-uppercase text-secondary mb-3">Full name</h6>
        <div class="row g-3">
          <div class="col-md-3"><label for="first_name" class="form-label">First name</label><input id="first_name" name="first_name" value="{{ $user->first_name }}" class="form-control" data-title-case required></div>
          <div class="col-md-3"><label for="middle_name" class="form-label">Middle name <span class="text-secondary fw-normal">(optional)</span></label><input id="middle_name" name="middle_name" value="{{ $user->middle_name }}" class="form-control" data-title-case></div>
          <div class="col-md-3"><label for="last_name" class="form-label">Last name</label><input id="last_name" name="last_name" value="{{ $user->last_name }}" class="form-control" data-title-case required></div>
          <div class="col-md-3"><label for="suffix" class="form-label">Suffix <span class="text-secondary fw-normal">(optional)</span></label><input id="suffix" name="suffix" value="{{ $user->suffix }}" class="form-control" data-title-case></div>
        </div>
      </div>
    </div>

    <div class="card yg-card mb-4">
      <div class="card-body p-4">
        <h6 class="fw-bold text-uppercase text-secondary mb-3">Personal details</h6>
        <div class="row g-3">
          <div class="col-md-6"><span class="form-label d-block">Sex at Birth</span><div class="d-flex flex-wrap gap-3">@foreach (['female' => 'Female', 'male' => 'Male', 'prefer_not_to_say' => 'Prefer not to say'] as $value => $label)<div class="form-check"><input class="form-check-input" type="radio" name="sex_at_birth" id="sex_at_birth_{{ $value }}" value="{{ $value }}" required @checked(old('sex_at_birth', $user->sex_at_birth ?: ($user->gender === 'others' ? 'prefer_not_to_say' : $user->gender)) === $value)><label class="form-check-label" for="sex_at_birth_{{ $value }}">{{ $label }}</label></div>@endforeach</div><label class="form-label mt-2">Preferred Gender Identity</label><select name="preferred_gender_identity" id="preferred_gender_identity" class="form-select" required onchange="document.getElementById('gender_identity_other_wrap').hidden = this.value !== 'self_describe'">@foreach (['woman' => 'Woman', 'man' => 'Man', 'non_binary' => 'Non-binary', 'transgender_woman' => 'Transgender Woman', 'transgender_man' => 'Transgender Man', 'genderqueer' => 'Genderqueer / Gender Non-conforming', 'self_describe' => 'Prefer to self-describe', 'prefer_not_to_say' => 'Prefer not to say'] as $value => $label)<option value="{{ $value }}" @selected(old('preferred_gender_identity', $user->preferred_gender_identity ?: ($user->gender === 'others' ? 'self_describe' : ($user->gender === 'female' ? 'woman' : 'man'))) === $value)>{{ $label }}</option>@endforeach</select><div id="gender_identity_other_wrap" class="mt-2" @if(old('preferred_gender_identity', $user->preferred_gender_identity ?: ($user->gender === 'others' ? 'self_describe' : null)) !== 'self_describe') hidden @endif><input name="gender_identity_other" value="{{ old('gender_identity_other', $user->gender_identity_other ?: $user->gender_other) }}" class="form-control" placeholder="Please specify your gender identity"></div><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_lgbtqia" value="1" id="is_lgbtqia" @checked(old('is_lgbtqia', $user->is_lgbtqia))><label class="form-check-label" for="is_lgbtqia">I identify as part of the LGBTQIA+ community</label></div></div>
          <div class="col-md-3"><label for="birthdate" class="form-label">Birthdate</label><input id="birthdate" name="birthdate" type="date" value="{{ $user->birthdate?->toDateString() }}" max="{{ now()->toDateString() }}" class="form-control" required></div>
          <div class="col-md-3"><label class="form-label">Age</label><input value="{{ $user->age !== null ? $user->age . ' years old' : '—' }}" readonly class="form-control bg-body-secondary"></div>
          <div class="col-md-6"><label for="contact_number" class="form-label">Contact number <span class="text-secondary fw-normal">(optional)</span></label><input id="contact_number" name="contact_number" value="{{ $user->contact_number }}" class="form-control"></div>
          <div class="col-12 col-md-6"><label for="occupation" class="form-label">Occupation <span class="text-secondary fw-normal">(optional)</span></label><input id="occupation" name="occupation" value="{{ $user->occupation }}" class="form-control" data-title-case></div>
          @include('pages.partials.student-fields', ['user' => $user])
        </div>
      </div>
    </div>

    <div class="card yg-card mb-4">
      <div class="card-body p-4">
        <h6 class="fw-bold text-uppercase text-secondary mb-1">Address</h6>
        <p class="small text-secondary">Fill in the first three. The rest is fixed for our barangay.</p>
        <div class="row g-3">
          <div class="col-md-4"><label for="house_no" class="form-label">House / building no.</label><input id="house_no" name="house_no" value="{{ $user->house_no }}" class="form-control" required></div>
          <div class="col-md-4"><label for="street" class="form-label">Street</label><input id="street" name="street" value="{{ $user->street }}" class="form-control" data-title-case required></div>
          <div class="col-md-4"><label for="zone" class="form-label">Zone number</label><input id="zone" name="zone" value="{{ $user->zone }}" class="form-control" required></div>
        </div>
        <div class="row g-3 mt-1 mx-0 p-3 bg-body-secondary rounded-3">@foreach (['Barangay' => $user->barangay, 'City / Municipality' => $user->city, 'Province' => $user->province, 'Country' => $user->country, 'Postal code' => $user->postal_code] as $label => $value)<div class="col-6 col-md"><div class="small text-uppercase text-secondary">{{ $label }}</div><div class="fw-semibold">{{ $value }}</div></div>@endforeach</div>
      </div>
    </div>

    <div class="card yg-card mb-4">
      <div class="card-body p-4">
        <h6 class="fw-bold text-uppercase text-secondary mb-3">Household</h6>
        <input type="hidden" name="is_head_of_family" value="0">
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_head_of_family" value="1" id="is_head_of_family" @checked($user->is_head_of_family)><label class="form-check-label" for="is_head_of_family">Is the head of the family</label></div>
        <div id="family_link_wrap" class="row g-3 mt-2"><div class="col-md-6"><label for="head_of_family_id" class="form-label">Link to a registered family head</label><select id="head_of_family_id" name="head_of_family_id" class="form-select"><option value="">Not linked</option>@foreach ($familyHeads as $head)<option value="{{ $head->id }}" @selected($user->head_of_family_id === $head->id)>{{ $head->full_name }} — {{ $head->house_no }} {{ $head->street }}, Zone {{ $head->zone }}</option>@endforeach</select></div><div class="col-md-6"><label for="head_of_family_name" class="form-label">Family head name</label><input id="head_of_family_name" name="head_of_family_name" value="{{ $user->head_of_family_name }}" class="form-control"></div></div>
      </div>
    </div>

    <div class="card yg-card mb-4">
      <div class="card-body p-4">
        <h6 class="fw-bold text-uppercase text-secondary mb-3">Account</h6>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Username</label><input value="{{ $user->username }}" class="form-control bg-body-secondary" readonly></div>
          <div class="col-md-6"><label for="email" class="form-label">Email</label><input id="email" type="email" name="email" value="{{ $user->email }}" class="form-control" required></div>
          <div class="col-md-6"><label for="password" class="form-label">Password</label><input id="password" type="password" name="password" class="form-control" autocomplete="new-password"><div class="form-text">Leave blank to keep the current password.</div></div>
          <div class="col-md-6"><label for="password_confirmation" class="form-label">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password"></div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4"><a href="{{ route('users.index') }}" class="link-secondary small">Cancel</a><button type="submit" class="btn btn-primary fw-semibold px-4"><i class="bi bi-check-circle me-1"></i>Save changes</button></div>
  </form>
@endsection

@push('scripts')
<script>
  const role = document.getElementById('role');
  const group = document.getElementById('official_group');
  const position = document.getElementById('official_position');
  const officialFields = document.querySelectorAll('.official-fields');
  function syncOfficialFields() {
    const visible = role.value === 'official';
    officialFields.forEach((field) => field.classList.toggle('d-none', !visible));
    group.required = visible;
    position.required = visible;
    Array.from(position.options).forEach((option) => { option.hidden = option.value !== '' && option.dataset.group !== group.value; });
  }
  role.addEventListener('change', syncOfficialFields);
  group.addEventListener('change', syncOfficialFields);
  syncOfficialFields();

  const head = document.getElementById('is_head_of_family');
  const familyWrap = document.getElementById('family_link_wrap');
  function syncFamilyFields() { familyWrap.hidden = head.checked; familyWrap.querySelectorAll('input, select').forEach((field) => field.disabled = head.checked); }
  head.addEventListener('change', syncFamilyFields);
  syncFamilyFields();
</script>
@endpush
