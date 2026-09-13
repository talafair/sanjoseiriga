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
        Record your own attendance or document resident attendance at events.
        Scanning opens two hours before the event starts and only works inside the venue.
      </p>

      {{-- Tab selection for officials --}}
      <div class="btn-group w-100 mb-3" role="tablist">
        <input type="radio" class="btn-check" name="attendance-mode" id="mode-own" value="own" checked>
        <label class="btn btn-outline-dark" for="mode-own">My Attendance</label>

        <input type="radio" class="btn-check" name="attendance-mode" id="mode-resident" value="resident">
        <label class="btn btn-outline-dark" for="mode-resident">Record Resident</label>
      </div>

      {{-- Event selection for official's own attendance --}}
      <div id="own-attendance-mode" class="mb-3">
        <label for="own-event-select" class="form-label small fw-semibold mb-1">Select your event</label>
        <select id="own-event-select" class="form-select form-select-sm" @disabled($openEvents->isEmpty())>
          <option value="">Select an event</option>
          @foreach ($openEvents as $event)
            <option value="{{ $event->id }}">{{ $event->title }} · {{ $event->event_start_at->format('M j, g:i A') }}</option>
          @endforeach
        </select>
      </div>

      {{-- Event selection for recording resident attendance --}}
      <div id="resident-attendance-mode" class="mb-3 d-none">
        <label for="resident-event-select" class="form-label small fw-semibold mb-1">Select resident's event</label>
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
      <div id="reader" class="position-relative rounded-3 overflow-hidden mx-auto border" style="width: min(100%, 320px); aspect-ratio: 1 / 1;">
      </div>
        <div class="text-center mt-3">
          <button id="start-btn" @disabled($openEvents->isEmpty() || (!$officialMode && !$activityMode)) class="btn btn-primary fw-semibold">
            <i class="bi bi-camera me-1"></i>Open camera
          </button>
          <button id="stop-btn" class="btn btn-outline-secondary fw-semibold d-none">Close camera</button>
        </div>

      <div id="result" class="alert d-none mt-3 rounded-3" role="alert"></div>

      <div id="permission-help" class="alert alert-light border mt-3">
        <div class="fw-semibold"><i class="bi bi-shield-check me-1 text-yg"></i>Camera and location permission required</div>
        <div class="small text-secondary mt-1">Allow camera access to read the QR code and location access so TalaFair can confirm you are at the venue. Nothing starts until you choose Open camera.</div>
      </div>
    @endif

    @if ($officialMode && ! $activityMode)
      <button id="manual-toggle" type="button" class="btn btn-outline-dark w-100 mt-3">
        <i class="bi bi-keyboard me-1"></i>QR code not scanning? Enter Resident Unique ID instead
      </button>
      <div class="form-text mt-2">You can keep trying the QR scanner as many times as needed, or use the manual Unique ID option (resident mode only).</div>
      <form id="manual-form" class="border rounded-3 p-3 mt-3 d-none">
        <label for="unique-id" class="form-label fw-semibold">Resident Unique ID Number</label>
        <div class="input-group">
          <input id="unique-id" class="form-control" placeholder="Z2-26-000000001" maxlength="32" autocomplete="off">
          <button class="btn btn-dark" type="submit">Record attendance</button>
        </div>
        <div class="form-text">Enter the resident's existing Unique ID Number from their ID card.</div>
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
  let officialMode = OFFICIAL; // 'own' for official's own attendance, 'resident' for recording resident
  let currentAttendanceMode = OFFICIAL ? 'own' : null;
  const QUEUE_KEY = 'talafair-attendance-queue';

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

  function queuedScans() {
    try { return JSON.parse(localStorage.getItem(QUEUE_KEY) || '[]'); } catch (e) { return []; }
  }

  function updateOfflineStatus() {
    const count = queuedScans().length;
    if (!offlineStatus) return;
    offlineStatus.classList.toggle('d-none', count === 0 && navigator.onLine);
    offlineStatus.innerHTML = count
      ? '<i class="bi bi-cloud-arrow-up me-1"></i>' + count + ' scan' + (count === 1 ? '' : 's') + ' waiting to sync when you are online.'
      : '<i class="bi bi-wifi me-1"></i>Back online. Saved scans are syncing.';
  }

  function queueScan(url, payload) {
    const queue = queuedScans();
    queue.push({ url, payload, queuedAt: Date.now() });
    localStorage.setItem(QUEUE_KEY, JSON.stringify(queue));
    updateOfflineStatus();
    show(true, '<div class="fw-semibold"><i class="bi bi-cloud-arrow-down-fill me-1"></i>Scan saved on this device.</div><div class="small mt-1">It will be submitted automatically when you are back online. Keep this app installed and signed in.</div>');
  }

  async function sendScan(url, payload) {
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await response.json().catch(() => ({ ok: false, message: 'The attendance service is temporarily unavailable. Please try again.' }));
    return data;
  }

  async function syncQueuedScans() {
    const queue = queuedScans();
    if (!queue.length || !navigator.onLine) return;
    const remaining = [];
    for (const item of queue) {
      try {
        await sendScan(item.url, item.payload);
      } catch (error) {
        remaining.push(item);
      }
    }
    localStorage.setItem(QUEUE_KEY, JSON.stringify(remaining));
    updateOfflineStatus();
  }

  window.addEventListener('online', syncQueuedScans);
  updateOfflineStatus();
  syncQueuedScans();

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

  async function submit(token) {
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
      let data;
      try {
        data = await sendScan(submitUrl, payload);
      } catch (error) {
        if (!navigator.onLine || error instanceof TypeError) {
          queueScan(submitUrl, payload);
          return;
        }
        throw error;
      }

      if (data.ok) {
        completed = !OFFICIAL;
        show(true, ACTIVITY
          ? '<div class="fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>' + data.message + '</div>'
          :
          '<div class="fw-semibold mb-2"><i class="bi bi-check-circle-fill me-1"></i>' + data.message + '</div>' +
          '<ul class="small mb-0 ps-3">' +
            '<li>Event: ' + data.event + '</li>' +
            '<li>Base points: ' + data.breakdown.base + '</li>' +
            '<li>Engagement bonus: +' + data.breakdown.early_bonus + '</li>' +
            '<li class="fw-semibold">Total: ' + data.breakdown.total + '</li>' +
          '</ul>');
        stopCamera();
      } else {
        const scanMessage = data.message || 'Attendance was not recorded. That scan could not be accepted.';
        if (OFFICIAL) {
          manualForm?.classList.remove('d-none');
          manualToggle?.classList.add('d-none');
          show(false, '<div class="fw-semibold"><i class="bi bi-exclamation-triangle-fill me-1"></i>' + scanMessage + '</div><div class="small mt-1">You can keep trying the scanner or enter the resident\'s Unique ID Number below.</div>');
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

  manualForm?.addEventListener('submit', async function (event) {
    event.preventDefault();
    if (completed || busy || currentAttendanceMode !== 'resident') return;
    busy = true;
    try {
      const uniqueId = document.getElementById('unique-id');
      if (!uniqueId.value.trim()) {
        show(false, 'Enter the resident unique ID from their ID card.');
        return;
      }
      const coords = await position();
      const res = await fetch(@json(route('attendance.check-by-id')), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({
          announcement_id: selectedAnnouncementId,
          unique_id: uniqueId.value,
          latitude: coords.latitude,
          longitude: coords.longitude
        })
      });
      const data = await res.json().catch(() => ({
        ok: false,
        message: 'The attendance service is temporarily unavailable. Please try again.'
      }));
      if (data.ok) {
        uniqueId.value = '';
        show(true, '<div class="fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Attendance recorded successfully.</div><div class="small mt-1">' + data.resident + ' · ' + data.message + '</div>');
      } else {
        show(false, data.message || 'Unique QR ID not found. Please check the ID and try again.');
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
      show(false, 'Switch to "Record Resident" mode to use manual ID entry.');
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
        : 'Select the resident\'s event before starting the scanner.');
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
        { fps: 10, qrbox: { width: 220, height: 220 } },
        text => {
          if (busy || completed) return;
          try {
            scanner.pause(true);
            scannerPaused = true;
          } catch (e) {}
          submit(text);
        },
        () => {}
      );
      scannerRunning = true;
      const scanMessage = OFFICIAL 
        ? (currentAttendanceMode === 'own' 
          ? 'Scanning for the event QR code…' 
          : 'Scanning for the resident QR code or event QR…')
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
        ? cameraMessage + '<div class="small fw-semibold mt-2">Use the Resident Unique ID Number fallback below if scanning is unavailable.</div>'
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
    submit(PREFILLED);
  }
})();
</script>
@endpush

