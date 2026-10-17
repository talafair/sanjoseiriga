@extends('layouts.app')

@section('title', 'Manage Users')

@section('content')

    {{-- Header --}}
    <div class="page-header p-3 p-lg-4 mb-3">
        <div class="row align-items-center position-relative" style="z-index: 1;">
            <div class="col">
                <h2 class="h4 fw-bold mb-1"><i class="bi bi-people-fill me-2"></i>Manage Users</h2>
                <p class="small mb-0 opacity-75">View and manage every account on the platform</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('users.create') }}" class="btn btn-yg btn-sm fw-semibold rounded-3">
                    <i class="bi bi-person-plus me-1"></i>Add User
                </a>
                <span class="badge bg-white text-yg rounded-pill px-2 py-1">
                    <i class="bi bi-person-check me-1"></i>{{ $users->count() }} {{ Str::plural('member', $users->count()) }}
                </span>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger rounded-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $errors->first() }}
        </div>
    @endif

    <div class="card yg-card p-3 mb-4">
        <div class="row g-2 align-items-center">
            <div class="col-lg-5">
                <div class="input-group">
                    <span class="input-group-text yg-addon"><i class="bi bi-search"></i></span>
                    <input type="search" id="userNameSearch" class="form-control yg-input" placeholder="Search by name..." aria-label="Search users by name" autocomplete="off">
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <select id="userCategoryFilter" class="form-select yg-input" aria-label="Filter by category">
                    <option value="all">All categories</option>
                    <option value="resident">Residents</option>
                    <option value="guest">Guests</option>
                    <option value="official">Officials</option>
                </select>
            </div>
            <div class="col-sm-6 col-lg-3">
                <select id="userStatusFilter" class="form-select yg-input" aria-label="Filter by status">
                    <option value="all">All statuses</option>
                    <option value="verified">Verified</option>
                    <option value="unverified">Unverified</option>
                    <option value="official">Official</option>
                </select>
            </div>
            <div class="col-lg-1 text-lg-end"><span id="userFilterCount" class="small text-secondary"></span></div>
        </div>
    </div>

    {{-- Users table --}}
    <div class="card yg-card overflow-hidden">
        <form id="bulk-id-print-form" method="POST" action="{{ route('users.id-cards.print-selected') }}" target="_blank">
            @csrf
            <div class="border-bottom p-3">
                <button type="button" class="btn btn-yg-outline btn-sm" id="toggle-id-selection" aria-expanded="false" aria-controls="id-selection-controls">
                    <i class="bi bi-check2-square me-1"></i>Select IDs to print
                </button>
                <div id="id-selection-controls" class="d-none flex-wrap align-items-center gap-2 mt-2">
                    <label for="bulk-id-paper-size" class="small text-secondary mb-0">Paper</label>
                    <select id="bulk-id-paper-size" name="paper_size" class="form-select form-select-sm" style="width: auto;">
                        <option value="a4" selected>A4</option>
                        <option value="letter">8.5 × 11 in</option>
                        <option value="long">8.5 × 13 in</option>
                    </select>
                    <button type="submit" class="btn btn-yg btn-sm" id="print-selected-ids" disabled>
                        <i class="bi bi-printer me-1"></i>Print Selected IDs
                    </button>
                    <span id="selected-id-count" class="small text-secondary" aria-live="polite">No resident IDs selected</span>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table user-management-table align-middle mb-0">
                <thead>
                    <tr class="small text-secondary text-uppercase">
                        <th class="id-selection-column ps-3 py-3 text-center d-none">
                            <input type="checkbox" id="select-all-resident-ids" class="form-check-input" aria-label="Select all visible residents">
                        </th>
                        <th class="ps-4 py-3 user-member-column">Member</th>
                        <th class="py-3 text-center">Category</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 text-center">Points</th>
                        <th class="pe-4 py-3 text-end" style="width: 70px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $i => $member)
                        <tr class="user-management-row" data-user-toggle="user-details-{{ $member->id }}" data-user-name="{{ strtolower($member->full_name) }}" data-user-category="{{ $member->role }}" data-user-status="{{ $member->role === 'official' ? ($member->is_verified ? 'verified' : 'unverified') : ($member->isOfficial() ? 'official' : ($member->is_verified ? 'verified' : 'unverified')) }}">
                            <td class="id-selection-column text-center d-none">
                                @if ($member->role === 'resident' && $member->unique_id)
                                    <input type="checkbox" class="form-check-input resident-id-selection" name="resident_ids[]" value="{{ $member->id }}" form="bulk-id-print-form" aria-label="Select TalaFair ID for {{ $member->full_name }}">
                                @endif
                            </td>
                            <td class="ps-4 user-member-cell">
                                <button type="button" class="btn btn-link link-dark text-decoration-none p-0 d-flex align-items-center gap-3 text-start" data-bs-toggle="collapse" data-bs-target="#user-details-{{ $member->id }}" aria-expanded="false" aria-controls="user-details-{{ $member->id }}">
                                    <img src="{{ $member->avatar_url }}" data-image-fallback="{{ asset('images/default-avatar.svg') }}" alt="{{ $member->name }}" class="avatar avatar-sm object-fit-cover">
                                    <div>
                                        <div class="fw-semibold text-truncate">
                                            {{ $member->name }}
                                            @if ($member->id === auth()->id())<span class="badge badge-yg rounded-pill ms-1">You</span>@endif
                                        </div>
                                        <div class="small text-secondary">{{ '@' . $member->username }}</div>
                                    </div>
                                </button>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $member->isOfficial() ? 'badge-gold' : 'badge-soft' }} rounded-pill">
                                    <i class="bi {{ $member->isOfficial() ? 'bi-person-badge' : 'bi-house-heart' }} me-1"></i>{{ \App\Models\User::ROLE_LABELS[$member->role] ?? 'Unassigned' }}
                                </span>
                                @if ($member->isOfficial() && ! $member->isSuperadmin() && $member->official_position)
                                    <div class="small text-secondary mt-1">{{ config("talafair.official_positions.{$member->official_group}.label") }}: {{ config("talafair.official_positions.{$member->official_group}.positions.{$member->official_position}") }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($member->isSuperadmin())
                                    <span class="badge bg-dark rounded-pill"><i class="bi bi-lock-fill me-1"></i>Protected</span>
                                @elseif ($member->role === 'official' && $member->is_verified)
                                    <span class="badge bg-success-subtle text-success-emphasis rounded-pill"><i class="bi bi-patch-check-fill me-1"></i>Verified</span>
                                @elseif ($member->role === 'official')
                                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill"><i class="bi bi-hourglass-split me-1"></i>Unverified</span>
                                @elseif ($member->isOfficial())
                                    <span class="badge badge-gold rounded-pill"><i class="bi bi-shield-check me-1"></i>Official</span>
                                @elseif ($member->is_verified)
                                    <span class="badge bg-success-subtle text-success-emphasis rounded-pill"><i class="bi bi-patch-check-fill me-1"></i>Verified</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill"><i class="bi bi-hourglass-split me-1"></i>Unverified</span>
                                @endif
                            </td>
                            <td class="text-center fw-bold">{{ number_format($member->points) }}</td>
                            <td class="pe-4 text-end user-actions-cell">
                                @if ($member->id !== auth()->id() && ! $member->isSuperadmin() && (auth()->user()->isSuperadmin() || ! $member->isOfficial()))
                                    <div class="dropdown">
                                        <button class="btn btn-link text-dark p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions for {{ $member->name }}">
                                            <i class="bi bi-three-dots-vertical fs-5"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if (! $member->isOfficial() || auth()->user()->isSuperadmin())
                                                <li>
                                                    <form action="{{ route('users.verification.toggle', $member) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="dropdown-item"><i class="bi {{ $member->is_verified ? 'bi-shield-x' : 'bi-shield-check' }} me-2"></i>{{ $member->is_verified ? 'Mark unverified' : 'Mark verified' }}</button>
                                                    </form>
                                                </li>
                                            @endif

                                            @if ($member->role === 'resident')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('users.id-card.print', $member) }}" target="_blank" rel="noopener">
                                                        <i class="bi bi-printer me-2"></i>Print TalaFair ID
                                                    </a>
                                                </li>
                                            @endif

                                            <li><a class="dropdown-item" href="{{ route('users.edit', $member) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                            <li>
                                                <form action="{{ route('users.destroy', $member) }}" method="POST" onsubmit="return confirm('Delete {{ $member->name }}\'s account? This cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                @else
                                    <span class="small text-secondary fst-italic">—</span>
                                @endif
                            </td>
                        </tr>
                        <tr class="collapse user-details-row" id="user-details-{{ $member->id }}">
                            <td colspan="6" class="p-0">
                                <div class="user-profile-details">
                                    <div class="fw-bold mb-3"><i class="bi bi-person-lines-fill text-yg me-1"></i>Profile Information</div>
                                    <div class="row g-4 align-items-start">
                                        <div class="col-12 col-md-3 d-flex justify-content-center"><img src="{{ $member->avatar_url }}" data-image-fallback="{{ asset('images/default-avatar.svg') }}" alt="{{ $member->full_name }}" width="112" height="112" class="rounded-circle object-fit-cover border"></div>
                                        <div class="col-12 col-md-9"><div class="row g-3 small profile-info-grid">
                                            <div class="col-md-6"><span class="detail-label">Full name</span><div>{{ $member->full_name }}</div></div>
                                            <div class="col-md-6"><span class="detail-label">Username</span><div>{{ '@' . $member->username }}</div></div>
                                            <div class="col-md-6"><span class="detail-label">Email</span><div>{{ $member->email }}</div></div>
                                            <div class="col-md-6"><span class="detail-label">Category</span><div>{{ \App\Models\User::ROLE_LABELS[$member->role] ?? 'Unassigned' }}</div></div>
                                            <div class="col-md-6"><span class="detail-label">Verification</span><div>{{ $member->isSuperadmin() ? 'Protected' : ($member->is_verified ? 'Verified' : 'Unverified') }}</div></div>
                                            <div class="col-md-6"><span class="detail-label">Points</span><div>{{ number_format($member->points) }}</div></div>
                                            <div class="col-md-6"><span class="detail-label">Joined</span><div>{{ $member->created_at?->format('M j, Y') ?: '—' }}</div></div>
                                            <div class="col-md-6"><span class="detail-label">Resident ID</span><div>{{ $member->unique_id ?: '—' }}</div></div>
                                            <div class="col-12"><span class="detail-label">Address</span><div>{{ $member->full_address ?: '—' }}</div></div>
                                            <div class="col-12"><span class="detail-label">Household</span><div>{{ $member->is_head_of_family ? 'Head of family' : ($member->head_of_family_name ?: 'Not linked') }}</div></div>
                                        </div></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p id="noUsersFound" class="text-center text-secondary py-4 mb-0 d-none">
                <i class="bi bi-person-x fs-3 d-block mb-1"></i>No users match the selected filters.
            </p>
        </div>
    </div>

    <p class="small text-secondary mt-3">
        <i class="bi bi-info-circle me-1"></i>You cannot edit or delete your own account from this page — use your Account page instead.
    </p>

@endsection

@push('styles')
<style>
    .user-profile-details { background: #fbfdf0; border-top: 1px solid rgba(90, 150, 52, .2); padding: 1.25rem 1.5rem; }
    .detail-label { color: #74806f; display: block; font-size: .68rem; letter-spacing: .04em; margin-bottom: .15rem; text-transform: uppercase; }
    .user-management-row > td { border-bottom-color: #dee2e6; }
    .user-details-row > td { border-bottom: 1px solid #dee2e6; }
    .user-member-cell > button { max-width: 100%; width: 100%; }

    @media (max-width: 767.98px) {
        .page-header .row { align-items: center !important; flex-wrap: nowrap; }
        .page-header .col { min-width: 0; }
        .page-header .col-auto { display: flex; flex: 0 0 auto; flex-direction: column; align-items: flex-end; gap: .5rem; margin-top: 0; width: auto; }
        .page-header .col-auto .btn { align-self: flex-end; margin-right: 0 !important; }
        .page-header .col-auto .badge { align-self: flex-end; }

        .user-management-table { min-width: 760px; table-layout: auto; }
        .user-management-table th,
        .user-management-table td { white-space: nowrap; }
        .user-management-table .user-member-cell { min-width: 270px; }
        .user-management-table .user-member-cell > button { gap: .75rem !important; width: auto; }
        .user-management-table .user-member-cell .fw-semibold,
        .user-management-table .user-member-cell .small { overflow-wrap: normal; }
        .user-management-table .user-management-row .badge { font-size: .75rem; }
        .user-profile-details { min-width: 760px; padding: 1.25rem 1.5rem; }
        .profile-info-grid > div > div { overflow-wrap: normal; }
    }
</style>
@endpush

@push('scripts')
<script>
    (() => {
        const search = document.getElementById('userNameSearch');
        const category = document.getElementById('userCategoryFilter');
        const status = document.getElementById('userStatusFilter');
        const count = document.getElementById('userFilterCount');
        const empty = document.getElementById('noUsersFound');
        const rows = [...document.querySelectorAll('.user-management-row')];

        function applyUserFilters() {
            const term = search.value.trim().toLowerCase();
            const categoryValue = category.value;
            const statusValue = status.value;
            let visible = 0;

            rows.forEach((row) => {
                const show = (!term || row.dataset.userName.includes(term))
                    && (categoryValue === 'all' || row.dataset.userCategory === categoryValue)
                    && (statusValue === 'all'
                        || (statusValue === 'official' ? row.dataset.userCategory === 'official' || row.dataset.userCategory === 'superadmin' : row.dataset.userStatus === statusValue));
                const details = document.getElementById(row.dataset.userToggle);
                row.classList.toggle('d-none', !show);
                details?.classList.toggle('d-none', !show);
                if (!show) details?.classList.remove('show');
                if (show) visible++;
            });

            count.textContent = `${visible} ${visible === 1 ? 'user' : 'users'}`;
            empty.classList.toggle('d-none', visible !== 0);
        }

        const selectAll = document.getElementById('select-all-resident-ids');
        const printSelected = document.getElementById('print-selected-ids');
        const selectedCount = document.getElementById('selected-id-count');
        const residentSelections = [...document.querySelectorAll('.resident-id-selection')];
        const toggleIdSelection = document.getElementById('toggle-id-selection');
        const idSelectionControls = document.getElementById('id-selection-controls');
        const idSelectionColumns = [...document.querySelectorAll('.id-selection-column')];

        toggleIdSelection.addEventListener('click', () => {
            const isExpanded = toggleIdSelection.getAttribute('aria-expanded') === 'true';
            toggleIdSelection.setAttribute('aria-expanded', String(!isExpanded));
            toggleIdSelection.innerHTML = isExpanded
                ? '<i class="bi bi-check2-square me-1"></i>Select IDs to print'
                : '<i class="bi bi-x-lg me-1"></i>Hide ID selection';
            idSelectionControls.classList.toggle('d-none', isExpanded);
            idSelectionControls.classList.toggle('d-flex', !isExpanded);
            idSelectionColumns.forEach((column) => column.classList.toggle('d-none', isExpanded));
        });

        function updateIdSelection() {
            const checked = residentSelections.filter((input) => input.checked);
            printSelected.disabled = checked.length === 0;
            selectedCount.textContent = checked.length
                ? `${checked.length} resident ${checked.length === 1 ? 'ID' : 'IDs'} selected`
                : 'No resident IDs selected';
            selectAll.checked = residentSelections.length > 0 && residentSelections.every((input) => input.checked);
            selectAll.indeterminate = checked.length > 0 && checked.length < residentSelections.length;
        }

        selectAll.addEventListener('change', () => {
            residentSelections.forEach((input) => { input.checked = selectAll.checked; });
            updateIdSelection();
        });
        residentSelections.forEach((input) => input.addEventListener('change', updateIdSelection));

        search.addEventListener('input', applyUserFilters);
        category.addEventListener('change', applyUserFilters);
        status.addEventListener('change', applyUserFilters);
        applyUserFilters();
    })();
</script>
@endpush
