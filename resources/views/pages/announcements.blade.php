@extends('layouts.app')

@section('title', 'Announcements')

@section('content')

<div class="announcement-page">
    @php
        $categoryStyles = [
            'events' => ['badge' => 'badge-soft', 'icon' => 'bi-calendar-event'],
            'updates' => ['badge' => 'badge-yg', 'icon' => 'bi-rocket-takeoff'],
            'rewards' => ['badge' => 'badge-gold', 'icon' => 'bi-gift'],
            'maintenance' => ['badge' => 'bg-secondary-subtle text-secondary', 'icon' => 'bi-tools'],
            'ice_breaker' => ['badge' => 'badge-gold', 'icon' => 'bi-lightbulb'],
            'q_and_a' => ['badge' => 'badge-yg', 'icon' => 'bi-question-circle'],
            'game' => ['badge' => 'badge-soft', 'icon' => 'bi-controller'],
            'intermission' => ['badge' => 'badge-gold', 'icon' => 'bi-music-note-beamed'],
        ];
    @endphp

    @php
        $isOfficial = auth()->user()?->canAccessOfficialFeatures() ?? false;
        $calendarData = $calendarAnnouncements->map(fn ($announcement) => [
            'id' => $announcement->id,
            'title' => $announcement->title,
            'category' => $announcement->category,
            'status' => $announcement->eventStatus() ?? 'none',
            'date' => ($announcement->event_start_at ?: $announcement->created_at)->toDateString(),
            'time' => $announcement->event_start_at?->format('g:i A'),
            'endTime' => $announcement->event_end_at?->format('g:i A'),
            'endDateLabel' => $announcement->event_end_at?->format('M j, Y'),
            'dateLabel' => ($announcement->event_start_at ?: $announcement->created_at)->format('M j, Y'),
            'description' => $announcement->body,
            'location' => $announcement->venue_name,
            'creator' => $announcement->creator?->full_name,
            'url' => route('announcements.show', $announcement),
        ])->values();
    @endphp

    <div class="page-header p-3 p-lg-4 mb-3">
        <div class="row align-items-center position-relative">
            <div class="col">
                <h2 class="h4 fw-bold mb-1"><i class="bi bi-megaphone-fill me-2"></i>{{ $showArchived ? 'Archived announcements' : 'Announcements' }}</h2>
                <p class="small mb-0 opacity-75">Stay up to date with community news, events, and important notices.</p>
            </div>
            <div class="col-auto">
                <span class="badge bg-dark rounded-pill px-2 py-1">
                    {{ $calendarAnnouncements->count() }} {{ Str::plural('announcement', $calendarAnnouncements->count()) }}
                </span>
            </div>
        </div>
    </div>

    @if ($isOfficial)
        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-4">
            <a href="{{ route('announcements', $showArchived ? [] : ['archived' => 1]) }}" class="btn btn-outline-secondary btn-sm rounded-3">
                <i class="bi {{ $showArchived ? 'bi-arrow-left' : 'bi-archive' }} me-1"></i>{{ $showArchived ? 'Back to announcements' : 'View archive' }}
            </a>
            @unless ($showArchived)
                <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                    <i class="bi bi-plus-circle me-2"></i>New Announcement
                </button>
            @endunless
        </div>
    @endif

    {{-- Featured announcement --}}
    @if ($featured && ! $showArchived)
        <div class="featured-banner position-relative p-4 p-lg-5 mb-4">
            <span class="deco" style="width: 260px; height: 260px; top: -100px; right: -70px;"></span>
            <span class="deco" style="width: 140px; height: 140px; bottom: -60px; right: 180px;"></span>
            <div class="row align-items-center position-relative" style="z-index: 1;">
                <div class="col-lg-8">
                    <span class="badge bg-white text-yg rounded-pill px-3 py-2 mb-3 fw-bold">
                        <i class="bi bi-pin-angle-fill me-1"></i>Featured
                    </span>
                    @if ($featured->eventStatus())
                        <span class="badge {{ $featured->eventStatus() === 'open' ? 'bg-success-subtle text-success-emphasis' : ($featured->eventStatus() === 'soon' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-secondary-subtle text-secondary') }} rounded-pill px-3 py-2 mb-3 ms-1">{{ ucfirst($featured->eventStatus()) }}</span>
                    @endif
                    <h2 class="fw-bold mb-2">
                        <a href="{{ route('announcements.show', $featured) }}" class="stretched-link text-white text-decoration-none">
                            {{ $featured->title }}
                        </a>
                    </h2>
                    <p class="mb-4 opacity-75" style="max-width: 34rem;">{{ Str::limit($featured->body, 180) }}</p>
                    <div class="d-flex align-items-center gap-3">
                        <span class="small fw-semibold opacity-75">
                            <i class="bi bi-calendar3 me-1"></i>{{ $featured->created_at->format('M j, Y') }}
                        </span>
                    </div>
                    @if ($isOfficial)
                        <div class="featured-announcement-actions mt-3 d-flex gap-2">
                            <button class="btn btn-light btn-sm fw-semibold rounded-3" data-bs-toggle="modal"
                                    data-bs-target="#editAnnouncementModal{{ $featured->id }}">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </button>
                            <form action="{{ route('announcements.destroy', $featured) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Delete this announcement?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-dark btn-sm fw-semibold rounded-3">
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
                <div class="col-lg-4 d-none d-lg-block text-center">
                    <i class="bi bi-megaphone-fill" style="font-size: 7rem; opacity: .5;"></i>
                </div>
            </div>
        </div>
    @endif

    
    {{-- Search + filters --}}
    <div class="card yg-card p-3 mb-2 announcement-controls">
        <div class="row g-3 align-items-center">
            <div class="col-lg-5">
                <div class="input-group">
                    <span class="input-group-text yg-addon"><i class="bi bi-search"></i></span>
                    <input type="search" id="announcementSearch" class="form-control yg-input"
                           placeholder="Search announcements..." aria-label="Search announcements">
                </div>
            </div>
            <div class="col-lg-7 d-flex justify-content-lg-end gap-2 flex-wrap">
                <div class="dropdown">
                    <button class="btn filter-dropdown-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            data-bs-auto-close="outside" aria-expanded="false" aria-label="Filter announcements">
                        <i class="bi bi-funnel me-2"></i><span id="announcementFilterLabel">All announcements</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-3 announcement-filter-menu">
                        <label for="announcementCategoryFilter" class="form-label small fw-semibold text-secondary mb-1">Category</label>
                        <select id="announcementCategoryFilter" class="form-select mb-3">
                            <option value="all">All categories</option>
                            <option value="events">Events</option>
                            <option value="updates">Updates</option>
                            <option value="rewards">Rewards</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="ice_breaker">Ice Breaker</option>
                            <option value="q_and_a">Q&amp;A</option>
                            <option value="game">Game</option>
                            <option value="intermission">Intermission</option>
                        </select>
                        <label for="announcementStatusFilter" class="form-label small fw-semibold text-secondary mb-1">Event status</label>
                        <select id="announcementStatusFilter" class="form-select">
                            <option value="all">All event status</option>
                            <option value="soon">Soon</option>
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-center mb-4">
        <div class="btn-group announcement-view-switch" role="group" aria-label="Announcement view">
            <button type="button" class="btn btn-outline-secondary active" id="cardsViewButton">
                <i class="bi bi-grid-3x3-gap me-1"></i>Cards
            </button>
            <button type="button" class="btn btn-outline-secondary" id="calendarViewButton">
                <i class="bi bi-calendar3 me-1"></i>Calendar
            </button>
        </div>
    </div>

    <div id="announcementCalendar" class="card yg-card p-3 p-lg-4 mb-4 d-none" aria-label="Announcements calendar">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button type="button" class="btn btn-light btn-sm" id="calendarPrevious" aria-label="Previous period"><i class="bi bi-chevron-left"></i></button>
            <h4 class="fw-bold mb-0" id="calendarMonthLabel"></h4>
            <button type="button" class="btn btn-light btn-sm" id="calendarNext" aria-label="Next period"><i class="bi bi-chevron-right"></i></button>
        </div>
        <div id="calendarMonthView">
          <div class="calendar-weekdays small fw-semibold text-secondary text-center">
            <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
          </div>
          <div id="calendarGrid" class="calendar-grid"></div>
        </div>
        <div class="calendar-selection mt-4" aria-live="polite">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                <h5 class="fw-bold mb-0" id="calendarSelectedDateLabel">Selected date</h5>
                <span class="small text-secondary" id="calendarSelectedCount"></span>
            </div>
            <div id="calendarSelectedEvents" class="calendar-agenda-list"></div>
        </div>
        <div class="mt-4 pt-4 border-top">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                <h5 class="fw-bold mb-0">Upcoming Events</h5>
                <span class="small text-secondary">Next scheduled announcements</span>
            </div>
            <div id="upcomingEvents" class="calendar-agenda-list"></div>
        </div>
    </div>

    <div class="modal fade" id="calendarEventModal" tabindex="-1" aria-labelledby="calendarEventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="calendarEventModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 pt-0">
                    <div class="calendar-modal-meta text-secondary small mb-3" id="calendarEventModalMeta"></div>
                    <p class="mb-0" id="calendarEventModalDescription"></p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <a class="btn btn-yg-outline btn-sm" id="calendarEventModalLink">View announcement <i class="bi bi-arrow-right ms-1"></i></a>
                    <button type="button" class="btn btn-light btn-sm rounded-3" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Announcement cards --}}
    <div class="mb-3" aria-label="Announcements">
    <div class="row g-4" id="announcementGrid">
        @forelse ($announcements as $announcement)
            @php $style = $categoryStyles[$announcement->category] ?? ['badge' => 'badge-soft', 'icon' => 'bi-megaphone']; @endphp
            <div class="col-md-6 col-xl-4 announcement-item" data-category="{{ $announcement->category }}" data-event-status="{{ $announcement->eventStatus() ?? 'none' }}">
                <div class="card yg-card yg-card-hover announcement-card h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge {{ $style['badge'] }} rounded-pill px-3">
                                <i class="bi {{ $style['icon'] }} me-1"></i>{{ ['ice_breaker' => 'Ice Breaker', 'q_and_a' => 'Q&A', 'intermission' => 'Intermission'][$announcement->category] ?? ucfirst($announcement->category) }}
                            </span>
                            <span class="small text-secondary">{{ $announcement->created_at->format('M j, Y') }}</span>
                        </div>
                        @if ($announcement->eventStatus())
                            <span class="badge {{ $announcement->eventStatus() === 'open' ? 'bg-success-subtle text-success-emphasis' : ($announcement->eventStatus() === 'soon' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-secondary-subtle text-secondary') }} rounded-pill align-self-start mb-2">
                                <i class="bi bi-circle-fill me-1" style="font-size:.45rem;vertical-align:middle;"></i>{{ ucfirst($announcement->eventStatus()) }}
                            </span>
                        @endif
                        <h5 class="fw-bold">
                            <a href="{{ route('announcements.show', $announcement) }}" class="stretched-link text-body text-decoration-none">
                                {{ $announcement->title }}
                            </a>
                        </h5>
                        <p class="text-secondary small flex-grow-1">{{ Str::limit($announcement->body, 140) }}</p>
                        @if ($announcement->is_event)
                            <div class="small text-secondary mb-3">
                                <div><i class="bi bi-calendar-event me-1 text-yg"></i>{{ $announcement->event_start_at?->format('M j, Y g:i A') }}</div>
                                <div><i class="bi bi-geo-alt me-1 text-yg"></i>{{ $announcement->venue_name ?: 'Barangay San Jose' }}</div>
                                <div class="fw-semibold text-yg mt-1">
                                    @if ($announcement->isParticipationActivity())
                                        {{ $announcement->participation_points }} participation points
                                    @else
                                        {{ $announcement->base_points }} base points + 10% early bonus
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div class="d-flex align-items-center gap-2">
                            @if ($isOfficial)
                                <div class="announcement-card-actions d-flex align-items-center gap-2 ms-auto">
                                <button class="btn btn-light btn-sm rounded-3" title="Edit" data-bs-toggle="modal"
                                        data-bs-target="#editAnnouncementModal{{ $announcement->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('announcements.destroy', $announcement) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete “{{ $announcement->title }}”?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-3" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @if ($announcement->is_archived)
                                    <form action="{{ route('announcements.restore', $announcement) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline-success btn-sm rounded-3" title="Restore announcement">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                                        </button>
                                    </form>
                                @elseif ($announcement->is_event && $announcement->eventStatus() === 'closed')
                                    <form action="{{ route('announcements.archive', $announcement) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Archive this ended event?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline-secondary btn-sm rounded-3" title="Archive ended event">
                                            <i class="bi bi-archive me-1"></i>Archive
                                        </button>
                                    </form>
                                @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 announcement-item announcement-empty">
                <div class="card yg-card text-center p-5">
                    <i class="bi bi-megaphone text-secondary" style="font-size: 3.5rem;"></i>
                    <h4 class="fw-bold mt-3">No announcements yet</h4>
                    <p class="text-secondary mb-0">Check back soon for news and updates.</p>
                </div>
            </div>
        @endforelse
      </div>
    </div>

    <p id="noResults" class="text-center text-secondary py-5 d-none">
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>No announcements match your search.
    </p>

    {{-- Pagination --}}
    @if ($announcements->hasPages())
        <nav aria-label="Announcements pagination">
            <ul class="pagination justify-content-center">
                <li class="page-item {{ $announcements->onFirstPage() ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $announcements->previousPageUrl() ?? '#' }}" aria-label="Previous"><i class="bi bi-chevron-left"></i></a>
                </li>
                @foreach ($announcements->getUrlRange(1, $announcements->lastPage()) as $page => $url)
                    <li class="page-item {{ $page === $announcements->currentPage() ? 'active' : '' }}">
                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                    </li>
                @endforeach
                <li class="page-item {{ $announcements->hasMorePages() ? '' : 'disabled' }}">
                    <a class="page-link" href="{{ $announcements->nextPageUrl() ?? '#' }}" aria-label="Next"><i class="bi bi-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
    @endif

    {{-- Read-more modals (everyone) --}}
    @foreach (collect([$featured])->filter()->concat($announcements) as $item)
        @php $style = $categoryStyles[$item->category] ?? ['badge' => 'badge-soft', 'icon' => 'bi-megaphone']; @endphp
        <div class="modal fade" id="viewAnnouncementModal{{ $item->id }}" tabindex="-1"
             aria-labelledby="viewAnnouncementLabel{{ $item->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content border-0" style="border-radius: 20px;">
                    <div class="modal-header border-0 pb-2">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge {{ $style['badge'] }} rounded-pill px-3">
                                    <i class="bi {{ $style['icon'] }} me-1"></i>{{ ['ice_breaker' => 'Ice Breaker', 'q_and_a' => 'Q&A', 'intermission' => 'Intermission'][$item->category] ?? ucfirst($item->category) }}
                                </span>
                                @if ($item->is_featured)
                                    <span class="badge badge-gold rounded-pill px-3"><i class="bi bi-pin-angle-fill me-1"></i>Featured</span>
                                @endif
                                <span class="small text-secondary">
                                    <i class="bi bi-calendar3 me-1"></i>{{ $item->created_at->format('F j, Y · g:i A') }}
                                </span>
                            </div>
                            <h4 class="modal-title fw-bold" id="viewAnnouncementLabel{{ $item->id }}">{{ $item->title }}</h4>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4 pb-4">
                        @include('pages.partials.announcement-details', ['announcement' => $item])
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-yg px-4" data-bs-dismiss="modal">
                            <i class="bi bi-check-circle me-2"></i>Got it
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @if ($isOfficial)
        @if ($errors->any())
            <div class="alert alert-danger rounded-3 mt-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $errors->first() }}
            </div>
        @endif

        {{-- Add announcement modal --}}
        <div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-labelledby="addAnnouncementLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0" style="border-radius: 20px;">
                    <form action="{{ route('announcements.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold" id="addAnnouncementLabel"><i class="bi bi-plus-circle me-2 text-yg"></i>New Announcement</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            @include('pages.partials.announcement-fields', ['announcement' => null, 'suffix' => 'add'])
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light fw-semibold rounded-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-yg"><i class="bi bi-megaphone me-2"></i>Publish</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Edit announcement modals --}}
        @foreach (collect([$featured])->filter()->concat($announcements) as $item)
            <div class="modal fade" id="editAnnouncementModal{{ $item->id }}" tabindex="-1"
                 aria-labelledby="editAnnouncementLabel{{ $item->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0" style="border-radius: 20px;">
                        <form action="{{ route('announcements.update', $item) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold" id="editAnnouncementLabel{{ $item->id }}"><i class="bi bi-pencil-square me-2 text-yg"></i>Edit Announcement</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                @include('pages.partials.announcement-fields', ['announcement' => $item, 'suffix' => $item->id])
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-light fw-semibold rounded-3" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-yg"><i class="bi bi-check-circle me-2"></i>Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

</div>

@endsection

@push('styles')
<style>
    .announcement-item > .card { min-height: 100%; }
    .announcement-card { position: relative; }
    .announcement-card-actions { position: relative; z-index: 2; }
    .featured-announcement-actions { position: relative; z-index: 2; }
    .filter-dropdown-toggle { background: #fff; border: 2px solid var(--yg-primary); color: var(--yg-secondary); font-weight: 600; border-radius: 50rem; padding: .45rem 1.15rem; }
    .filter-dropdown-toggle:hover, .filter-dropdown-toggle:focus { background: var(--yg-primary); color: var(--yg-ink); }
    .announcement-filter-menu { min-width: 15rem; border: 0; border-radius: 1rem; box-shadow: 0 10px 30px rgba(52, 58, 64, .14); }
    .announcement-view-switch .btn.active { background: var(--yg-primary); border-color: var(--yg-primary); color: var(--yg-ink); }
    @media (max-width: 767.98px) {
        .announcement-controls .row { --bs-gutter-x: .5rem; flex-wrap: nowrap; gap: .5rem !important; }
        .announcement-controls .row > .col-lg-5 { flex: 1 1 auto; width: auto; min-width: 0; }
        .announcement-controls .row > .col-lg-7 { flex: 0 0 auto; width: auto; padding: 0; }
        .announcement-controls .announcement-filter-menu { right: 0; left: auto; }
        .announcement-controls .filter-dropdown-toggle { width: 2.75rem; height: 2.75rem; padding: 0; }
        .announcement-controls .filter-dropdown-toggle .bi-funnel { margin: 0 !important; }
        .announcement-controls .filter-dropdown-toggle #announcementFilterLabel,
        .announcement-controls .filter-dropdown-toggle::after { display: none; }
        .announcement-controls .filter-dropdown-toggle { font-size: 0; }
        .announcement-controls .filter-dropdown-toggle .bi-funnel { font-size: 1rem; }
    }
    #announcementCalendar { min-width: 0; overflow: hidden; }
    .calendar-weekdays, .calendar-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .45rem; min-width: 0; }
    .calendar-weekdays { margin-bottom: .45rem; }
    .calendar-grid { grid-auto-rows: minmax(5rem, auto); }
    .calendar-day { position: relative; min-width: 0; min-height: 5rem; background: #fff; border: 1px solid rgba(52, 58, 64, .12); border-radius: .6rem; padding: .4rem; overflow: hidden; }
    .calendar-day.is-selected { border-color: var(--yg-primary); background: rgba(245, 248, 229, .55); box-shadow: 0 0 0 2px rgba(112, 144, 30, .18); }
    .calendar-day.is-today .calendar-day-number { display: inline-flex; align-items: center; justify-content: center; min-width: 1.55rem; height: 1.55rem; border-radius: 50%; background: var(--yg-primary); color: var(--yg-ink); }
    .calendar-day-button { position: absolute; inset: 0; z-index: 0; display: block; width: 100%; height: 100%; border: 0; padding: 0; background: transparent; text-align: left; color: inherit; cursor: pointer; }
    .calendar-day-button:focus-visible { outline: 2px solid var(--yg-secondary); outline-offset: -2px; border-radius: inherit; }
    .calendar-day.is-past .calendar-day-number { color: #8a9197; }
    .calendar-day.is-other-month { background: #fafafa; }
    .calendar-day.is-other-month .calendar-day-number { color: #adb3b7; }
    .calendar-day-number { position: absolute; top: .35rem; left: .4rem; font-size: .8rem; font-weight: 700; color: var(--yg-secondary); pointer-events: none; }
    .calendar-day-events { position: relative; z-index: 1; margin-top: 1.8rem; }
    .calendar-day-events-compact { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .04rem; }
    .calendar-entry { position: relative; z-index: 1; display: block; width: 100%; min-width: 0; margin-top: .35rem; padding: .3rem .4rem; border: 0; border-left: 3px solid var(--calendar-event-color, #6c757d); border-radius: .25rem; background: var(--calendar-event-bg, #f1f3f5); color: var(--calendar-event-text, #343a40); font-size: .75rem; line-height: 1.2; text-align: left; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .calendar-entry:hover, .calendar-entry:focus { filter: brightness(.96); outline: 2px solid rgba(52, 58, 64, .14); }
    .calendar-entry::before { content: ''; display: inline-block; width: .42rem; height: .42rem; margin: 0 .3rem .05rem 0; border-radius: 50%; background: var(--calendar-event-color, #6c757d); }
    .calendar-entry-dot { position: relative; display: inline-block; width: 1rem; height: 1rem; margin: .12rem .08rem 0; padding: 0; border: 0; border-radius: 50%; background: transparent; font-size: 0; vertical-align: top; }
    .calendar-entry-dot::before { position: absolute; top: 50%; left: 50%; width: .48rem; height: .48rem; margin: 0; transform: translate(-50%, -50%); }
    .calendar-entry-dot:hover, .calendar-entry-dot:focus { background: color-mix(in srgb, var(--calendar-event-color) 16%, transparent); outline: 2px solid color-mix(in srgb, var(--calendar-event-color) 40%, transparent); }
    .calendar-more { position: relative; z-index: 1; display: block; margin-top: .25rem; border: 0; background: transparent; color: var(--yg-secondary); font-size: .7rem; font-weight: 700; padding: 0; }
    .calendar-day-events-compact .calendar-more { flex-basis: 100%; margin-top: 0; text-align: center; }
    .calendar-more:hover, .calendar-more:focus { color: var(--yg-ink); text-decoration: underline; }
    .calendar-agenda-list { display: grid; gap: .55rem; }
    .calendar-agenda-item { display: flex; align-items: center; justify-content: space-between; gap: 1rem; width: 100%; border: 1px solid rgba(52, 58, 64, .12); border-radius: .6rem; padding: .65rem .8rem; background: #fff; color: inherit; text-align: left; }
    button.calendar-agenda-item { cursor: pointer; }
    .calendar-agenda-item:hover, .calendar-agenda-item:focus { border-color: var(--yg-primary); background: #fff; }
    .calendar-agenda-item-title { min-width: 0; overflow-wrap: anywhere; }
    .calendar-agenda-empty { color: var(--yg-secondary); font-size: .9rem; }
    @media (max-width: 991.98px) {
        #announcementCalendar { padding: .75rem !important; }
        .calendar-weekdays, .calendar-grid { gap: .25rem; }
        .calendar-grid { grid-auto-rows: minmax(3.7rem, auto); }
        .calendar-day { min-height: 3.7rem; padding: .3rem; border-radius: .4rem; }
        .calendar-day-number { font-size: .72rem; }
        .calendar-day-events { margin-top: 1.7rem; }
        .calendar-selection { margin-top: 1rem !important; }
    }
    @media (max-width: 767.98px) {
        .calendar-weekdays { font-size: .68rem; }
    }
    @media (max-width: 575.98px) {
        #announcementCalendar { padding: .5rem !important; }
        .calendar-weekdays, .calendar-grid { gap: .14rem; }
        .calendar-weekdays { font-size: .62rem; margin-bottom: .14rem; }
        .calendar-grid { grid-auto-rows: minmax(3.1rem, auto); }
        .calendar-day { min-height: 3.1rem; padding: .16rem; border-radius: .3rem; }
        .calendar-day-number { font-size: .68rem; }
        .calendar-day-events { margin-top: 1.7rem; }
        .calendar-entry-dot { width: .8rem; height: .8rem; margin: .1rem .04rem 0; }
        .calendar-entry-dot::before { width: .4rem; height: .4rem; }
        .calendar-more { font-size: .6rem; margin-top: .1rem; }
        #calendarMonthLabel { font-size: 1rem; }
        #calendarPrevious, #calendarNext { padding: .2rem .4rem; font-size: .7rem; }
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const search = document.getElementById('announcementSearch');
        const categoryFilter = document.getElementById('announcementCategoryFilter');
        const statusFilter = document.getElementById('announcementStatusFilter');
        const filterLabel = document.getElementById('announcementFilterLabel');
        const items = document.querySelectorAll('.announcement-item');
        const noResults = document.getElementById('noResults');
        const announcementGrid = document.getElementById('announcementGrid');
        const announcementCalendar = document.getElementById('announcementCalendar');
        const cardsViewButton = document.getElementById('cardsViewButton');
        const calendarViewButton = document.getElementById('calendarViewButton');
        const calendarGrid = document.getElementById('calendarGrid');
        const calendarMonthView = document.getElementById('calendarMonthView');
        const calendarMonthLabel = document.getElementById('calendarMonthLabel');
        const calendarData = @json($calendarData);
        const compactCalendarQuery = window.matchMedia('(max-width: 991.98px)');
        let activeFilter = 'all';
        let activeStatus = 'all';
        let activeView = 'cards';
        let calendarDate = new Date();
        let selectedDate = new Date();

        function applyFilters() {
            const term = search.value.trim().toLowerCase();
            let visible = 0;
            items.forEach(item => {
                const matchesCategory = activeFilter === 'all' || item.dataset.category === activeFilter;
                const matchesStatus = activeStatus === 'all' || item.dataset.eventStatus === activeStatus;
                const matchesTerm = !term || item.textContent.toLowerCase().includes(term);
                const show = matchesCategory && matchesStatus && matchesTerm;
                item.classList.toggle('d-none', !show);
                if (show) visible++;
            });
            if (items.length > 0) noResults.classList.toggle('d-none', visible > 0);
        }

        function calendarMatches(item) {
            const term = search.value.trim().toLowerCase();
            return (activeFilter === 'all' || item.category === activeFilter)
                && (activeStatus === 'all' || item.status === activeStatus)
                && (!term || item.title.toLowerCase().includes(term) || item.category.toLowerCase().includes(term));
        }

            function toDateKey(date) {
                return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
            }

        function renderCalendar() {
            const year = calendarDate.getFullYear();
            const month = calendarDate.getMonth();
            announcementCalendar.classList.toggle('calendar-compact', compactCalendarQuery.matches);
            calendarMonthLabel.textContent = calendarDate.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const previousMonthDays = new Date(year, month, 0).getDate();
            const cellCount = Math.ceil((firstDay + daysInMonth) / 7) * 7;
            calendarGrid.innerHTML = '';
            for (let index = 0; index < cellCount; index++) {
                const dayOffset = index - firstDay + 1;
                const cellDate = new Date(year, month, dayOffset);
                const isOtherMonth = dayOffset < 1 || dayOffset > daysInMonth;
                const cell = document.createElement('div');
                const dateKey = toDateKey(cellDate);
                const todayKey = toDateKey(new Date());
                const events = calendarData.filter(item => item.date === dateKey && calendarMatches(item));
                cell.className = `calendar-day${isOtherMonth ? ' is-other-month' : ''}${dateKey < todayKey ? ' is-past' : ''}${dateKey === toDateKey(selectedDate) ? ' is-selected' : ''}${dateKey === todayKey ? ' is-today' : ''}`;
                const dayNumber = document.createElement('div');
                dayNumber.className = 'calendar-day-number';
                dayNumber.textContent = isOtherMonth && dayOffset < 1 ? previousMonthDays + dayOffset : (isOtherMonth ? dayOffset - daysInMonth : dayOffset);
                const dayButton = document.createElement('button');
                dayButton.type = 'button';
                dayButton.className = 'calendar-day-button';
                dayButton.setAttribute('aria-label', `Select ${cellDate.toLocaleDateString(undefined, { dateStyle: 'long' })}`);
                dayButton.appendChild(dayNumber);
                dayButton.addEventListener('click', () => selectDate(cellDate));
                cell.appendChild(dayButton);
                const visibleEvents = events.slice(0, compactCalendarQuery.matches ? 5 : 3);
                const eventMarkers = document.createElement('div');
                eventMarkers.className = `calendar-day-events${compactCalendarQuery.matches ? ' calendar-day-events-compact' : ''}`;
                visibleEvents.forEach(item => {
                    const link = document.createElement('button');
                    link.type = 'button';
                    link.className = `calendar-entry${compactCalendarQuery.matches ? ' calendar-entry-dot' : ''}`;
                    const eventStyle = getEventStyle(item);
                    link.style.setProperty('--calendar-event-color', eventStyle.color);
                    link.style.setProperty('--calendar-event-bg', eventStyle.background);
                    link.style.setProperty('--calendar-event-text', eventStyle.text);
                    link.title = `${eventStyle.label}: ${item.title}`;
                    link.setAttribute('aria-label', `${item.title} on ${cellDate.toLocaleDateString(undefined, { dateStyle: 'long' })}`);
                    link.textContent = item.title;
                    link.addEventListener('click', () => compactCalendarQuery.matches ? selectDate(cellDate) : showEventDetails(item));
                    eventMarkers.appendChild(link);
                });
                if (events.length > visibleEvents.length) {
                    const more = document.createElement('button');
                    more.type = 'button';
                    more.className = 'calendar-more';
                    more.textContent = `+${events.length - visibleEvents.length}`;
                    more.addEventListener('click', () => selectDate(cellDate));
                    eventMarkers.appendChild(more);
                }
                if (events.length) cell.appendChild(eventMarkers);
                calendarGrid.appendChild(cell);
            }
        }

        function getEventStyle(item) {
            const styles = {
                events: { color: '#2f6f9f', background: '#eaf3f9', text: '#214d70', label: 'General event' },
                game: { color: '#3f8057', background: '#edf7ef', text: '#2b5b3d', label: 'Community activity' },
                ice_breaker: { color: '#b4771e', background: '#fff5df', text: '#765016', label: 'TalaFair activity' },
                rewards: { color: '#b4771e', background: '#fff5df', text: '#765016', label: 'Raffle or rewards' },
                q_and_a: { color: '#76559b', background: '#f3edfa', text: '#513a6d', label: 'Important activity' },
                maintenance: { color: '#b14b4b', background: '#fbeeee', text: '#7d3333', label: 'Urgent activity' },
            };
            return styles[item.category] || { color: '#2f6f9f', background: '#eaf3f9', text: '#214d70', label: 'General announcement' };
        }

        function displayAgendaItem(item) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'calendar-agenda-item';
            button.innerHTML = `<span class="calendar-agenda-item-title"><strong>${item.title}</strong><span class="d-block small text-secondary">${item.dateLabel}</span></span><span class="small text-secondary text-nowrap">${item.time || 'All day'}</span>`;
            button.addEventListener('click', () => showEventDetails(item));
            return button;
        }

        function renderAgenda() {
            const selectedItems = calendarData.filter(item => item.date === toDateKey(selectedDate) && calendarMatches(item));
            const selectedList = document.getElementById('calendarSelectedEvents');
            const upcomingList = document.getElementById('upcomingEvents');
            selectedList.innerHTML = '';
            upcomingList.innerHTML = '';
            document.getElementById('calendarSelectedDateLabel').textContent = selectedDate.toLocaleDateString(undefined, { dateStyle: 'full' });
            document.getElementById('calendarSelectedCount').textContent = `${selectedItems.length} item${selectedItems.length === 1 ? '' : 's'}`;
            if (!selectedItems.length) selectedList.innerHTML = '<div class="calendar-agenda-empty">No announcements scheduled for this date.</div>';
            selectedItems.forEach(item => selectedList.appendChild(displayAgendaItem(item)));
            const upcomingItems = calendarData.filter(item => item.date >= toDateKey(new Date()) && calendarMatches(item))
                .sort((first, second) => `${first.date} ${first.time || ''}`.localeCompare(`${second.date} ${second.time || ''}`)).slice(0, 8);
            if (!upcomingItems.length) upcomingList.innerHTML = '<div class="calendar-agenda-empty">No upcoming announcements.</div>';
            upcomingItems.forEach(item => upcomingList.appendChild(displayAgendaItem(item)));
        }

        function selectDate(date) {
            selectedDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());
            calendarDate = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
            renderCalendar();
            renderAgenda();
        }

        if (compactCalendarQuery.addEventListener) compactCalendarQuery.addEventListener('change', renderCalendar);
        else compactCalendarQuery.addListener(renderCalendar);

        function showEventDetails(item) {
            document.getElementById('calendarEventModalLabel').textContent = item.title;
            const endDate = item.endDateLabel && item.endDateLabel !== item.dateLabel ? `${item.endDateLabel} ` : '';
            document.getElementById('calendarEventModalMeta').innerHTML = `<div><i class="bi bi-calendar-event me-2 text-yg"></i>${item.dateLabel}${item.time ? ` · ${item.time}${item.endTime ? ` - ${endDate}${item.endTime}` : ''}` : ''}</div>${item.location ? `<div><i class="bi bi-geo-alt me-2 text-yg"></i>${item.location}</div>` : ''}${item.creator ? `<div><i class="bi bi-person me-2 text-yg"></i>${item.creator}</div>` : ''}`;
            document.getElementById('calendarEventModalDescription').textContent = item.description || 'No description provided.';
            document.getElementById('calendarEventModalLink').href = item.url;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('calendarEventModal')).show();
        }

        function setView(view) {
            activeView = view;
            announcementGrid.closest('.announcement-page > .mb-3').classList.toggle('d-none', view !== 'cards');
            noResults.classList.toggle('d-none', view !== 'cards');
            announcementCalendar.classList.toggle('d-none', view !== 'calendar');
            cardsViewButton.classList.toggle('active', view === 'cards');
            calendarViewButton.classList.toggle('active', view === 'calendar');
            if (view === 'calendar') renderCalendar();
        }

        function updateFilterLabel() {
            const categoryLabel = categoryFilter.options[categoryFilter.selectedIndex].text;
            const statusLabel = statusFilter.options[statusFilter.selectedIndex].text;
            filterLabel.textContent = activeFilter === 'all' && activeStatus === 'all'
                ? 'All announcements'
                : activeFilter !== 'all' && activeStatus !== 'all'
                    ? `${categoryLabel} · ${statusLabel}`
                    : activeFilter !== 'all' ? categoryLabel : statusLabel;
        }

        categoryFilter.addEventListener('change', () => {
            activeFilter = categoryFilter.value;
            updateFilterLabel();
            applyFilters();
            if (activeView === 'calendar') { renderCalendar(); renderAgenda(); }
        });

        statusFilter.addEventListener('change', () => {
            activeStatus = statusFilter.value;
            updateFilterLabel();
            applyFilters();
            if (activeView === 'calendar') { renderCalendar(); renderAgenda(); }
        });

            search.addEventListener('input', () => {
                applyFilters();
                if (activeView === 'calendar') { renderCalendar(); renderAgenda(); }
            });
            cardsViewButton.addEventListener('click', () => setView('cards'));
            calendarViewButton.addEventListener('click', () => setView('calendar'));
            document.getElementById('calendarPrevious').addEventListener('click', () => {
                calendarDate.setMonth(calendarDate.getMonth() - 1);
                renderCalendar();
                renderAgenda();
            });
            document.getElementById('calendarNext').addEventListener('click', () => {
                calendarDate.setMonth(calendarDate.getMonth() + 1);
                renderCalendar();
                renderAgenda();
            });
            setView(activeView);
            renderAgenda();
        })();

        </script>
        @endpush

        {{-- Geolocation for the event venue pin (add + edit modals) --}}
        @push('scripts')
        <script>
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-use-my-location');
            if (! btn) return;

            if (! navigator.geolocation) {
                alert('Your browser does not support location.');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function (p) {
                    document.getElementById(btn.dataset.lat).value = p.coords.latitude.toFixed(7);
                    document.getElementById(btn.dataset.lng).value = p.coords.longitude.toFixed(7);
                },
                function () {
                    alert('Could not read your location. Allow location access and try again.');
                }
            );
        });
        </script>
        @endpush
