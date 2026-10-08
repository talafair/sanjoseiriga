@extends('layouts.app')

@section('title', 'Leaderboard')

@section('content')

    {{-- Header --}}
    <div class="page-header p-4 p-lg-5 mb-5">
        <div class="position-relative" style="z-index: 1;">
            <h2 class="fw-bold mb-1"><i class="bi bi-trophy-fill me-2"></i>Leaderboard</h2>
            <p class="mb-0 opacity-75">Rankings are based on total points earned</p>
        </div>
    </div>

    @if ($players->isEmpty())
        <div class="card yg-card text-center p-5">
            <i class="bi bi-trophy text-secondary" style="font-size: 3.5rem;"></i>
            <h4 class="fw-bold mt-3">No rankings yet</h4>
            <p class="text-secondary mb-0">Rankings will appear once members start earning points.</p>
            @if (auth()->check() && auth()->user()->isOfficial())
                <a href="{{ route('spin') }}" class="btn btn-yg mx-auto mt-3"><i class="bi bi-stars me-2"></i>Spin the Wheel</a>
            @endif
        </div>
    @else

        @php
            $avatarAlts = ['alt-1', 'alt-2', 'alt-3', 'alt-4', ''];
            $podiumMeta = [
                ['class' => 'podium-1', 'order' => 'order-lg-2 order-1', 'rank' => '1', 'avatar' => 'avatar-xl', 'title' => 'h4', 'score' => 'fs-3 fw-bold text-gold'],
                ['class' => 'podium-2', 'order' => 'order-lg-1 order-2', 'rank' => '2', 'avatar' => 'avatar-lg', 'title' => 'h5', 'score' => 'fs-4 fw-bold text-yg'],
                ['class' => 'podium-3', 'order' => 'order-lg-3 order-3', 'rank' => '3', 'avatar' => 'avatar-lg', 'title' => 'h5', 'score' => 'fs-4 fw-bold text-yg'],
            ];
        @endphp

        {{-- Top 3 podium --}}
        <div class="row g-4 justify-content-center align-items-end mb-5 pt-3">
            @foreach (($ranking === 'family' ? $households->take(3) : $podium) as $i => $entry)
                @php
                    $player = $ranking === 'family' ? $entry['head'] : $entry;
                    $entryPoints = $ranking === 'family' ? $entry['points'] : $player->points;
                @endphp
                <div class="col-10 col-sm-6 col-lg-3 {{ $podiumMeta[$i]['order'] }}">
                    <div class="card podium-card {{ $podiumMeta[$i]['class'] }} text-center p-4 {{ $i === 0 ? 'pb-5' : '' }}">
                        <span class="podium-rank">{!! $podiumMeta[$i]['rank'] !!}</span>
                        <img src="{{ $player->avatar_url }}" alt="{{ $player->name }}" class="avatar {{ $podiumMeta[$i]['avatar'] }} object-fit-cover mx-auto mt-3 mb-2">
                        <div class="{{ $podiumMeta[$i]['title'] }} fw-bold mb-0">
                            {{ $player->name }}
                            @if ($ranking === 'individual' && auth()->id() === $player->id)<span class="badge badge-yg rounded-pill ms-1">You</span>@endif
                        </div>
                        <div class="small text-secondary mb-2">{{ $ranking === 'family' ? 'Household ranking' : '@' . $player->username }}</div>
                        <div class="leader-badges justify-content-center mb-3" aria-label="{{ $player->badges->count() }} badges earned">
                            @forelse ($player->badges as $badge)
                                <img src="{{ $badge->image_url }}" alt="{{ $badge->name }}" class="leader-badge" title="{{ $badge->name }}">
                            @empty
                                <span class="small text-secondary">No badges yet</span>
                            @endforelse
                        </div>
                        <div class="small text-secondary">{{ number_format($entryPoints) }} points</div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Ranking type --}}
        <div class="d-flex justify-content-center mb-4">
            <div class="btn-group" role="group" aria-label="Ranking type">
                <a href="{{ route('leaderboard', ['ranking' => 'individual']) }}" class="btn {{ $ranking === 'individual' ? 'btn-yg' : 'btn-outline-yg' }}">
                    <i class="bi bi-person me-1"></i>Individual Rankings
                </a>
                <a href="{{ route('leaderboard', ['ranking' => 'family']) }}" class="btn {{ $ranking === 'family' ? 'btn-yg' : 'btn-outline-yg' }}">
                    <i class="bi bi-people me-1"></i>Family Rankings
                </a>
            </div>
        </div>

        {{-- Search --}}
        <div class="card yg-card p-3 mb-4">
            <div class="input-group" style="max-width: 420px;">
                <span class="input-group-text yg-addon"><i class="bi bi-search"></i></span>
                <input type="search" id="leaderSearch" class="form-control yg-input"
                       placeholder="Search {{ $ranking === 'family' ? 'family heads...' : 'players...' }}" aria-label="Search {{ $ranking === 'family' ? 'family heads' : 'players' }}">
            </div>
        </div>

        {{-- Ranking table --}}
        <div class="card yg-card overflow-hidden">
            <div class="table-responsive">
                <table class="table leader-table align-middle mb-0" id="leaderTable">
                    <thead>
                        @if ($ranking === 'family')
                            <tr class="small text-secondary text-uppercase"><th class="ps-4 py-3" style="width: 70px;">Rank</th><th class="py-3">Family Head</th><th class="py-3 text-center">Members</th><th class="pe-4 py-3 text-center">Total Points</th></tr>
                        @else
                            <tr class="small text-secondary text-uppercase"><th class="ps-4 py-3" style="width: 70px;">Rank</th><th class="py-3">Player</th><th class="py-3">Badges</th><th class="pe-4 py-3 text-center">Points</th></tr>
                        @endif
                    </thead>
                    <tbody>
                        @if ($ranking === 'family')
                            @foreach ($households as $i => $household)
                                @php($head = $household['head'])
                                <tr class="ranking-row">
                                    <td class="ps-4 fw-bold text-secondary">{{ $i + 1 }}</td>
                                    <td>
                                        <button class="btn btn-link link-dark text-decoration-none fw-semibold p-0 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#household-{{ $head->id }}" aria-expanded="false" aria-controls="household-{{ $head->id }}">
                                            <i class="bi bi-chevron-down me-2 small"></i>{{ $head->full_name }}
                                        </button>
                                    </td>
                                    <td class="text-center">{{ $household['members']->count() }}</td>
                                    <td class="pe-4 text-center fw-bold">{{ number_format($household['points']) }}</td>
                                </tr>
                                <tr class="collapse bg-light" id="household-{{ $head->id }}">
                                    <td></td>
                                    <td colspan="3" class="py-3">
                                        <div class="family-details">
                                            <div class="fw-bold mb-3"><i class="bi bi-house-door-fill text-yg me-1"></i>Household Details</div>
                                            <div class="mb-2"><strong>Family Head:</strong> {{ $head->full_name }}</div>
                                            <div class="mb-3"><strong>Address:</strong> {{ $head->full_address ?: 'Address not provided' }}</div>
                                            <div class="small text-uppercase text-secondary mb-2">Household Members</div>
                                            <div class="family-member-list">
                                                @foreach ($household['members'] as $member)
                                                    <div class="family-member-row">
                                                        <span><i class="bi bi-person-circle text-secondary me-1"></i>{{ $member->full_name }}@if ($member->id === $head->id) <span class="badge badge-yg rounded-pill ms-1">Head</span>@endif</span>
                                                        <span>{{ number_format($member->points) }} pts</span>
                                                    </div>
                                                @endforeach
                                                <div class="family-member-row family-total-row">
                                                    <span>Total Family Points</span>
                                                    <span>{{ number_format($household['points']) }} pts</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            @foreach ($players as $i => $player)
                                <tr class="ranking-row {{ auth()->id() === $player->id ? 'current-user' : '' }}">
                                    <td class="ps-4 fw-bold {{ auth()->id() === $player->id ? 'text-yg' : 'text-secondary' }}">{{ $i + 1 }}</td>
                                    <td><div class="d-flex align-items-center gap-3"><img src="{{ $player->avatar_url }}" alt="{{ $player->name }}" class="avatar avatar-sm object-fit-cover"><span class="{{ auth()->id() === $player->id ? 'fw-bold' : 'fw-semibold' }}">{{ $player->name }} @if (auth()->id() === $player->id)<span class="badge badge-yg rounded-pill ms-1">You</span>@endif</span></div></td>
                                    <td><div class="leader-badges" aria-label="{{ $player->badges->count() }} badges earned">@forelse ($player->badges as $badge)<img src="{{ $badge->image_url }}" alt="{{ $badge->name }}" class="leader-badge" title="{{ $badge->name }}">@empty<span class="small text-secondary">None</span>@endforelse</div></td>
                                    <td class="pe-4 text-center fw-bold">{{ number_format($player->points) }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <p id="noPlayers" class="text-center text-secondary py-4 mb-0 d-none">
                <i class="bi bi-person-x fs-3 d-block mb-1"></i>No {{ $ranking === 'family' ? 'families' : 'players' }} found.
            </p>
        </div>

    @endif

@endsection

@push('styles')
<style>
    .leader-badges { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; }
    .leader-badge { width: 2.5rem; height: 2.5rem; object-fit: contain; }
    .podium-card .leader-badge { width: 2.85rem; height: 2.85rem; }

    .family-details { background: #fffef4; margin: -.75rem -1rem; padding: 1rem .75rem .7rem; }
    .family-member-list { background: #fff; }
    .family-member-row { border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; gap: 1rem; padding: .3rem .5rem; }
    .family-member-row:last-child { border-bottom: 0; }
    .family-total-row { border-top: 1px solid #adb5bd; font-weight: 700; }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const search = document.getElementById('leaderSearch');
        if (!search) return;
        const rows = document.querySelectorAll('#leaderTable tbody tr.ranking-row');
        const noPlayers = document.getElementById('noPlayers');

        search.addEventListener('input', function () {
            const term = this.value.trim().toLowerCase();
            let visible = 0;
            rows.forEach(row => {
                const show = !term || row.textContent.toLowerCase().includes(term);
                row.classList.toggle('d-none', !show);
                if (show) visible++;
            });
            noPlayers.classList.toggle('d-none', visible > 0);
        });
    })();
</script>
@endpush
