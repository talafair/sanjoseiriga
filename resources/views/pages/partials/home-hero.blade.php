<section class="page-header p-4 p-lg-5 mb-4 position-relative overflow-hidden rounded-3"
         @if ($background) style="background-image:url('{{ $background }}');background-size:cover;background-position:center;" @endif>

  @if ($background)
    <div class="position-absolute top-0 start-0 w-100 h-100" style="background:rgba(15,15,15,.55);"></div>
  @endif

  <div class="position-relative" style="z-index: 1;">
    <div class="small text-uppercase opacity-75" style="letter-spacing:.12em;">
      Barangay San Jose &middot; Iriga City
    </div>
    <h2 class="fw-bold mt-1 mb-1">
      Kumusta, {{ auth()->user()->first_name ?? auth()->user()->name }}
    </h2>
    <p class="mb-4 opacity-75">{{ number_format(auth()->user()->points) }} points earned</p>

    <div class="position-absolute top-0 end-0 d-none d-sm-flex gap-2">
      <span class="badge bg-white text-yg rounded-pill px-3 py-2 fs-6">
        <i class="bi {{ in_array(auth()->user()->role, ['official', 'superadmin'], true) ? 'bi-person-badge' : 'bi-house-heart' }} me-1"></i>{{ ucfirst(auth()->user()->role) }}
      </span>
      <span class="badge {{ auth()->user()->is_verified ? 'text-bg-success' : 'text-bg-danger' }} rounded-pill px-3 py-2 fs-6">
        <i class="bi {{ auth()->user()->is_verified ? 'bi-shield-check' : 'bi-shield-x' }} me-1"></i>{{ auth()->user()->is_verified ? 'Verified account' : 'Unverified account' }}
      </span>
    </div>

    <div class="d-flex flex-wrap gap-2">
      @if (auth()->user()->isOfficial() || auth()->user()->is_verified)
        <a href="{{ route('attendance.scanner') }}" class="btn btn-light fw-semibold">
          <i class="bi bi-qr-code-scan me-1"></i>Scan attendance
          @if ($scanNow > 0)
            <span class="badge rounded-pill text-bg-success ms-1">{{ $scanNow }} open</span>
          @endif
        </a>
      @else
        <span class="btn btn-light fw-semibold disabled" title="Your account is waiting for verification">
          <i class="bi bi-hourglass-split me-1"></i>Verification pending
        </span>
      @endif
      @unless (auth()->user()->isSuperadmin())
        <a href="{{ route('id-card.show') }}" class="btn btn-outline-light fw-semibold">
          <i class="bi bi-person-badge me-1"></i>My resident ID
        </a>
      @endunless
      @if (auth()->user()->isOfficial())
        <a href="{{ route('badges.index') }}" class="btn btn-outline-light fw-semibold">
          <i class="bi bi-award me-1"></i>Manage badges
        </a>
      @endif
    </div>
  </div>
</section>

{{-- Announcement banners --}}
@if ($featured || $banners->isNotEmpty())
  <section class="mb-4">
    <div class="d-flex justify-content-between align-items-baseline mb-3">
      <h5 class="fw-bold mb-0"><i class="bi bi-megaphone me-1 text-yg"></i>Announcements</h5>
      <a href="{{ route('announcements') }}" class="small fw-semibold text-yg text-decoration-none">
        More announcements <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>

    <div id="homeAnnouncementsCarousel" class="carousel slide" data-bs-ride="carousel">
      <div class="carousel-inner">
        @foreach ($displayBanners->chunk(3) as $slide)
          <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
            <div class="row g-3">
              @foreach ($slide as $banner)
                <div class="col-md-4">
                  <a href="{{ route('announcements.show', $banner) }}" class="card yg-card yg-card-hover h-100 text-decoration-none text-body {{ $banner->is_featured ? 'border border-2 border-warning' : '' }}">
                    @if ($banner->banner_url)
                      <img src="{{ $banner->banner_url }}" alt="" class="card-img-top object-fit-cover" style="height:7rem;">
                    @else
                      <div class="card-img-top d-flex align-items-center justify-content-center bg-body-secondary" style="height:7rem;"><span class="small fw-bold text-uppercase text-secondary">{{ $banner->category }}</span></div>
                    @endif
                    <div class="card-body p-3">
                      <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                        <div class="small text-uppercase fw-bold text-yg">{{ $banner->is_event ? 'Event' : (['ice_breaker' => 'Ice Breaker', 'q_and_a' => 'Q&A', 'intermission' => 'Intermission'][$banner->category] ?? ucfirst($banner->category)) }}</div>
                        @if ($banner->is_featured)
                          <span class="badge badge-gold rounded-pill"><i class="bi bi-pin-angle-fill me-1"></i>Featured</span>
                        @endif
                      </div>
                      @if ($banner->eventStatus())
                        <span class="badge {{ $banner->eventStatus() === 'open' ? 'bg-success-subtle text-success-emphasis' : ($banner->eventStatus() === 'soon' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-secondary-subtle text-secondary') }} rounded-pill mb-1">{{ ucfirst($banner->eventStatus()) }}</span>
                      @endif
                      <div class="fw-semibold text-truncate">{{ $banner->title }}</div>
                      @if ($banner->is_event && $banner->event_start_at)
                        <div class="small fw-semibold text-yg mt-1"><i class="bi bi-calendar-event me-1"></i>{{ $banner->event_start_at->format('M j, Y') }}</div>
                      @endif
                      <p class="small text-secondary mt-2 mb-0">{{ Str::limit($banner->body, 90) }}</p>
                    </div>
                  </a>
                </div>
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
      @if ($displayBanners->chunk(3)->count() > 1)
        <button class="carousel-control-prev" type="button" data-bs-target="#homeAnnouncementsCarousel" data-bs-slide="prev" aria-label="Previous announcement"><span class="carousel-control-prev-icon" aria-hidden="true"></span></button>
        <button class="carousel-control-next" type="button" data-bs-target="#homeAnnouncementsCarousel" data-bs-slide="next" aria-label="Next announcement"><span class="carousel-control-next-icon" aria-hidden="true"></span></button>
      @endif
    </div>

    <div class="home-announcements-mobile">
      @foreach ($displayBanners as $banner)
        <a href="{{ route('announcements.show', $banner) }}" class="card yg-card yg-card-hover text-decoration-none text-body {{ $banner->is_featured ? 'border border-2 border-warning' : '' }}">
          @if ($banner->banner_url)
            <img src="{{ $banner->banner_url }}" alt="" class="card-img-top object-fit-cover" style="height:10rem;">
          @else
            <div class="card-img-top d-flex align-items-center justify-content-center bg-body-secondary" style="height:10rem;"><span class="small fw-bold text-uppercase text-secondary">{{ $banner->category }}</span></div>
          @endif
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
              <span class="badge {{ $banner->is_event ? 'badge-soft' : 'badge-yg' }} rounded-pill px-3"><i class="bi {{ $banner->is_event ? 'bi-calendar-event' : 'bi-megaphone' }} me-1"></i>{{ $banner->is_event ? 'Event' : ucfirst($banner->category) }}</span>
              @if ($banner->is_featured)<span class="badge badge-gold rounded-pill"><i class="bi bi-pin-angle-fill me-1"></i>Featured</span>@endif
            </div>
            @if ($banner->eventStatus())
              <span class="badge {{ $banner->eventStatus() === 'open' ? 'bg-success-subtle text-success-emphasis' : ($banner->eventStatus() === 'soon' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-secondary-subtle text-secondary') }} rounded-pill mb-2 align-self-start">{{ ucfirst($banner->eventStatus()) }}</span>
            @endif
            <h5 class="fw-bold">{{ $banner->title }}</h5>
            <p class="text-secondary small mb-3">{{ Str::limit($banner->body, 140) }}</p>
            @if ($banner->is_event && $banner->event_start_at)
              <div class="small text-secondary"><i class="bi bi-calendar-event me-1 text-yg"></i>{{ $banner->event_start_at->format('M j, Y g:i A') }}</div>
              <div class="small text-secondary"><i class="bi bi-geo-alt me-1 text-yg"></i>{{ $banner->venue_name ?: 'Barangay San Jose' }}</div>
            @endif
          </div>
        </a>
      @endforeach
    </div>
  </section>
@endif

@push('styles')
<style>
  .home-announcements-mobile { display: none; }
  @media (max-width: 767.98px) {
    #homeAnnouncementsCarousel { display: none; }
    .home-announcements-mobile { display: flex; gap: 1rem; margin-inline: -.25rem; overflow-x: auto; padding: .25rem .25rem 1rem; scroll-snap-type: x mandatory; scrollbar-width: none; }
    .home-announcements-mobile::-webkit-scrollbar { display: none; }
    .home-announcements-mobile > .card { flex: 0 0 calc(100% - 1.25rem); min-width: calc(100% - 1.25rem); scroll-snap-align: start; }
  }
</style>
@endpush
