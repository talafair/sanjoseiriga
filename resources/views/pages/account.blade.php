@extends('layouts.app')

@section('title', 'My Account')

@section('content')

  <div class="page-header p-4 p-lg-5 mb-4">
    <div class="row align-items-center position-relative" style="z-index: 1;">
      <div class="col-md d-flex align-items-center gap-3">
        <img src="{{ $user->avatar_url }}" alt="{{ $user->full_name }}" width="88" height="88"
             class="rounded-circle border border-3 border-white object-fit-cover shadow-sm flex-shrink-0">
        <div>
          <h2 class="fw-bold mb-1">{{ $user->full_name }}</h2>
          <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-white text-yg rounded-pill px-3 py-2">
              <i class="bi bi-person-badge me-1"></i>{{ $user->unique_id ?? 'ID pending' }}
            </span>
            @if ($user->isOfficial())
              <span class="badge badge-gold rounded-pill px-3 py-2"><i class="bi bi-shield-check me-1"></i>Official account</span>
            @elseif ($user->is_verified)
              <span class="badge badge-yg rounded-pill px-3 py-2"><i class="bi bi-patch-check-fill me-1"></i>Verified resident</span>
            @else
              <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="bi bi-hourglass-split me-1"></i>Unverified resident</span>
            @endif
          </div>
        </div>
      </div>
      <div class="col-auto mt-3 mt-md-0 d-flex flex-wrap justify-content-end align-items-center gap-2">
        <span class="badge bg-white text-yg rounded-pill px-3 py-2 fs-6">
          <i class="bi bi-star-fill me-1"></i>{{ number_format($user->points) }} points
        </span>
        <a href="{{ route('id-card.show') }}" class="btn btn-dark fw-semibold">
          <i class="bi bi-person-badge me-1"></i>View my ID card
        </a>
      </div>
    </div>
  </div>

  <div class="row g-4">

    {{-- Earned badges --}}
    <div class="col-12">
      <div class="card yg-card">
        <div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-award-fill me-1 text-yg"></i>My badges</h6>
            <span class="small text-secondary">{{ $user->badges->count() }} earned</span>
            <a href="{{ route('badges.catalog') }}" class="small fw-semibold text-yg text-decoration-none">View all <i class="bi bi-arrow-right"></i></a>
          </div>

          @forelse ($user->badges as $badge)
            <div class="d-inline-flex align-items-center gap-2 border rounded p-2 me-2 mb-2">
              <img src="{{ $badge->image_url }}" alt="{{ $badge->name }}" width="48" height="48" class="object-fit-cover rounded">
              <div>
                <div class="fw-semibold">{{ $badge->name }}</div>
                @if ($badge->description)
                  <div class="small text-secondary">{{ $badge->description }}</div>
                @endif
              </div>
            </div>
          @empty
            <p class="small text-secondary mb-0">You have not earned any badges yet.</p>
          @endforelse
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card yg-card">
        <div class="card-body p-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-1 text-yg"></i>Points history</h6>
          @forelse ($recentPointTransactions as $transaction)
            <div class="d-flex justify-content-between align-items-center gap-3 border-bottom py-2">
              <div>
                <div class="fw-semibold">{{ $transaction->description }}</div>
                <div class="small text-secondary">Base points: {{ $transaction->base_points }} · Multiplier: {{ number_format($transaction->multiplier, 2) }}x · {{ $transaction->created_at->format('M j, Y g:i A') }}</div>
              </div>
              <span class="fw-bold text-success">+{{ $transaction->points_awarded }}</span>
            </div>
          @empty
            <p class="small text-secondary mb-0">No point transactions yet.</p>
          @endforelse
        </div>
      </div>
    </div>

    @if ($householdHead)
      <div class="col-12">
        <div class="card yg-card">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="fw-bold mb-0"><i class="bi bi-people-fill me-1 text-yg"></i>My household</h6>
              <span class="small text-secondary">{{ $householdMembers->count() + 1 }} household {{ Str::plural('person', $householdMembers->count() + 1) }}</span>
            </div>
            <div class="d-flex align-items-center gap-3 border border-success-subtle rounded p-2 mb-2 bg-success-subtle bg-opacity-25">
              <img src="{{ $householdHead->avatar_url }}" alt="{{ $householdHead->full_name }}" width="44" height="44" class="rounded-circle object-fit-cover">
              <div class="flex-grow-1">
                <div class="fw-semibold">{{ $householdHead->full_name }} @if ($householdHead->id === $user->id)<span class="text-secondary fw-normal">(You)</span>@endif</div>
                <div class="small text-secondary">{{ $householdHead->email }}</div>
              </div>
              <span class="badge badge-yg">Family head</span>
            </div>
            @forelse ($householdMembers as $member)
              <div class="d-flex align-items-center gap-3 border rounded p-2 mb-2">
                <img src="{{ $member->avatar_url }}" alt="{{ $member->full_name }}" width="44" height="44" class="rounded-circle object-fit-cover">
                <div class="flex-grow-1">
                  <div class="fw-semibold">{{ $member->full_name }}</div>
                  <div class="small text-secondary text-capitalize">{{ $member->role }} · {{ $member->email }}</div>
                </div>
                @if ($member->head_of_family_id === $user->id)
                  <span class="badge badge-soft">Linked</span>
                @else
                  <span class="badge bg-light text-secondary">Address match</span>
                @endif
              </div>
            @empty
              <p class="small text-secondary mb-0">No registered household members found yet.</p>
            @endforelse

          </div>
        </div>
      </div>
    @endif
    @if (! $householdHead)
      <div class="col-12">
        <div class="card yg-card border-warning-subtle">
          <div class="card-body p-3">
            <h6 class="fw-bold mb-1"><i class="bi bi-people-fill me-1 text-warning"></i>Household</h6>
            <p class="small text-secondary mb-0">No Household Head linked to this account. Please ask an Official to update the household assignment.</p>
          </div>
        </div>
      </div>
    @endif

    {{-- Account settings --}}
    <div class="col-12 mt-4">
      <div class="accordion" id="account-settings">
        <div class="accordion-item yg-card">
          <h2 class="accordion-header" id="profile-picture-heading">
            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                    data-bs-target="#profile-picture-panel" aria-expanded="false" aria-controls="profile-picture-panel">
              <i class="bi bi-image me-2 text-yg"></i>Profile picture
            </button>
          </h2>
          <div id="profile-picture-panel" class="accordion-collapse collapse" aria-labelledby="profile-picture-heading">
            <div class="accordion-body p-4">
              <form method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data">
                @csrf
                <div class="text-center mb-3">
                  <img src="{{ $user->avatar_url }}" alt="{{ $user->full_name }}" id="avatar-preview" width="120" height="120"
                       class="rounded-circle border object-fit-cover">
                </div>

                <input type="file" name="avatar" accept="image/*" required
                       onchange="document.getElementById('avatar-preview').src = URL.createObjectURL(this.files[0])"
                       class="form-control @error('avatar') is-invalid @enderror">
                @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <button class="btn btn-primary fw-semibold mt-3">
                  <i class="bi bi-upload me-1"></i>Upload photo
                </button>
              </form>

              @if ($user->avatar_path)
                <form method="POST" action="{{ route('profile.photo.destroy') }}" class="mt-2">
                  @csrf @method('DELETE')
                  <button class="btn btn-link btn-sm text-danger p-0">Remove current photo</button>
                </form>
              @endif
            </div>
          </div>
        </div>

        <div class="accordion-item yg-card">
          <h2 class="accordion-header" id="account-settings-heading">
            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                    data-bs-target="#account-settings-panel" aria-expanded="false" aria-controls="account-settings-panel">
              <i class="bi bi-person-lines-fill me-2 text-yg"></i>Account settings
            </button>
          </h2>
          <div id="account-settings-panel" class="accordion-collapse collapse" aria-labelledby="account-settings-heading">
            <div class="accordion-body p-4">

          @if (! $user->isOfficial())
            <div class="alert alert-light border small"><i class="bi bi-lock me-1"></i>Account information can only be edited by an official. Please contact an official if anything needs to be corrected.</div>
            <div class="row g-3">
              <div class="col-md-3"><label class="form-label text-secondary">First name</label><div class="form-control bg-body-secondary">{{ $user->first_name }}</div></div>
              <div class="col-md-3"><label class="form-label text-secondary">Middle name</label><div class="form-control bg-body-secondary">{{ $user->middle_name ?: '—' }}</div></div>
              <div class="col-md-3"><label class="form-label text-secondary">Last name</label><div class="form-control bg-body-secondary">{{ $user->last_name }}</div></div>
              <div class="col-md-3"><label class="form-label text-secondary">Suffix</label><div class="form-control bg-body-secondary">{{ $user->suffix ?: '—' }}</div></div>
              <div class="col-md-4"><label class="form-label text-secondary">Gender</label><div class="form-control bg-body-secondary text-capitalize">{{ $user->gender }}</div></div>
              <div class="col-md-4"><label class="form-label text-secondary">Birthdate</label><div class="form-control bg-body-secondary">{{ $user->birthdate?->format('F j, Y') ?: '—' }}</div></div>
              <div class="col-md-4"><label class="form-label text-secondary">Contact number</label><div class="form-control bg-body-secondary">{{ $user->contact_number ?: '—' }}</div></div>
              <div class="col-md-4"><label class="form-label text-secondary">Email</label><div class="form-control bg-body-secondary">{{ $user->email }}</div></div>
              <div class="col-md-4"><label class="form-label text-secondary">House / building no.</label><div class="form-control bg-body-secondary">{{ $user->house_no }}</div></div>
              <div class="col-md-4"><label class="form-label text-secondary">Street</label><div class="form-control bg-body-secondary">{{ $user->street }}</div></div>
              <div class="col-md-4"><label class="form-label text-secondary">Zone number</label><div class="form-control bg-body-secondary">{{ $user->zone }}</div></div>
              <div class="col-md-4"><label class="form-label text-secondary">Occupation</label><div class="form-control bg-body-secondary">{{ $user->occupation ?: '—' }}</div></div>
              <div class="col-md-4"><label class="form-label text-secondary">School</label><div class="form-control bg-body-secondary">{{ $user->school ?: '—' }}</div></div>
            </div>
          @else

          <form method="POST" action="{{ route('profile.update') }}" class="row g-3">
            @csrf @method('PATCH')

            @if ($user->isOfficial())
              <div class="col-12">
                <h6 class="fw-bold text-uppercase text-secondary mb-0">Official position</h6>
              </div>
              <div class="col-md-6">
                <label for="account_official_group" class="form-label">Group</label>
                <select id="account_official_group" name="official_group" class="form-select" required>
                  @foreach (config('talafair.official_positions') as $groupKey => $group)
                    <option value="{{ $groupKey }}" @selected(old('official_group', $user->official_group) === $groupKey)>{{ $group['label'] }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6">
                <label for="account_official_position" class="form-label">Position</label>
                <select id="account_official_position" name="official_position" class="form-select" required>
                  @foreach (config('talafair.official_positions') as $groupKey => $group)
                    @foreach ($group['positions'] as $positionKey => $positionLabel)
                      <option value="{{ $positionKey }}" data-group="{{ $groupKey }}" @selected(old('official_position', $user->official_position) === $positionKey)>{{ $positionLabel }}</option>
                    @endforeach
                  @endforeach
                </select>
              </div>
            @endif

            <div class="col-md-3">
              <label class="form-label">First name</label>
              <input name="first_name" value="{{ old('first_name', $user->first_name) }}" required class="form-control" data-title-case>
            </div>
            <div class="col-md-3">
              <label class="form-label">Middle name</label>
              <input name="middle_name" value="{{ old('middle_name', $user->middle_name) }}" class="form-control" data-title-case>
            </div>
            <div class="col-md-3">
              <label class="form-label">Last name</label>
              <input name="last_name" value="{{ old('last_name', $user->last_name) }}" required class="form-control" data-title-case>
            </div>
            <div class="col-md-3">
              <label class="form-label">Suffix</label>
              <input name="suffix" value="{{ old('suffix', $user->suffix) }}" class="form-control" data-title-case>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Sex at Birth</label>
              <select name="sex_at_birth" class="form-select">
                @foreach (['female' => 'Female', 'male' => 'Male', 'prefer_not_to_say' => 'Prefer not to say'] as $v => $l)
                  <option value="{{ $v }}" @selected(old('sex_at_birth', $user->sex_at_birth ?: $user->gender) === $v)>{{ $l }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Preferred Gender Identity</label>
              <select name="preferred_gender_identity" id="account_preferred_gender_identity" class="form-select" required>
                @foreach (['woman' => 'Woman', 'man' => 'Man', 'non_binary' => 'Non-binary', 'transgender_woman' => 'Transgender Woman', 'transgender_man' => 'Transgender Man', 'genderqueer' => 'Genderqueer / Gender Non-conforming', 'self_describe' => 'Prefer to self-describe', 'prefer_not_to_say' => 'Prefer not to say'] as $v => $l)
                  <option value="{{ $v }}" @selected(old('preferred_gender_identity', $user->preferred_gender_identity ?: ($user->gender === 'female' ? 'woman' : 'man')) === $v)>{{ $l }}</option>
                @endforeach
              </select>
              <input name="gender_identity_other" value="{{ old('gender_identity_other', $user->gender_identity_other ?: $user->gender_other) }}" class="form-control mt-2" placeholder="Please specify your gender identity">
            </div>
            <div class="col-md-4">
              <div class="form-check mt-md-4"><input class="form-check-input" type="checkbox" name="is_lgbtqia" value="1" id="account_is_lgbtqia" @checked(old('is_lgbtqia', $user->is_lgbtqia))><label class="form-check-label" for="account_is_lgbtqia">I identify as part of the LGBTQIA+ community</label></div>
            </div>

            <div class="col-md-2">
              <label class="form-label">Birthdate</label>
              <input type="date" name="birthdate" max="{{ now()->toDateString() }}" required
                     value="{{ old('birthdate', $user->birthdate?->toDateString()) }}" class="form-control">
            </div>
            <div class="col-md-2">
              <label class="form-label">Age</label>
              <input value="{{ $user->age !== null ? $user->age : '—' }}" readonly class="form-control bg-body-secondary">
            </div>

            <div class="col-md-4">
              <label class="form-label">Contact number</label>
              <input name="contact_number" value="{{ old('contact_number', $user->contact_number) }}" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Occupation</label>
              <input name="occupation" value="{{ old('occupation', $user->occupation) }}" class="form-control" data-title-case>
            </div>
            @include('pages.partials.student-fields', ['formPrefix' => 'account_', 'user' => $user])
            <div class="col-md-4">
              <label class="form-label">Email</label>
              <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="form-control">
            </div>

            <div class="col-md-4">
              <label class="form-label">House / building no.</label>
              <input name="house_no" value="{{ old('house_no', $user->house_no) }}" required class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Street</label>
              <input name="street" value="{{ old('street', $user->street) }}" required class="form-control" data-title-case>
            </div>
            <div class="col-md-4">
              <label class="form-label">Zone number</label>
              <input name="zone" value="{{ old('zone', $user->zone) }}" required class="form-control">
            </div>

            <div class="col-12">
              <div class="alert alert-light border small mb-0">
                <i class="bi bi-geo-alt me-1"></i>
                Barangay San Jose &middot; Iriga City &middot; Camarines Sur &middot; Philippines &middot; 4431
              </div>
            </div>

            <div class="col-12">
              <button class="btn btn-primary fw-semibold px-4">Save changes</button>
            </div>
          </form>
          @endif
            </div>
          </div>
        </div>

        <div class="accordion-item yg-card mt-3">
          <h2 class="accordion-header" id="change-password-heading">
            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                    data-bs-target="#change-password-panel" aria-expanded="false" aria-controls="change-password-panel">
              <i class="bi bi-shield-lock me-2 text-yg"></i>Change password
            </button>
          </h2>
          <div id="change-password-panel" class="accordion-collapse collapse" aria-labelledby="change-password-heading">
            <div class="accordion-body p-4">
              <p class="small text-secondary">Use at least 8 characters you do not use anywhere else.</p>
              <form method="POST" action="{{ route('password.update') }}">
                @csrf @method('PUT')
                <div class="mb-3">
                  <label for="current_password" class="form-label">Current password</label>
                  <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                         class="form-control @error('current_password', 'updatePassword') is-invalid @enderror">
                  @error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                  <label for="new_password" class="form-label">New password</label>
                  <input id="new_password" name="password" type="password" required autocomplete="new-password"
                         class="form-control @error('password', 'updatePassword') is-invalid @enderror">
                  @error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                  <label for="password_confirmation" class="form-label">Confirm new password</label>
                  <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="form-control">
                </div>
                <button class="btn btn-dark fw-semibold px-4"><i class="bi bi-shield-check me-1"></i>Change password</button>
              </form>
            </div>
          </div>
        </div>

        <div class="card yg-card mt-3" id="install-app-card">
          <div class="card-body p-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
            <div>
              <h6 class="fw-bold mb-1"><i class="bi bi-phone me-1 text-yg"></i>Install TalaFair</h6>
              <p class="small text-secondary mb-0">Add TalaFair to your device home screen for quick access.</p>
            </div>
            <button type="button" id="install-app" class="btn btn-primary fw-semibold">
              <i class="bi bi-download me-1"></i>Install app
            </button>
          </div>
        </div>

        <div class="card yg-card mt-3">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="fw-bold mb-0"><i class="bi bi-building me-1 text-yg"></i>Barangay San Jose, Iriga City</h6>
              <span class="small text-secondary">Need help?</span>
            </div>
            <p class="small text-secondary mb-3">Contact the Barangay San Jose Council for help with profile information or community concerns.</p>
            <div class="row g-2">
              <div class="col-12 col-md-4">
                <a href="tel:09919702491" class="btn btn-light border w-100 text-start">
                  <i class="bi bi-telephone me-2 text-yg"></i>0991 970 2491
                </a>
              </div>
              <div class="col-12 col-md-4">
                <a href="mailto:sanjosebrgycouncil@gmail.com" class="btn btn-light border w-100 text-start text-truncate">
                  <i class="bi bi-envelope me-2 text-yg"></i>sanjosebrgycouncil@gmail.com
                </a>
              </div>
              <div class="col-12 col-md-4">
                <a href="https://www.facebook.com/SanJoseIrigaCityCamSur" target="_blank" rel="noopener noreferrer" class="btn btn-light border w-100 text-start">
                  <i class="bi bi-facebook me-2 text-yg"></i>Facebook page
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  const accountGroup = document.getElementById('account_official_group');
  const accountPosition = document.getElementById('account_official_position');
  if (accountGroup && accountPosition) {
    function filterAccountPositions() {
      Array.from(accountPosition.options).forEach(function (option) {
        option.hidden = option.dataset.group !== accountGroup.value;
      });
      if (accountPosition.selectedOptions[0]?.dataset.group !== accountGroup.value) accountPosition.value = '';
    }
    accountGroup.addEventListener('change', filterAccountPositions);
    filterAccountPositions();
  }

</script>
@endpush

