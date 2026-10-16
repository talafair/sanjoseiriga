@extends('layouts.app')

@section('title', 'Attendance Statistics')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-9">

    <a href="{{ route('announcements.show', $announcement) }}" class="small link-secondary">
      <i class="bi bi-arrow-left me-1"></i>{{ $announcement->title }}
    </a>
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-4">
      <h4 class="fw-bold mt-1 mb-0">Attendance statistics</h4>
      @if ($canViewDetails)
        <div class="dropdown">
          <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-file-earmark-arrow-down me-1"></i>Export reports
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li><h6 class="dropdown-header">Announcement statistics</h6></li>
            <li><a class="dropdown-item" href="{{ route('announcements.summary.download', $announcement) }}"><i class="bi bi-download me-2"></i>Download summary PDF</a></li>
            <li><a class="dropdown-item" target="_blank" rel="noopener" href="{{ route('announcements.summary.print', $announcement) }}"><i class="bi bi-printer me-2"></i>Print summary</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header">Event attendance sheet</h6></li>
            <li><a class="dropdown-item" href="{{ route('announcements.attendance.download', $announcement) }}"><i class="bi bi-download me-2"></i>Download attendance PDF</a></li>
            <li><a class="dropdown-item" target="_blank" rel="noopener" href="{{ route('announcements.attendance.print', $announcement) }}"><i class="bi bi-printer me-2"></i>Print attendance sheet</a></li>
          </ul>
        </div>
      @endif
    </div>

    <div class="row g-3 mb-4">
      @foreach ([
        'Audience notified' => $stats['audience'],
        'Confirmed yes'     => $stats['attending'],
        'Said no'           => $stats['not_attending'],
        'No response'       => $stats['no_response'],
        'Residents present' => $stats['resident_scanned'],
        'Officials present' => $stats['official_scanned'],
        'Total checked in'  => $stats['scanned'],
        'Early check-ins'   => $stats['early'],
      ] as $label => $value)
        <div class="col-6 col-md-4">
          <div class="card yg-card h-100">
            <div class="card-body py-3">
              <div class="h4 fw-bold mb-0">{{ number_format($value) }}</div>
              <div class="small text-uppercase text-secondary">{{ $label }}</div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <p>Turnout against confirmations: <span class="fw-bold text-yg">{{ $stats['turnout_rate'] }}%</span></p>

    @if ($canViewDetails)
      <div class="card yg-card overflow-hidden mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center gap-3 flex-wrap">
          <span class="fw-bold">Check-in log</span>
          <form method="GET" class="d-flex gap-2">
            <label for="attendanceUserName" class="visually-hidden">Filter by user name</label>
            <input id="attendanceUserName" name="user_name" value="{{ $userName }}" class="form-control form-control-sm" placeholder="Search user name" autocomplete="off">
            <button class="btn btn-yg btn-sm" type="submit"><i class="bi bi-search me-1"></i>Search</button>
            @if ($userName !== '')
              <a href="{{ route('announcements.statistics', $announcement) }}" class="btn btn-outline-secondary btn-sm" title="Clear filter"><i class="bi bi-x-lg"></i></a>
            @endif
          </form>
        </div>
        <div class="list-group list-group-flush">
          @forelse ($attendees as $row)
            <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
              <div>
                <div class="fw-semibold">{{ $row->user->full_name }}</div>
                <div class="small font-monospace text-secondary">{{ $row->user->unique_id }}</div>
              </div>
              <div class="text-end">
                <div>{{ $row->scanned_at->format('g:i A') }}</div>
                <div class="small {{ $row->is_early ? 'text-warning-emphasis' : 'text-secondary' }}">
                  {!! $row->is_early ? 'Early &middot; +10%' : 'On time' !!} &middot; +{{ $row->points_awarded }}
                </div>
              </div>
            </div>
          @empty
            <div class="list-group-item text-center text-secondary py-5">No one has checked in yet.</div>
          @endforelse
        </div>
      </div>
    @endif

    @if ($announcement->raffle_enabled)
      <div class="card yg-card overflow-hidden mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <span class="fw-bold"><i class="bi bi-ticket-perforated me-1 text-yg"></i>Event raffle</span>
        </div>
        <div class="card-body">
          <p class="small text-secondary mb-3">Eligible attendees are entered automatically, and winners are selected fairly from successful event check-ins.</p>
          @if ($canViewDetails)
            @if ($raffleWinners->isNotEmpty())
              <div class="alert alert-info border-0 mb-3">
                <i class="bi bi-info-circle me-2"></i><strong>{{ $raffleWinners->count() }} winner(s) announced</strong>
              </div>
            @endif
            <form method="POST" action="{{ route('announcements.raffle.prizes.store', $announcement) }}" class="row g-2 align-items-end border-bottom pb-3 mb-3">
              @csrf
              <div class="col-md-4"><label class="form-label small fw-semibold">Prize Name</label><input name="name" class="form-control" required maxlength="255"></div>
              <div class="col-md-3"><label class="form-label small fw-semibold">Type of Prize</label><select name="prize_type" class="form-select raffle-prize-type" data-points-target="raffle-points-add" required>@foreach (\App\Models\Prize::TYPES as $value => $type)@if($value !== 'none')<option value="{{ $value }}" @selected($value === 'foods')>{{ $type['label'] }}</option>@endif @endforeach</select></div>
              <div class="col-md-2 d-none" id="raffle-points-add"><label class="form-label small fw-semibold">Points to Award</label><input name="points_amount" type="number" min="1" max="1000000" class="form-control"></div>
              <div class="col-md-2"><label class="form-label small fw-semibold">No. of Winners</label><input name="quantity" type="number" min="1" max="10000" value="1" class="form-control" required></div>
              <div class="col-md-2"><label class="form-label small fw-semibold">Description</label><input name="description" class="form-control" maxlength="2000"></div>
              <div class="col-md-1"><button class="btn btn-yg w-100" title="Add prize"><i class="bi bi-plus-lg"></i></button></div>
            </form>
          @endif
          <div class="list-group list-group-flush">
            @forelse ($rafflePrizes as $prize)
              <div class="list-group-item px-0">
                <div class="d-flex justify-content-between align-items-center gap-2">
                  <span class="fw-semibold">{{ $prize->name }} <span class="small text-secondary fw-normal">({{ $prize->prize_type ? $prize->prize_type_label : ($prize->type ?: 'Prize') }}{{ $prize->isPointsPrize() ? ': ' . number_format($prize->points_amount) . ' points' : '' }})</span></span>
                  <span class="small text-secondary raffle-prize-count" data-prize-id="{{ $prize->id }}" data-quantity="{{ $prize->quantity }}">{{ $prize->winners_count }} / {{ $prize->quantity }} winners</span>
                </div>
                @if ($prize->description)<div class="small text-secondary">{{ $prize->description }}</div>@endif
                @if ($canViewDetails)
                  <div class="d-flex flex-wrap gap-2 mt-2">
                  @if ($prize->winners_count < $prize->quantity)
                    <form method="POST" action="{{ route('announcements.raffle.draw', [$announcement, $prize]) }}" class="raffle-draw-form">
                      @csrf
                      <button class="btn btn-yg btn-sm"><i class="bi bi-fullscreen me-1"></i>Open full-screen drawing</button>
                    </form>
                  @else
                    <span class="badge text-bg-secondary">All winner slots filled</span>
                  @endif
                  @if ($prize->winners_count == 0)
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#edit-prize-{{ $prize->id }}"><i class="bi bi-pencil me-1"></i>Edit</button>
                    <form method="POST" action="{{ route('announcements.raffle.prizes.destroy', [$announcement, $prize]) }}" onsubmit="return confirm('Remove this prize?')">
                      @csrf @method('DELETE')
                      <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Delete</button>
                    </form>
                  @endif
                  </div>
                  @if ($prize->winners_count == 0)
                    <form id="edit-prize-{{ $prize->id }}" method="POST" action="{{ route('announcements.raffle.prizes.update', [$announcement, $prize]) }}" class="collapse row g-2 mt-1">
                      @csrf @method('PUT')
                      <div class="col-md-4"><label class="form-label small">Prize Name</label><input name="name" value="{{ $prize->name }}" class="form-control form-control-sm" required></div>
                      <div class="col-md-3"><label class="form-label small">Type of Prize</label><select name="prize_type" class="form-select form-select-sm raffle-prize-type" data-points-target="raffle-points-{{ $prize->id }}" required>@foreach (\App\Models\Prize::TYPES as $value => $type)@if($value !== 'none')<option value="{{ $value }}" @selected(($prize->prize_type ?: 'foods') === $value)>{{ $type['label'] }}</option>@endif @endforeach</select></div>
                      <div class="col-md-2 @if(($prize->prize_type ?: 'foods') !== 'points') d-none @endif" id="raffle-points-{{ $prize->id }}"><label class="form-label small">Points to Award</label><input name="points_amount" type="number" min="1" max="1000000" value="{{ $prize->points_amount }}" class="form-control form-control-sm"></div>
                      <div class="col-md-2"><label class="form-label small">No. of Winners</label><input name="quantity" type="number" min="1" value="{{ $prize->quantity }}" class="form-control form-control-sm" required></div>
                      <div class="col-md-3"><label class="form-label small">Description</label><input name="description" value="{{ $prize->description }}" class="form-control form-control-sm"></div>
                      <div class="col-12"><button class="btn btn-dark btn-sm">Save prize</button></div>
                    </form>
                  @endif
                @endif
                <div class="raffle-winner-list">
                  @foreach ($prize->winners as $winner)
                    <div class="small text-success mt-2"><i class="bi bi-trophy me-1"></i>{{ $winner->winner_name_snapshot }}</div>
                  @endforeach
                </div>
              </div>
            @empty
              <div class="text-secondary small">No prizes have been configured yet.</div>
            @endforelse
          </div>
        </div>
      </div>
    @endif

    @if ($canViewDetails && $stats['reasons']->isNotEmpty())
      <div class="card yg-card overflow-hidden">
        <div class="card-header bg-white fw-bold">Why residents cannot attend</div>
        <ul class="list-group list-group-flush">
          @foreach ($stats['reasons'] as $reason)
            <li class="list-group-item small">{{ $reason->reason }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div id="raffleStage" class="raffle-stage position-fixed top-0 start-0 w-100 min-vh-100 d-flex flex-column justify-content-center align-items-center p-3" style="z-index:1100;" hidden>
      <button type="button" class="btn btn-light position-absolute top-0 end-0 m-3" onclick="document.getElementById('raffleStage').hidden = true; document.fullscreenElement && document.exitFullscreen();">
        <i class="bi bi-x-lg me-1"></i>Close
      </button>
      <div class="small text-uppercase fw-semibold opacity-75">TalaFair raffle drawing</div>
      <h1 id="raffleStageTitle" class="text-center fw-bold mt-2 mb-1">Prize</h1>
      <p id="raffleStageStatus" class="lead text-center mb-4">Ready to draw</p>
      <div class="position-relative d-flex justify-content-center">
        <div id="raffleStageWheel" class="raffle-stage-wheel"></div>
        <span class="raffle-pointer"></span>
      </div>
      <button id="raffleStageStart" type="button" class="btn btn-warning btn-lg fw-bold mt-4 px-5">
        <i class="bi bi-play-fill me-1"></i>Spin now
      </button>
      <p class="small opacity-75 mt-4 mb-0">The wheel animation reveals the winner selected securely by the server.</p>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('.raffle-prize-type').forEach(select => {
    const pointsWrap = document.getElementById(select.dataset.pointsTarget);
    const pointsInput = pointsWrap?.querySelector('input[name="points_amount"]');
    const syncPoints = () => {
      const isPoints = select.value === 'points';
      pointsWrap?.classList.toggle('d-none', !isPoints);
      if (pointsInput) pointsInput.required = isPoints;
    };
    select.addEventListener('change', syncPoints);
    syncPoints();
  });
</script>
<style>
  .raffle-stage { background: radial-gradient(circle at center, #244d30 0, #102318 52%, #07110b 100%); color: #fff; }
  .raffle-stage-wheel { width: min(72vw, 34rem); aspect-ratio: 1; border-radius: 50%; border: .8rem solid #fbc02d; box-shadow: 0 0 0 1rem rgba(251, 192, 45, .15), 0 1.5rem 4rem rgba(0,0,0,.45); position: relative; overflow: hidden; transition: transform 5.2s cubic-bezier(.12,.7,.08,1); }
  .raffle-stage-wheel::after { content: ''; position: absolute; inset: 43%; border-radius: 50%; background: #fff; border: .45rem solid #fbc02d; box-shadow: 0 .3rem .8rem rgba(0,0,0,.35); }
  .raffle-wheel-label { position: absolute; left: 50%; top: 50%; width: 42%; transform-origin: 0 0; font-weight: 700; font-size: clamp(.7rem, 2vw, 1rem); text-align: right; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .raffle-pointer { position: absolute; z-index: 2; top: -1rem; left: calc(50% - 1rem); width: 0; height: 0; border-left: 1rem solid transparent; border-right: 1rem solid transparent; border-top: 2.2rem solid #fff; filter: drop-shadow(0 .2rem .2rem rgba(0,0,0,.5)); }
  .raffle-stage[hidden] { display: none !important; }
</style>
<script>
  (() => {
    const form = document.getElementById('attendanceUserName')?.form;
    const input = document.getElementById('attendanceUserName');
    let timer;

    input?.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(() => form?.requestSubmit(), 350);
    });
  })();

  const raffleParticipants = @json($raffleAttendees->map(fn ($attendance) => ['id' => $attendance->user_id, 'name' => $attendance->user->full_name])->values());

  const wheelColors = [
    { background: '#fbc02d', text: '#1f2937' },
    { background: '#2e7d32', text: '#ffffff' },
    { background: '#0288d1', text: '#ffffff' },
    { background: '#ef6c00', text: '#ffffff' },
    { background: '#c2185b', text: '#ffffff' },
    { background: '#6a1b9a', text: '#ffffff' },
  ];

  const buildWheelColors = participantCount => {
    if (participantCount < 1) return [];

    const assigned = [];
    for (let index = 0; index < participantCount; index += 1) {
      const previous = assigned[index - 1];
      const first = assigned[0];
      const candidates = wheelColors
        .map((color, colorIndex) => ({ color, colorIndex }))
        .sort(({ colorIndex: left }, { colorIndex: right }) =>
          (left - (index % wheelColors.length)) - (right - (index % wheelColors.length))
        );
      const selected = candidates.find(({ color }) =>
        color !== previous && (index !== participantCount - 1 || participantCount < 2 || color !== first)
      );

      assigned.push(selected?.color ?? wheelColors[index % wheelColors.length]);
    }

    return assigned;
  };

  let activeRaffleForm = null;

  const openRaffleStage = form => {
    const stage = document.getElementById('raffleStage');
    const wheel = document.getElementById('raffleStageWheel');
    const title = document.getElementById('raffleStageTitle');
    const status = document.getElementById('raffleStageStatus');
    const names = raffleParticipants.map(participant => participant.name);
    const angle = 360 / Math.max(names.length, 1);
    const segmentColors = buildWheelColors(names.length);
    const startButton = document.getElementById('raffleStageStart');

    activeRaffleForm = form;
    stage.hidden = false;
    document.documentElement.requestFullscreen?.().catch(() => {});
    title.textContent = form.closest('.list-group-item').querySelector('.fw-semibold').textContent;
    status.textContent = 'Ready to draw';
    startButton.hidden = false;
    startButton.disabled = false;
    wheel.innerHTML = '';
    wheel.style.transform = 'rotate(0deg)';
    wheel.style.background = `conic-gradient(${segmentColors.map((color, index) => `${color.background} ${index * angle}deg ${(index + 1) * angle}deg`).join(', ')})`;
    names.forEach((name, index) => {
      const label = document.createElement('span');
      label.className = 'raffle-wheel-label';
      label.textContent = name;
      label.style.color = segmentColors[index].text;
      label.style.transform = `rotate(${index * angle + angle / 2 - 90}deg) translateX(-100%)`;
      wheel.appendChild(label);
    });
  };

  const spinRaffle = async () => {
    if (!activeRaffleForm) return;

    const form = activeRaffleForm;
    const button = document.getElementById('raffleStageStart');
    const status = document.getElementById('raffleStageStatus');
    button.disabled = true;
    status.textContent = 'Drawing a winner...';

    try {
      const response = await fetch(form.action, {method: 'POST', headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value}});
      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'The draw could not be completed.');

      updatePrizeAfterDraw(form, data);
      button.hidden = true;
      const wheel = document.getElementById('raffleStageWheel');
      const winnerIndex = raffleParticipants.findIndex(participant => participant.id === data.winner_user_id);
      const angle = 360 / Math.max(raffleParticipants.length, 1);
      window.requestAnimationFrame(() => {
        wheel.style.transform = `rotate(${360 * 6 + (360 - ((winnerIndex < 0 ? 0 : winnerIndex) * angle + angle / 2))}deg)`;
      });
      window.setTimeout(() => {
        status.innerHTML = `<span class="text-warning">Congratulations!</span><br><strong>${data.winner}</strong><br><span>${data.prize_type}${data.points_awarded ? ` · +${data.points_awarded} points` : ''}</span>`;
        window.setTimeout(() => window.location.reload(), 2200);
      }, 5400);
    } catch (error) {
      window.alert(error.message);
      status.textContent = 'Ready to draw';
      button.disabled = false;
    }
  };

  const updatePrizeAfterDraw = (form, data) => {
    const item = form.closest('.list-group-item');
    const counter = item.querySelector('.raffle-prize-count');
    const quantity = Number(counter.dataset.quantity);
    const current = Number.parseInt(counter.textContent, 10) || 0;
    counter.textContent = `${current + 1} / ${quantity} winners`;
    item.querySelector('.raffle-winner-list').insertAdjacentHTML('beforeend', `<div class="small text-success mt-2"><i class="bi bi-trophy me-1"></i>${data.winner}</div>`);
    if (current + 1 >= quantity) {
      form.outerHTML = '<span class="badge text-bg-secondary">All winner slots filled</span>';
    }
  };

  document.getElementById('raffleStageStart').addEventListener('click', spinRaffle);

  document.querySelectorAll('.raffle-draw-form').forEach(form => form.addEventListener('submit', event => {
    event.preventDefault();
    openRaffleStage(form);
  }));
</script>
@endpush
