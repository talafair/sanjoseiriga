@extends('layouts.app')

@section('title', 'Home')

@section('content')

@include('pages.partials.home-hero')

    {{-- Welcome header --}}
    <div class="page-header p-4 p-lg-5 mb-4">
        <div class="row align-items-center position-relative" style="z-index: 1;">
            <div class="col">
                <h2 class="fw-bold mb-1">Here's what's happening on TalaFair right now!</h2>
                <p class="mb-0 opacity-75">See more of the latest happening in the community.</p>
            </div>
            
        </div>
    </div>

    <div class="row g-4">

        {{-- Recent leaderboard --}}
        <div class="col-lg-4">
            <div class="card yg-card h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-trophy me-2 text-yg"></i>Leaderboard</span>
                    <a href="{{ route('leaderboard') }}" class="small fw-semibold text-yg text-decoration-none">View all <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="card-body p-3">
                    @forelse ($topPlayers as $i => $player)
                        <div class="home-ranking-row d-flex align-items-center gap-3 p-2 {{ !$loop->first ? 'border-top' : '' }}">
                            <span class="home-ranking-rank fw-bold text-secondary">
                                {{ $i + 1 }}
                            </span>
                            <img src="{{ $player->avatar_url }}" alt="{{ $player->name }}" class="avatar avatar-sm object-fit-cover">
                            <div class="flex-grow-1 text-truncate">
                                <div class="fw-semibold small text-truncate">
                                    {{ $player->name }}
                                    @if ($player->id === auth()->id())<span class="badge badge-yg rounded-pill ms-1">You</span>@endif
                                </div>
                            </div>
                            <span class="badge badge-soft rounded-pill">{{ number_format($player->points) }} pts</span>
                        </div>
                    @empty
                        <p class="text-center text-secondary small py-4 mb-0">
                            <i class="bi bi-trophy fs-4 d-block mb-1"></i>No rankings yet.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent spin winners --}}
        <div class="col-lg-4">
            <div class="card yg-card h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-stars me-2 text-yg"></i>Recent Winners</span>
                </div>
                <div class="card-body p-3">
                    @forelse ($recentWinners as $i => $win)
                        <div class="home-ranking-row d-flex align-items-center gap-3 p-2 {{ !$loop->first ? 'border-top' : '' }}">
                            <img src="{{ $win->user->avatar_url }}" alt="{{ $win->user->name }}" class="avatar avatar-sm object-fit-cover">
                            <div class="flex-grow-1 text-truncate">
                                <div class="fw-semibold small text-truncate">{{ $win->user->name }}</div>
                                <div class="text-secondary" style="font-size: .75rem;">{{ $win->created_at->diffForHumans() }}</div>
                            </div>
                            <span class="badge badge-gold rounded-pill">{{ $win->prize_label }}</span>
                        </div>
                    @empty
                        <p class="text-center text-secondary small py-4 mb-0">
                            <i class="bi bi-stars fs-4 d-block mb-1"></i>No wheel winners yet.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- New members --}}
        <div class="col-lg-4">
            <div class="card yg-card h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-person-plus me-2 text-yg"></i>New Members</span>
                    @if (auth()->user()->isOfficial())
                        <a href="{{ route('users.index') }}" class="small fw-semibold text-yg text-decoration-none">Manage <i class="bi bi-arrow-right"></i></a>
                    @endif
                </div>
                <div class="card-body p-3">
                    @forelse ($newMembers as $i => $member)
                        <div class="home-ranking-row d-flex align-items-center gap-3 p-2 {{ !$loop->first ? 'border-top' : '' }}">
                            <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}" class="avatar avatar-sm object-fit-cover">
                            <div class="flex-grow-1 text-truncate">
                                <div class="fw-semibold small text-truncate">
                                    {{ $member->name }}
                                    @if ($member->id === auth()->id())<span class="badge badge-yg rounded-pill ms-1">You</span>@endif
                                </div>
                                <div class="text-secondary" style="font-size: .75rem;">Joined {{ $member->created_at->diffForHumans() }}</div>
                            </div>
                            <span class="badge {{ $member->isOfficial() ? 'badge-gold' : 'badge-soft' }} rounded-pill">
                                <i class="bi {{ $member->isOfficial() ? 'bi-person-badge' : 'bi-house-heart' }} me-1"></i>{{ ucfirst($member->role) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-center text-secondary small py-4 mb-0">
                            <i class="bi bi-person-plus fs-4 d-block mb-1"></i>No members yet.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

    <div class="card yg-card mt-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <span><i class="bi bi-award me-2 text-yg"></i>Badges to earn</span>
            <a href="{{ route('badges.catalog') }}" class="small fw-semibold text-yg text-decoration-none">View all <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="card-body p-3">
            <div class="home-badges-track row g-3">
                @forelse ($badges as $badge)
                    @php($earned = $earnedBadgeIds->contains($badge->id))
                    <div class="col-6 col-md-3 home-badge-item">
                        <div class="card h-100 border-0 bg-body-secondary-subtle text-center {{ $earned ? '' : 'opacity-50' }}">
                            <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center">
                                <img src="{{ $badge->image_url }}" alt="{{ $badge->name }}" style="height:5rem;" class="object-fit-contain {{ $earned ? '' : 'grayscale' }}">
                                <div class="small fw-semibold mt-2">{{ $badge->name }}</div>
                                <div class="small text-secondary">{{ number_format($badge->points_required) }} pts</div>
                                @if ($earned)
                                    <span class="badge bg-success-subtle text-success-emphasis rounded-pill mt-2"><i class="bi bi-check-circle me-1"></i>Earned</span>
                                @else
                                    <span class="badge bg-light text-secondary rounded-pill mt-2">Locked</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center text-secondary small py-3">No badges are available yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card yg-card mt-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <span><i class="bi bi-megaphone me-2 text-yg"></i>Latest Announcements</span>
            <a href="{{ route('announcements') }}" class="small fw-semibold text-yg text-decoration-none">View all <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="card-body p-3">
            @forelse ($announcements as $announcement)
                <a href="{{ route('announcements.show', $announcement) }}" class="d-block text-decoration-none text-reset p-2 {{ !$loop->first ? 'border-top' : '' }}">
                    <div class="d-flex justify-content-between gap-3">
                        <span class="fw-semibold text-truncate">{{ $announcement->title }}</span>
                        <span class="small text-secondary text-nowrap">{{ $announcement->created_at->format('M j') }}</span>
                    </div>
                    <div class="small text-secondary">{{ Str::limit($announcement->body, 110) }}</div>
                    @if ($announcement->is_event)
                        <div class="small text-yg mt-1">
                            <i class="bi bi-calendar-event me-1"></i>{{ $announcement->event_start_at?->format('M j, Y g:i A') }}
                            @if ($announcement->isParticipationActivity())
                                &middot; {{ $announcement->participation_points }} participation points
                            @else
                                &middot; {{ $announcement->base_points }} base points
                            @endif
                        </div>
                    @endif
                </a>
            @empty
                <p class="text-center text-secondary small py-3 mb-0">No announcements yet.</p>
            @endforelse
        </div>
    </div>

@endsection

@push('styles')
<style>
    @media (max-width: 767.98px) {
        .home-badges-track { flex-wrap: nowrap; margin-inline: -.25rem; overflow-x: auto; padding: .25rem .25rem .75rem; scroll-snap-type: x mandatory; scrollbar-width: none; }
        .home-badges-track::-webkit-scrollbar { display: none; }
        .home-badge-item { flex: 0 0 calc(100% - 1.25rem); min-width: calc(100% - 1.25rem); padding-left: .5rem; padding-right: .5rem; scroll-snap-align: start; }
    }
</style>
@endpush
