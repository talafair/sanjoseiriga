@extends('layouts.app')

@section('title', 'Scan Attendance')

@section('content')
<div class="row justify-content-center">
  <div class="col-12 col-sm-10 col-md-7 col-lg-5">

    @if ($activityMode)
      <h4 class="fw-bold mb-1"><i class="bi bi-qr-code-scan me-2 text-yg"></i>Scan activity participants</h4>
      <p class="text-secondary">
        Point your camera at the resident ID QR after each activity.
        Scanning opens two hours before the event starts and only works inside the venue.
      </p>
    @elseif ($officialMode)
      <h4 class="fw-bold mb-1"><i class="bi bi-qr-code-scan me-2 text-yg"></i>Attendance Options</h4>
      <p class="text-secondary">
        @if (auth()->user()->isSuperadmin())
          Document attendee attendance at events.
        @else
        Record your own attendance or check in residents and guests at events.
        @endif
        Scanning opens two hours before the event starts and only works inside the venue.
      </p>

      {{-- Tab selection for officials --}}
      @unless (auth()->user()->isSuperadmin())
      <div class="btn-group w-100 mb-3" role="tablist">
        <input type="radio" class="btn-check" name="attendance-mode" id="mode-own" value="own" checked>
        <label class="btn btn-outline-dark" for="mode-own">My Attendance</label>

        <input type="radio" class="btn-check" name="attendance-mode" id="mode-resident" value="resident">
        <label class="btn btn-outline-dark" for="mode-resident">Record Attendee</label>
      </div>
      @endunless

      {{-- Event selection for official's own attendance --}}
      @unless (auth()->user()->isSuperadmin())
      <div id="own-attendance-mode" class="mb-3">
        <label for="own-event-select" class="form-label small fw-semibold mb-1">Select your event</label>
        <select id="own-event-select" class="form-select form-select-sm" @disabled($openEvents->isEmpty())>
          <option value="">Select an event</option>
          @foreach ($openEvents as $event)
            <option value="{{ $event->id }}">{{ $event->title }} · {{ $event->event_start_at->format('M j, g:i A') }}</option>
          @endforeach
        </select>
      </div>
      @endunless

      {{-- Event selection for recording attendee attendance --}}
      <div id="resident-attendance-mode" class="mb-3 {{ auth()->user()->isSuperadmin() ? '' : 'd-none' }}">
        <label for="resident-event-select" class="form-label small fw-semibold mb-1">Select attendee's event</label>
        <select id="resident-event-select" class="form-select form-select-sm" @disabled($openEvents->isEmpty())>
          <option value="">Select an event</option>
          @foreach ($openEvents as $event)
            <option value="{{ $event->id }}">{{ $event->title }} · {{ $event->event_start_at->format('M j, g:i A') }}</option>
          @endforeach
        </select>
      </div>
    @else
      <h4 class="fw-bold mb-1"><i class="bi bi-qr-code-scan me-2 text-yg"></i>Scan for attendance</h4>
      <p class="text-secondary">
        Point your camera at the event QR.
        Scanning opens two hours before the event starts and only works inside the venue.
      </p>
    @endif

    @if ($announcement)
      <div class="alert alert-light border">
        <strong>{{ $announcement->title }}</strong>
        <div class="small text-secondary">{{ $announcement->event_start_at?->format('M j, Y g:i A') }}</div>
      </div>
    @endif

    @if ($openEvents->isEmpty())
      <div class="card yg-card">
        <div class="card-body text-center text-secondary py-5">
          <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
          No event is open for scanning right now.
          <a href="{{ url('/') }}" class="d-block mt-2 fw-semibold">Back to home</a>
        </div>
      </div>
    @elseif (! $officialMode && ! $activityMode)
      <div class="mb-3">
        <label for="event-select" class="form-label small fw-semibold mb-1">Select an event to scan</label>
        <select id="event-select" class="form-select" @disabled($openEvents->isEmpty())>
          <option value="">Select an event</option>
          @foreach ($openEvents as $event)
            <option value="{{ $event->id }}">
              {{ $event->title }} - {{ $event->event_start_at->format('M j, g:i A') }} - {{ $event->venue_name ?: 'Barangay San Jose' }}
            </option>
          @endforeach
        </select>
      </div>
    @endif

    @if (! $openEvents->isEmpty() || $announcement)
      <div id="scanner-placeholder" class="text-center text-secondary px-4 mb-2">
        Select an event and tap "Open camera" to scan the event QR code.
      </div>
      <div id="reader" class="position-relative rounded-3 overflow-hidden mx-auto border" style="width: min(100%, 440px); aspect-ratio: 1 / 1;">
      </div>
        <div class="text-center mt-3">
          <button id="start-btn" @disabled($openEvents->isEmpty() || (!$officialMode && !$activityMode)) class="btn btn-primary fw-semibold">
            <i class="bi bi-camera me-1"></i>Open camera
          </button>
          <button id="stop-btn" class="btn btn-outline-secondary fw-semibold d-none">Close camera</button>
        </div>

      <div id="result" class="alert d-none mt-3 rounded-3" role="alert"></div>

      <form id="attendance-acknowledgment" class="border rounded-3 p-3 mt-3 d-none">
        <div class="fw-semibold mb-2">Review your attendance name</div>
        <label for="confirmed-name" class="form-label">Name for the attendance sheet</label>
        <input id="confirmed-name" class="form-control mb-3" type="text" maxlength="255" autocomplete="name" required value="{{ auth()->user()->full_name }}">
        <p class="small mb-2">I acknowledge that the information I provide will be recorded and included in the attendance sheet for the purpose of documenting my participation in this barangay event.</p>
        <div class="form-check mb-3">
          <input id="attendance-acknowledgment-checkbox" class="form-check-input" type="checkbox" required>
          <label class="form-check-label" for="attendance-acknowledgment-checkbox">I have read and acknowledge the statement above.</label>
        </div>
        <button class="btn btn-primary w-100 fw-semibold" type="submit">Confirm attendance</button>
        <button id="cancel-attendance-acknowledgment" class="btn btn-link btn-sm w-100 mt-2" type="button">Cancel</button>
      </form>

      <div id="permission-help" class="alert alert-light border mt-3">
        <div class="fw-semibold"><i class="bi bi-shield-check me-1 text-yg"></i>Camera and location permission required</div>
        <div class="small text-secondary mt-1">Allow camera access to read the QR code and location access so TalaFair can confirm you are at the venue. Nothing starts until you choose Open camera.</div>
      </div>
    @endif

    @if ($officialMode && ! $activityMode)
      <button id="manual-toggle" type="button" class="btn btn-outline-dark w-100 mt-3">
        <i class="bi bi-keyboard me-1"></i>QR code not scanning? Enter attendee Unique ID instead
      </button>
      <div class="form-text mt-2">Use this for a resident or guest ID when their QR code will not scan.</div>
      <form id="manual-form" class="border rounded-3 p-3 mt-3 d-none">
        <label for="unique-id" class="form-label fw-semibold">Resident or Guest Unique ID Number</label>
        <div class="input-group">
          <input id="unique-id" class="form-control" placeholder="Z2-26-000000001 or GZ2-26-000000001" maxlength="32" autocomplete="off">
          <button class="btn btn-dark" type="submit">Record attendance</button>
        </div>
        <label for="manual-confirmed-name" class="form-label fw-semibold mt-3">Name for the attendance sheet</label>
        <input id="manual-confirmed-name" class="form-control" maxlength="255" autocomplete="off" required>
        <p class="small mt-3 mb-2">I acknowledge that the information I provide will be recorded and included in the attendance sheet for the purpose of documenting my participation in this barangay event.</p>
        <div class="form-check mb-3">
          <input id="manual-acknowledgment-checkbox" class="form-check-input" type="checkbox" required>
          <label class="form-check-label" for="manual-acknowledgment-checkbox">I have read and acknowledge the statement above.</label>
        </div>
        <div class="form-text">Guest check-in must be enabled for the selected event.</div>
        <button id="manual-back" type="button" class="btn btn-link btn-sm px-0">Back to scanner</button>
      </form>
    @endif

    @if (! $officialMode)
      <div id="resident-id-fallback" class="alert alert-warning d-none mt-3">
        <div class="fw-semibold"><i class="bi bi-person-badge me-1"></i>QR scanning is unavailable.</div>
        <div class="small mt-1">Ask an official to scan your digital ID card instead.</div>
        <a href="{{ route('id-card.show') }}" class="btn btn-sm btn-warning mt-2"><i class="bi bi-person-badge me-1"></i>Open my ID card</a>
      </div>
    @endif

    @if (! $openEvents->isEmpty() || $announcement)
      <p id="gps-status" class="form-text mt-2">
        <i class="bi bi-geo-alt me-1"></i>Location is not shared yet.
      </p>
      <p id="offline-status" class="form-text mt-1 d-none"></p>
    @endif

    <a href="{{ route('attendance.history') }}" class="btn btn-link btn-sm px-0">
      <i class="bi bi-clock-history me-1"></i>My attendance history
    </a>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
  const CHECK_URL = @json(route('attendance.check'));
  const CSRF      = @json(csrf_token());
  const PREFILLED = @json($prefilledToken);
  const ANNOUNCEMENT_ID = @json($announcement?->id);
  const OFFICIAL = @json($officialMode);
  const SUPERADMIN = @json(auth()->user()->isSuperadmin());
  const ACTIVITY = @json($activityMode);

  const resultBox = document.getElementById('result');
  const gpsStatus = document.getElementById('gps-status');
  const startBtn  = document.getElementById('start-btn');
  const stopBtn   = document.getElementById('stop-btn');
  const scannerPlaceholder = document.getElementById('scanner-placeholder');
  const offlineStatus = document.getElementById('offline-status');
  const manualForm = document.getElementById('manual-form');
  const manualToggle = document.getElementById('manual-toggle');
  const manualBack = document.getElementById('manual-back');
  const manualConfirmedName = document.getElementById('manual-confirmed-name');
  const manualAcknowledgmentCheckbox = document.getElementById('manual-acknowledgment-checkbox');
  const acknowledgmentForm = document.getElementById('attendance-acknowledgment');
  const confirmedNameInput = document.getElementById('confirmed-name');
  const acknowledgmentCheckbox = document.getElementById('attendance-acknowledgment-checkbox');
  const cancelAcknowledgmentButton = document.getElementById('cancel-attendance-acknowledgment');

  // For official mode
  const ownAttendanceMode = document.getElementById('own-attendance-mode');
  const residentAttendanceMode = document.getElementById('resident-attendance-mode');
  const ownEventSelect = document.getElementById('own-event-select');
  const residentEventSelect = document.getElementById('resident-event-select');
  const modeOwnRadio = document.getElementById('mode-own');
  const modeResidentRadio = document.getElementById('mode-resident');
  
  // Legacy support for single event select
  const eventSelect = document.getElementById('event-select');

  let scanner = null, cameraStream = null, preview = null, busy = false, completed = false, scannerPaused = false, scannerRunning = false;
  let selectedAnnouncementId = ANNOUNCEMENT_ID;
  let pendingSelfCheckInToken = null;
  let officialMode = OFFICIAL; // 'own' for official's own attendance, 'resident' for recording resident
  let currentAttendanceMode = OFFICIAL ? (SUPERADMIN ? 'resident' : 'own') : null;
  const QUEUE_KEY = 'talafair-attendance-queue';
  const OFFLINE_EVENTS_KEY = 'talafair-offline-events';

  try {
    localStorage.setItem(OFFLINE_EVENTS_KEY, JSON.stringify(@json($openEvents->map(fn ($event) => ['id' => $event->id, 'title' => $event->title])->values())));
  } catch (error) {
    console.warn('Unable to save event choices for offline attendance scanning.', error);
  }

  if (!startBtn) return;

  // Handle official mode switching
  if (OFFICIAL && !ACTIVITY) {
    modeOwnRadio?.addEventListener('change', () => {
      currentAttendanceMode = 'own';
      ownAttendanceMode?.classList.remove('d-none');
      residentAttendanceMode?.classList.add('d-none');
      manualForm?.classList.add('d-none');
      manualToggle?.classList.remove('d-none');
      stopCamera();
      stopBtn.classList.add('d-none');
      startBtn.classList.remove('d-none');
      resultBox.classList.add('d-none');
    });

    modeResidentRadio?.addEventListener('change', () => {
      currentAttendanceMode = 'resident';
      ownAttendanceMode?.classList.add('d-none');
      residentAttendanceMode?.classList.remove('d-none');
      manualForm?.classList.add('d-none');
      manualToggle?.classList.remove('d-none');
      stopCamera();
      stopBtn.classList.add('d-none');
      startBtn.classList.remove('d-none');
      resultBox.classList.add('d-none');
    });

    ownEventSelect?.addEventListener('change', () => {
      selectedAnnouncementId = ownEventSelect.value || null;
      startBtn.disabled = !selectedAnnouncementId;
    });

    residentEventSelect?.addEventListener('change', () => {
      selectedAnnouncementId = residentEventSelect.value || null;
      startBtn.disabled = !selectedAnnouncementId;
    });
  }

  eventSelect?.addEventListener('change', () => {
    selectedAnnouncementId = eventSelect.value || null;
    startBtn.disabled = !selectedAnnouncementId;
  });

  if (OFFICIAL && (ownEventSelect || residentEventSelect)) {
    startBtn.disabled = true;
  } else if (eventSelect) {
    startBtn.disabled = !eventSelect.value;
  } else {
    startBtn.disabled = !selectedAnnouncementId;
  }

  function stopCamera() {
    if (scanner && scannerRunning) {
      scanner.stop().catch(() => {});
    }
    if (cameraStream) {
      cameraStream.getTracks().forEach(track => track.stop());
      cameraStream = null;
    }
    preview?.remove();
    preview = null;
    scannerRunning = false;
    scannerPaused = false;
    startBtn.classList.remove('d-none');
    stopBtn.classList.add('d-none');
    scannerPlaceholder?.classList.remove('d-none');
  }

  function cameraError(error) {
    const name = error?.name || '';
    if (name === 'InsecureContextError') {
      return 'Camera access requires HTTPS. Use https:// or open TalaFair through http://localhost; local network IP addresses are not allowed by mobile browsers.';
    }
    if (name === 'NotAllowedError' || name === 'SecurityError') {
      return 'Camera permission was denied. Allow camera access for this site, then try again. Mobile browsers also require HTTPS (localhost is allowed).';
    }
    if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
      return 'No camera was found on this device.';
    }
    if (name === 'NotReadableError' || name === 'TrackStartError') {
      return 'The camera is already in use by another app or browser tab. Close it and try again.';
    }
    if (name === 'OverconstrainedError') {
      return 'The requested camera is unavailable. Check that a camera is connected and try again.';
    }
    return 'Camera access could not start. Check your browser permissions and make sure this page is served over HTTPS.';
  }

  function show(ok, html) {
    resultBox.className = 'alert mt-3 rounded-3 ' + (ok ? 'alert-success' : 'alert-danger');
    resultBox.innerHTML = html;
  }

  function status(message, tone = 'info') {
    resultBox.className = 'alert mt-3 rounded-3 alert-' + tone;
    resultBox.textContent = message;
  }

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    })[character]);
  }

  function feedbackHtml(data, fallback) {
    const message = escapeHtml(data.message || fallback);
    return data.resident
      ? '<strong>' + escapeHtml(data.resident) + ':</strong> ' + message
      : message;
  }

  function queuedScans() {
    const savedQueue = localStorage.getItem(QUEUE_KEY);
    if (!savedQueue) return [];
    const queue = JSON.parse(savedQueue);
    if (!Array.isArray(queue)) throw new Error('Saved attendance scans are not in the expected format.');
    let updated = false;
    queue.forEach(item => {
      if (!item.id) {
        item.id = `${Date.now()}-${Math.random().toString(36).slice(2)}`;
        updated = true;
      }
    });
    if (updated) localStorage.setItem(QUEUE_KEY, JSON.stringify(queue));
    return queue;
  }

  function updateOfflineStatus() {
    let queue;
    try {
      queue = queuedScans();
    } catch (error) {
      console.error('Unable to read saved attendance scans.', error);
      if (offlineStatus) {
        offlineStatus.classList.remove('d-none');
        offlineStatus.textContent = 'Saved scans could not be read from this device. Do not clear browser storage.';
      }
      return;
    }
    const count = queue.length;
    const failed = queue.filter(item => item.syncError).length;
    if (!offlineStatus) return;
    offlineStatus.classList.toggle('d-none', count === 0 && navigator.onLine);
    offlineStatus.textContent = count
      ? failed
        ? `${count} saved scan${count === 1 ? '' : 's'} remain on this device; ${failed} need attention. ${queue.find(item => item.syncError).syncError} They are not confirmed attendance.`
        : `${count} saved scan${count === 1 ? '' : 's'} waiting to sync. They are not confirmed attendance yet.`
      : navigator.onLine
        ? 'Back online. Saved scans are syncing.'
        : 'You are offline. Scans will be saved on this device.';
  }

  function queueScan(url, payload) {
    try {
      const queue = queuedScans();
      queue.push({
        id: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
        userId: @json(auth()->id()),
        url,
        payload,
        queuedAt: Date.now()
      });
      localStorage.setItem(QUEUE_KEY, JSON.stringify(queue));
      updateOfflineStatus();
      show(true, '<div class="fw-semibold"><i class="bi bi-cloud-arrow-down-fill me-1"></i>Scan saved on this device.</div><div class="small mt-1">It is not confirmed attendance until it syncs successfully. Reconnect before the event attendance window closes.</div>');
      window.dispatchEvent(new Event('attendance-queue-updated'));
      if (!OFFICIAL) {
        completed = true;
        stopCamera();
      }
      return true;
    } catch (error) {
      console.error('Unable to save attendance scan on this device.', error);
      show(false, 'This scan could not be saved on this device. Check available device storage and try again.');
      return false;
    }
  }

  async function sendScan(url, payload) {
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
      body: JSON.stringify(payload)
    });
    if (response.status >= 500) {
      const error = new Error('The attendance service is temporarily unavailable.');
      error.status = response.status;
      throw error;
    }
    const data = await response.json().catch(() => ({
      ok: false,
      message: response.status === 401 || response.status === 419
        ? 'Sign in again to sync this saved scan.'
        : 'The attendance service did not return a valid response.'
    }));
    return data;
  }

  updateOfflineStatus();

  function position() {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) {
        const error = new Error('This device cannot share its location.');
        error.name = 'LocationPermissionError';
        return reject(error);
      }
      navigator.geolocation.getCurrentPosition(
        p => resolve(p.coords),
        () => {
          const error = new Error('Turn on location so the barangay can confirm you are at the venue.');
          error.name = 'LocationPermissionError';
          reject(error);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
      );
    });
  }

  async function requestScannerPermissions() {
    if (!window.isSecureContext) {
      throw new DOMException('Camera access requires HTTPS.', 'InsecureContextError');
    }
    if (!navigator.mediaDevices?.getUserMedia) {
      throw new DOMException('Camera API is unavailable in this browser.', 'NotSupportedError');
    }

    let requestedStream = null;
    try {
      const cameraRequest = navigator.mediaDevices.getUserMedia({
        video: { facingMode: { ideal: 'environment' } },
        audio: false
      }).then((stream) => {
        requestedStream = stream;
        return stream;
      });

      const [camera, coords] = await Promise.all([cameraRequest, position()]);
      return { camera, coords };
    } catch (error) {
      requestedStream?.getTracks().forEach(track => track.stop());
      throw error;
    }
  }

  function isAttendeeQr(token) {
    try {
      const payload = JSON.parse(token);
      return Boolean(payload && typeof payload === 'object' && payload.id && payload.sig);
    } catch (error) {
      return false;
    }
  }

  function handleScan(token) {
    if (!ACTIVITY) {
      pendingSelfCheckInToken = token;
      confirmedNameInput.value = isAttendeeQr(token) ? '' : @json(auth()->user()->full_name);
      acknowledgmentCheckbox.checked = false;
      acknowledgmentForm?.classList.remove('d-none');
      confirmedNameInput?.focus();
      status('Review your name and acknowledge the statement before confirming attendance.', 'info');
      return;
    }

    submit(token);
  }

  async function submit(token, acknowledgment = null) {
    token = typeof token === 'string' ? token.trim() : '';
    if (!token || busy || completed) return;
    busy = true;
    status('Processing QR code…', 'info');
    try {
      gpsStatus.innerHTML = '<i class="bi bi-geo-alt me-1"></i>Checking your location…';
      const coords = await position();
      gpsStatus.innerHTML = '<i class="bi bi-geo-alt-fill me-1"></i>Location accurate to about ' +
                            Math.round(coords.accuracy) + ' m.';

      const submitUrl = ACTIVITY ? @json($announcement ? route('announcements.participation', $announcement) : route('attendance.check')) : CHECK_URL;
      const payload = {
        token: token,
        announcement_id: selectedAnnouncementId,
        latitude: coords.latitude,
        longitude: coords.longitude,
        accuracy: coords.accuracy
      };
      if (acknowledgment) {
        payload.confirmed_name = acknowledgment.confirmed_name;
        payload.acknowledgment = acknowledgment.acknowledgment;
      }
      let data;
      try {
        data = await sendScan(submitUrl, payload);
      } catch (error) {
        if (!navigator.onLine || error instanceof TypeError || error.status >= 500) {
          queueScan(submitUrl, payload);
          return;
        }
        throw error;
      }

      if (data.ok) {
        acknowledgmentForm?.classList.add('d-none');
        completed = !OFFICIAL;
        show(true, ACTIVITY
          ? '<div class="fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>' + escapeHtml(data.resident) + ': ' + escapeHtml(data.message) + '</div>'
          :
          '<div class="fw-semibold mb-2"><i class="bi bi-check-circle-fill me-1"></i>' + escapeHtml(data.resident) + ': ' + escapeHtml(data.message) + '</div>' +
          '<ul class="small mb-0 ps-3">' +
            '<li>Event: ' + escapeHtml(data.event) + '</li>' +
            '<li>Base points: ' + data.breakdown.base + '</li>' +
            '<li>Early scan bonus: +' + data.breakdown.early_bonus + '</li>' +
            '<li class="fw-semibold">Total: ' + data.breakdown.total + '</li>' +
          '</ul>');
        stopCamera();
      } else {
        const scanMessage = feedbackHtml(data, 'Attendance was not recorded. That scan could not be accepted.');
        if (OFFICIAL) {
          manualForm?.classList.remove('d-none');
          manualToggle?.classList.add('d-none');
          show(false, '<div class="fw-semibold"><i class="bi bi-exclamation-triangle-fill me-1"></i>' + scanMessage + '</div><div class="small mt-1">You can keep trying the scanner or enter the attendee\'s Unique ID Number below.</div>');
        } else {
          show(false, scanMessage);
          document.getElementById('resident-id-fallback')?.classList.remove('d-none');
        }
      }
    } catch (err) {
      show(false, 'Unable to record attendance. Please try again.');
    } finally {
      if (scannerPaused && scanner) {
        try { scanner.resume(); } catch (e) {}
        scannerPaused = false;
      }
      busy = false;
    }
  }

  acknowledgmentForm?.addEventListener('submit', event => {
    event.preventDefault();
    const confirmedName = confirmedNameInput.value.trim();
    if (!confirmedName || !acknowledgmentCheckbox.checked || !pendingSelfCheckInToken) {
      show(false, 'Enter your name and acknowledge the statement before confirming attendance.');
      return;
    }
    const token = pendingSelfCheckInToken;
    pendingSelfCheckInToken = null;
    acknowledgmentForm.classList.add('d-none');
    submit(token, { confirmed_name: confirmedName, acknowledgment: true });
  });

  cancelAcknowledgmentButton?.addEventListener('click', () => {
    pendingSelfCheckInToken = null;
    acknowledgmentForm.classList.add('d-none');
    status('Attendance was not submitted. Scan the event QR when you are ready.', 'secondary');
    if (scannerPaused && scanner) {
      try { scanner.resume(); } catch (error) {}
      scannerPaused = false;
    }
  });

  manualForm?.addEventListener('submit', async function (event) {
    event.preventDefault();
    if (completed || busy || currentAttendanceMode !== 'resident') return;
    busy = true;
    try {
      const uniqueId = document.getElementById('unique-id');
      if (!uniqueId.value.trim()) {
        show(false, 'Enter the resident or guest Unique ID from their ID card.');
        return;
      }
      if (!manualConfirmedName.value.trim() || !manualAcknowledgmentCheckbox.checked) {
        show(false, 'Enter the attendee name and acknowledge the statement before recording attendance.');
        return;
      }
      const coords = await position();
      const manualUrl = @json(route('attendance.check-by-id'));
      const payload = {
        announcement_id: selectedAnnouncementId,
        unique_id: uniqueId.value,
        confirmed_name: manualConfirmedName.value.trim(),
        acknowledgment: manualAcknowledgmentCheckbox.checked,
        latitude: coords.latitude,
        longitude: coords.longitude
      };
      manualConfirmedName.value = '';
      manualAcknowledgmentCheckbox.checked = false;
      let data;
      try {
        data = await sendScan(manualUrl, payload);
      } catch (error) {
        if (!navigator.onLine || error instanceof TypeError || error.status >= 500) {
          if (queueScan(manualUrl, payload)) uniqueId.value = '';
          return;
        }
        throw error;
      }
      if (data.ok) {
        uniqueId.value = '';
        manualConfirmedName.value = '';
        manualAcknowledgmentCheckbox.checked = false;
        show(true, '<div class="fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>' + escapeHtml(data.resident) + '</div><div class="small mt-1">' + escapeHtml(data.message) + '</div>');
      } else {
        show(false, feedbackHtml(data, 'Attendee Unique ID not found. Please check the ID and try again.'));
      }
    } catch (err) {
      show(false, 'Unable to record attendance. Please try again.');
    } finally {
      busy = false;
    }
  });

  manualToggle?.addEventListener('click', () => {
    // Only show manual form in resident mode for officials
    if (OFFICIAL && currentAttendanceMode !== 'resident') {
      show(false, 'Switch to "Record Attendee" mode to use manual ID entry.');
      return;
    }
    manualForm?.classList.remove('d-none');
    manualToggle.classList.add('d-none');
    if (scanner) scanner.stop().catch(() => {});
    stopBtn.classList.add('d-none');
    startBtn.classList.remove('d-none');
  });

  manualBack?.addEventListener('click', () => {
    manualForm.classList.add('d-none');
    manualToggle?.classList.remove('d-none');
  });

  startBtn.addEventListener('click', async function () {
    if (completed) return;
    
    const selectedEvent = OFFICIAL ? (currentAttendanceMode === 'own' ? ownEventSelect : residentEventSelect) : eventSelect;
    if (!selectedAnnouncementId) {
      show(false, currentAttendanceMode === 'own' 
        ? 'Select your event before starting the scanner.'
        : 'Select the attendee\'s event before starting the scanner.');
      selectedEvent?.focus();
      return;
    }

    try {
      status('Requesting camera and location permission…', 'info');
      const permissions = await requestScannerPermissions();
      cameraStream = permissions.camera;
      gpsStatus.innerHTML = '<i class="bi bi-geo-alt-fill me-1"></i>Location permission granted. Checking your position when you scan.';
      preview = document.createElement('video');
      preview.autoplay = true;
      preview.playsInline = true;
      preview.muted = true;
      preview.className = 'w-100 h-100 object-fit-cover';
      preview.srcObject = cameraStream;
      document.getElementById('reader').prepend(preview);
      await preview.play();

      const deviceId = cameraStream.getVideoTracks()[0]?.getSettings().deviceId;
      cameraStream.getTracks().forEach(track => track.stop());
      cameraStream = null;
      preview.remove();
      preview = null;
      scanner = scanner || new Html5Qrcode('reader');
      status('Starting QR scanner…', 'info');
      await scanner.start(
        deviceId ? { deviceId: { exact: deviceId } } : { facingMode: { ideal: 'environment' } },
        {
          fps: 10,
          qrbox: (viewfinderWidth, viewfinderHeight) => {
            const size = Math.min(320, Math.floor(Math.min(viewfinderWidth, viewfinderHeight) * 0.8));
            return { width: size, height: size };
          }
        },
        text => {
          if (busy || completed) return;
          try {
            scanner.pause(true);
            scannerPaused = true;
          } catch (e) {}
          handleScan(text);
        },
        () => {}
      );
      scannerRunning = true;
      const scanMessage = OFFICIAL 
        ? (currentAttendanceMode === 'own' 
          ? 'Scanning for the event QR code…' 
          : 'Scanning for the resident or guest ID QR code or event QR…')
        : 'Scanning for a QR code…';
      status(scanMessage, 'info');
      startBtn.classList.add('d-none');
      stopBtn.classList.remove('d-none');
      scannerPlaceholder?.classList.add('d-none');
      document.getElementById('permission-help')?.classList.add('d-none');
    } catch (e) {
      stopCamera();
      const cameraMessage = e.name === 'LocationPermissionError'
        ? e.message
        : e.name === 'NotSupportedError'
        ? 'This browser does not support camera access.'
        : cameraError(e);
      const fallbackMsg = OFFICIAL && manualToggle && currentAttendanceMode === 'resident'
        ? cameraMessage + '<div class="small fw-semibold mt-2">Use the attendee Unique ID Number fallback below if scanning is unavailable.</div>'
        : cameraMessage;
      show(false, fallbackMsg);
      if (OFFICIAL && manualForm && manualToggle && currentAttendanceMode === 'resident') {
        manualForm.classList.remove('d-none');
        manualToggle.classList.add('d-none');
      }
      gpsStatus.innerHTML = '<i class="bi bi-geo-alt me-1"></i>Camera and location permission are still required.';
    }
  });

  stopBtn.addEventListener('click', function () {
    stopCamera();
    status('Camera stopped. Select Open camera to try again.', 'secondary');
  });

  window.addEventListener('pagehide', () => {
    stopCamera();
  });

  if (PREFILLED) {
    status('Processing event QR code…', 'info');
    handleScan(PREFILLED);
  }
})();
</script>
@endpush
