@extends('layouts.app')

@section('title', $announcement->title)

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-9">

    {{-- Details --}}
    <div class="card yg-card overflow-hidden mb-4">
      @if ($announcement->banner_url)
        <img src="{{ $announcement->banner_url }}" data-image-fallback="{{ asset('images/image-placeholder.svg') }}" alt="" class="card-img-top object-fit-cover" style="height:14rem;">
      @endif

      <div class="card-body p-4">
        <span class="badge text-bg-light text-uppercase">{{ $announcement->category }}</span>
        <h3 class="fw-bold mt-3">{{ $announcement->title }}</h3>

        <div class="mt-3">
          @include('pages.partials.announcement-details', ['announcement' => $announcement])
        </div>

        @if ($myAttendance)
          <div class="alert alert-success d-flex align-items-start gap-2 mt-3 mb-0" role="status">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <div>
              <div class="fw-semibold">Your attendance was recorded for this event.</div>
              <div class="small">
                Checked in {{ $myAttendance->scanned_at->format('M j, Y g:i A') }}
                · {{ $myAttendance->points_awarded }} points earned
              </div>
            </div>
          </div>
        @endif

        @if ($announcement->is_event && ! $myAttendance && auth()->check() && (! $announcement->isParticipationActivity() || auth()->user()->canAccessOfficialFeatures()))
          <a href="{{ route('announcements.scan', $announcement) }}" class="btn btn-primary mt-3">
            <i class="bi bi-qr-code-scan me-1"></i>
            {{ $announcement->isParticipationActivity() ? 'Scan participants' : (auth()->user()->canAccessOfficialFeatures() ? 'Scan resident ID' : 'Scan event QR') }}
          </a>
          @if ($announcement->isParticipationActivity())
            <span class="small text-secondary ms-2">{{ $announcement->participation_points }} points per resident</span>
          @endif
        @endif

        @if ($announcement->is_event && auth()->check() && auth()->user()->canAccessOfficialFeatures() && ! $announcement->isParticipationActivity())
          <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 border rounded-3 bg-body-tertiary p-3 mt-3">
            <div>
              <div class="fw-semibold"><i class="bi bi-qr-code me-2 text-yg"></i>Event check-in QR</div>
              <div class="small text-secondary">Display or print this QR for attendees to scan at the event.</div>
            </div>
            <a href="{{ route('announcements.qr', $announcement) }}" class="btn btn-success fw-semibold text-nowrap">
              <i class="bi bi-qr-code me-1"></i>Display Event QR
            </a>
          </div>
        @endif

        <hr>
        <p class="small text-secondary mb-0">
          Posted by {{ $announcement->creator?->full_name ?? 'Barangay office' }}
          ({{ $announcement->creator?->unique_id ?? '—' }})
          on {{ $announcement->created_at->format('M j, Y g:i A') }}
          @if ($announcement->editor && $announcement->updated_at->gt($announcement->created_at))
            &middot; last updated by {{ $announcement->editor->full_name }}
            ({{ $announcement->editor->unique_id }})
            on {{ $announcement->updated_at->format('M j, Y g:i A') }}
          @endif
        </p>
      </div>
    </div>

    {{-- Attendance survey (audience only) --}}
    @if ($announcement->is_event && $iAmAudience)
      <div class="card yg-card mb-4">
        <div class="card-body p-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-check2-square me-1 text-yg"></i>Will you attend?</h6>

          @if ($assignedSubstitution)
            <div class="alert alert-info border small">
              <i class="bi bi-person-check-fill me-1"></i>You were assigned to attend this event as a substitute.
            </div>
          @endif

          @if ($myRsvp)
            <p class="mb-3">
              <span class="badge {{ $myRsvp->isAttending() ? 'text-bg-success' : 'text-bg-secondary' }}">
                {{ $myRsvp->isAttending() ? 'You said yes' : 'You said no' }}
              </span>
              <span class="small text-secondary ms-1">answered {{ $myRsvp->responded_at->diffForHumans() }}</span>
              @if ($myRsvp->reason)
                <span class="d-block small text-secondary mt-1">Reason: {{ $myRsvp->reason }}</span>
              @endif
            </p>
          @endif

          @if ($announcement->surveyIsOpen())
            <form method="POST" action="{{ route('announcements.rsvp', $announcement) }}">
              @csrf
              <div class="d-flex flex-wrap gap-3 mb-3">
                <div class="form-check border rounded-3 px-3 py-2 ps-5">
                  <input class="form-check-input" type="radio" name="status" id="rsvp_yes" value="attending" required
                         @checked(old('status', $myRsvp?->status) === 'attending')
                        onchange="document.getElementById('reason_wrap').hidden = true; document.getElementById('substitute_wrap')?.setAttribute('hidden', '')">
                  <label class="form-check-label" for="rsvp_yes">Yes, I will attend</label>
                </div>
                <div class="form-check border rounded-3 px-3 py-2 ps-5">
                  <input class="form-check-input" type="radio" name="status" id="rsvp_no" value="not_attending" required
                         @checked(old('status', $myRsvp?->status) === 'not_attending')
                        onchange="document.getElementById('reason_wrap').hidden = false; document.getElementById('substitute_wrap')?.removeAttribute('hidden')">
                  <label class="form-check-label" for="rsvp_no">No, I cannot</label>
                </div>
              </div>

              <div id="reason_wrap" class="mb-3" @if(old('status', $myRsvp?->status) !== 'not_attending') hidden @endif>
                <label class="form-label">Why not?</label>
                <textarea name="reason" rows="3"
                          class="form-control @error('reason') is-invalid @enderror">{{ old('reason', $myRsvp?->reason) }}</textarea>
                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <button class="btn btn-primary fw-semibold px-4">
                {{ $myRsvp ? 'Update my answer' : 'Send my answer' }}
              </button>
            </form>

            @if (auth()->user()->is_head_of_family)
              @if ($householdMembers->isNotEmpty())
                <div id="substitute_wrap" class="border rounded-3 p-3 mt-3 bg-body-secondary"
                     @if(old('status', $myRsvp?->status) !== 'not_attending') hidden @endif>
                  <div class="fw-semibold"><i class="bi bi-person-check-fill me-1 text-yg"></i>Assign a substitute</div>
                  <div class="small text-secondary mb-2">Choose a household member to attend on your behalf. Points go to the person who attends.</div>
                  <form method="POST" action="{{ route('account.substitute') }}" class="row g-2 align-items-end">
                    @csrf
                    <input type="hidden" name="announcement_id" value="{{ $announcement->id }}">
                    <div class="col-md-8">
                      <label class="form-label small fw-semibold" for="substitute_{{ $announcement->id }}">Household member</label>
                      <select id="substitute_{{ $announcement->id }}" name="substitute_user_id" class="form-select" required>
                        <option value="">Select a member</option>
                        @foreach ($householdMembers as $member)
                          <option value="{{ $member->id }}" @selected($substitution?->substitute_user_id === $member->id)>{{ $member->full_name }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-4"><button class="btn btn-primary w-100"><i class="bi bi-check2 me-1"></i>Assign</button></div>
                  </form>
                </div>
              @else
                <div id="substitute_wrap" class="alert alert-light border small mt-3 mb-0"
                     @if(old('status', $myRsvp?->status) !== 'not_attending') hidden @endif>
                  No eligible household members are available to assign as a substitute.
                </div>
              @endif
            @endif
          @else
            <p class="text-secondary mb-0">
              The survey closed on {{ $announcement->rsvp_due_at?->format('M j, Y g:i A') }}.
              You can still check in at the venue with the event QR.
            </p>
          @endif
        </div>
      </div>
    @endif

    {{-- Statistics (residents and officials both see this) --}}
    @if ($announcement->is_event)
      <div class="card yg-card mb-4">
        <div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart me-1 text-yg"></i>Attendance so far</h6>
            <a href="{{ route('announcements.statistics', $announcement) }}" class="btn btn-primary btn-sm rounded-2 fw-semibold">Full breakdown</a>
          </div>

          <div class="row g-3 text-center">
            @foreach ([
              'Notified' => $stats['audience'],
              'Confirmed yes' => $stats['attending'],
              'Said no' => $stats['not_attending'],
              'Checked in' => $stats['scanned'],
            ] as $label => $value)
              <div class="col-6 col-md-3">
                <div class="bg-body-secondary rounded-3 py-3">
                  <div class="h4 fw-bold mb-0">{{ number_format($value) }}</div>
                  <div class="small text-uppercase text-secondary">{{ $label }}</div>
                </div>
              </div>
            @endforeach
          </div>

          <div class="progress mt-3" style="height:.5rem;" role="progressbar">
            <div class="progress-bar bg-success"
                 style="width: {{ $stats['audience'] ? min(100, round($stats['attending'] / $stats['audience'] * 100)) : 0 }}%"></div>
          </div>
          <p class="small text-secondary mt-2 mb-0">
            {{ $stats['no_response'] }} resident(s) have not answered yet.
            {{ $stats['early'] }} of {{ $stats['scanned'] }} check-ins were early.
          </p>
        </div>
      </div>
    @endif

    {{-- Resident-facing raffle entry point. Visible before drawing starts. --}}
    @if ($announcement->is_event && $announcement->raffle_enabled)
      <div class="card yg-card mb-4 border-warning-subtle">
        <div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
              <h6 class="fw-bold mb-1"><i class="bi bi-broadcast-pin me-1 text-yg"></i>Live event raffle</h6>
              @if ($raffleWinners->isEmpty())
                <p class="small text-secondary mb-0">The raffle is ready. Join this page when the drawing begins.</p>
              @else
                <p class="small text-success mb-0">The raffle is live. {{ $raffleWinners->count() }} winner(s) have been announced.</p>
              @endif
            </div>
            <span class="badge text-bg-warning"><i class="bi bi-broadcast-pin me-1"></i>Ready for the drawing</span>
          </div>

          @if ($rafflePrizes->isNotEmpty())
            <div class="d-flex flex-wrap gap-2 mt-3">
              @foreach ($rafflePrizes as $prize)
                <span class="badge text-bg-light border">{{ $prize->name }} · {{ $prize->winners_count }}/{{ $prize->quantity }}</span>
              @endforeach
            </div>
          @else
            <p class="small text-secondary mt-3 mb-0">The official is still preparing the prizes.</p>
          @endif

          @if ($raffleWinners->isNotEmpty())
            <div class="border-top mt-3 pt-3">
              <div class="small text-uppercase text-secondary fw-semibold mb-2">Winners announced</div>
              <div class="list-group list-group-flush">
                @foreach ($raffleWinners->groupBy('event_raffle_prize_id') as $prizeWinners)
                  @foreach ($prizeWinners as $winner)
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center gap-3">
                      <span class="small">{{ $winner->prize->name }}</span>
                      <strong class="small text-success"><i class="bi bi-trophy-fill me-1"></i>{{ $winner->public_name }}</strong>
                    </div>
                  @endforeach
                @endforeach
              </div>
            </div>
          @endif
        </div>
      </div>
    @endif

    {{-- Official controls --}}
    @if ($announcement->is_event && auth()->check() && auth()->user()->canAccessOfficialFeatures())
      <div class="card yg-card">
        <div class="card-body p-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-sliders me-1 text-yg"></i>Official controls</h6>

          <div class="row g-4">
            <div class="col-md-6">
              <form method="POST" action="{{ route('announcements.extend', $announcement) }}">
                @csrf @method('PATCH')
                <label class="form-label">Extend the confirmation deadline</label>
                <input type="datetime-local" name="rsvp_due_at" class="form-control"
                       value="{{ $announcement->rsvp_due_at?->format('Y-m-d\TH:i') }}">
                <button class="btn btn-dark btn-sm fw-semibold mt-2">Extend</button>
              </form>
            </div>

            <div class="col-md-6">
              <form method="POST" action="{{ route('announcements.remind', $announcement) }}" class="border-top mt-3 pt-3">
                @csrf
                <input type="hidden" name="kind" value="event_reminder">
                <label class="form-label fw-semibold" for="eventReminderTarget">Remind the audience</label>
                <select id="eventReminderTarget" name="target" class="form-select" aria-label="Reminder type" required>
                  <option value="everyone">Remind everyone about this event</option>
                  @if ($announcement->surveyIsOpen())
                    <option value="pending_rsvp">Ask audience members who have not responded to RSVP</option>
                  @endif
                </select>
                <input id="eventReminderMessage" name="message" class="form-control mt-2" maxlength="500"
                       placeholder="Optional note" value="{{ old('message') }}">
                <button class="btn btn-primary btn-sm fw-semibold mt-2">Send reminder</button>
              </form>
            </div>
          </div>

          <hr>
          @if ($announcement->reminders()->exists())
            <ul class="small text-secondary mt-3 mb-0 ps-3">
              @foreach ($announcement->reminders()->with('sender')->latest()->take(5)->get() as $reminder)
                <li>
                  {{ $reminder->created_at->format('M j, g:i A') }} —
                  {{ $reminder->kind === 'event_reminder' ? 'Event reminder' : 'Survey reminder' }}
                  to {{ $reminder->recipients_count }} resident(s), sent by
                  {{ $reminder->sender?->full_name }} ({{ $reminder->sender?->unique_id }})
                </li>
              @endforeach
            </ul>
          @endif
        </div>
      </div>
    @endif
  </div>
</div>
@endsection
