(() => {
  const QUEUE_KEY = 'talafair-attendance-queue';
  const ACCOUNT_KEY = 'talafair-attendance-account';
  const USER_KEY = 'talafair-attendance-user-id';
  const CAN_SCAN_KEY = 'talafair-can-scan-attendance';
  const ATTENDANCE_URL_KEY = 'talafair-attendance-url';
  const CHECK_URL_KEY = 'talafair-attendance-check-url';
  const CHECK_BY_ID_URL_KEY = 'talafair-attendance-check-by-id-url';
  const script = document.getElementById('attendance-sync-script');
  const userId = script?.dataset.userId;
  const csrfToken = script?.dataset.csrfToken;
  const attendanceStatus = document.getElementById('offline-status');
  let syncing = false;
  let queueChangedDuringSync = false;

  if (!userId || !csrfToken) {
    console.error('Offline attendance sync needs a signed-in user and CSRF token.');
    return;
  }

  try {
    const openEvents = JSON.parse(script.dataset.openEvents || '[]');
    if (!Array.isArray(openEvents)) throw new Error('Open event choices are not in the expected format.');
    localStorage.setItem(ACCOUNT_KEY, JSON.stringify({
      id: userId,
      name: script.dataset.accountName,
      role: script.dataset.accountRole,
      isSuperadmin: script.dataset.isSuperadmin === '1',
      canScanAttendance: script.dataset.canScanAttendance === '1',
      attendanceUrl: script.dataset.attendanceUrl,
      checkUrl: script.dataset.checkUrl,
      checkByIdUrl: script.dataset.checkByIdUrl,
      updatedAt: Date.now()
    }));
    localStorage.setItem(USER_KEY, userId);
    localStorage.setItem(CAN_SCAN_KEY, script.dataset.canScanAttendance === '1' ? '1' : '0');
    localStorage.setItem(ATTENDANCE_URL_KEY, script.dataset.attendanceUrl);
    localStorage.setItem(CHECK_URL_KEY, script.dataset.checkUrl);
    localStorage.setItem(CHECK_BY_ID_URL_KEY, script.dataset.checkByIdUrl);
    localStorage.setItem('talafair-offline-events', JSON.stringify(openEvents));
  } catch (error) {
    console.error('Unable to save the signed-in account for offline attendance.', error);
    showStatus('Offline attendance cannot be saved because this browser blocks local storage.', 'danger');
    return;
  }

  function readQueue() {
    const saved = localStorage.getItem(QUEUE_KEY);
    if (!saved) return [];
    const queue = JSON.parse(saved);
    if (!Array.isArray(queue)) throw new Error('Saved attendance scans are not in the expected format.');
    let updated = false;
    queue.forEach((item) => {
      if (!item.id) {
        item.id = `${Date.now()}-${Math.random().toString(36).slice(2)}`;
        updated = true;
      }
      if (!item.userId) {
        item.userId = userId;
        updated = true;
      }
    });
    if (updated) localStorage.setItem(QUEUE_KEY, JSON.stringify(queue));
    return queue;
  }

  function showStatus(message, tone = 'info') {
    if (attendanceStatus) {
      attendanceStatus.classList.remove('d-none');
      attendanceStatus.textContent = message;
      return;
    }

    let notice = document.getElementById('attendance-sync-notice');
    if (!notice) {
      notice = document.createElement('div');
      notice.id = 'attendance-sync-notice';
      notice.className = 'alert position-fixed bottom-0 end-0 m-3 shadow';
      notice.style.zIndex = '1080';
      notice.setAttribute('role', 'status');
      document.body.append(notice);
    }
    notice.classList.remove('alert-info', 'alert-success', 'alert-warning', 'alert-danger');
    notice.classList.add(`alert-${tone}`);
    notice.textContent = message;
  }

  function displayQueueStatus(queue) {
    if (!queue.length) return;
    const thisUserCount = queue.filter(item => !item.userId || String(item.userId) === String(userId)).length;
    const otherUserCount = queue.length - thisUserCount;
    const messages = [];
    if (otherUserCount) {
      messages.push(
        `${otherUserCount} saved attendance scan${otherUserCount === 1 ? '' : 's'} ${otherUserCount === 1 ? 'belongs' : 'belong'} to another account on this device. Sign in to that account to sync them.`
      );
    }
    if (thisUserCount) {
      messages.push(`${thisUserCount} saved attendance scan${thisUserCount === 1 ? '' : 's'} waiting to sync.`);
    }
    showStatus(messages.join(' '), otherUserCount ? 'warning' : 'info');
  }

  async function syncQueue() {
    if (syncing || !navigator.onLine) return;

    let queue;
    try {
      queue = readQueue();
    } catch (error) {
      console.error('Unable to read saved attendance scans.', error);
      showStatus('Saved attendance scans could not be read from this device. Do not clear browser storage.', 'danger');
      return;
    }

    const pending = queue.filter(item => !item.userId || String(item.userId) !== String(userId));
    const owned = queue.filter(item => item.userId && String(item.userId) === String(userId));
    if (!owned.length) {
      displayQueueStatus(pending);
      return;
    }

    syncing = true;
    const confirmedIds = new Set();
    const failedScans = new Map();
    let syncedCount = 0;
    try {
      for (const item of owned) {
        try {
          const endpoint = new URL(item.url, window.location.href);
          const knownEndpoints = [
            localStorage.getItem(CHECK_URL_KEY),
            localStorage.getItem(CHECK_BY_ID_URL_KEY)
          ].filter(Boolean).map(url => new URL(url, window.location.href).pathname);
          const isParticipationEndpoint = /(?:^|\/)manage\/announcements\/\d+\/participation$/.test(endpoint.pathname);
          if (endpoint.origin !== window.location.origin
            || (!knownEndpoints.includes(endpoint.pathname) && !isParticipationEndpoint)) {
            failedScans.set(item.id, {
              ...item,
              syncError: 'This saved scan has an invalid destination and was not sent.'
            });
            continue;
          }

          const response = await fetch(endpoint.href, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
              'Accept': 'application/json'
            },
            body: JSON.stringify(item.payload)
          });
          const data = await response.json().catch(() => null);
          if (data?.ok || data?.message === 'Attendance is already recorded.') {
            confirmedIds.add(item.id);
            syncedCount++;
          } else {
            failedScans.set(item.id, {
              ...item,
              syncError: data?.message || (
                response.status === 401 || response.status === 419
                  ? 'Sign in again with the account that made this scan.'
                  : 'The attendance service did not confirm this scan.'
              )
            });
          }
        } catch (error) {
          failedScans.set(item.id, {
            ...item,
            syncError: 'Could not reach the attendance service. This scan remains saved on this device.'
          });
        }
      }

      const latestQueue = readQueue()
        .filter(item => !confirmedIds.has(item.id))
        .map(item => failedScans.get(item.id) || item);
      localStorage.setItem(QUEUE_KEY, JSON.stringify(latestQueue));

      const failedOwned = latestQueue.filter(item => item.userId && String(item.userId) === String(userId));
      const waitingOtherUser = latestQueue.length - failedOwned.length;
      const messages = [];
      if (syncedCount) {
        messages.push(`${syncedCount} offline attendance scan${syncedCount === 1 ? '' : 's'} synced and recorded in TalaFair.`);
      }
      if (failedOwned.length) {
        messages.push(`${failedOwned.length} scan${failedOwned.length === 1 ? '' : 's'} could not be confirmed and remain saved: ${failedOwned[0].syncError}`);
      }
      if (waitingOtherUser) {
        messages.push(`${waitingOtherUser} saved scan${waitingOtherUser === 1 ? '' : 's'} ${waitingOtherUser === 1 ? 'belongs' : 'belong'} to another account on this device.`);
      }
      if (messages.length) showStatus(messages.join(' '), failedOwned.length || waitingOtherUser ? 'warning' : 'success');
    } catch (error) {
      console.error('Unable to update saved attendance scans after syncing.', error);
      showStatus('Could not update the saved attendance queue. Scans remain stored on this device.', 'danger');
    } finally {
      syncing = false;
      if (queueChangedDuringSync) {
        queueChangedDuringSync = false;
        syncQueue();
      }
    }
  }

  window.addEventListener('online', syncQueue);
  window.addEventListener('attendance-queue-updated', () => {
    if (syncing) {
      queueChangedDuringSync = true;
      return;
    }
    syncQueue();
  });
  window.addEventListener('focus', syncQueue);
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') syncQueue();
  });
  document.querySelectorAll('form[action$="/logout"]').forEach((form) => {
    form.addEventListener('submit', () => {
      localStorage.removeItem(ACCOUNT_KEY);
      localStorage.removeItem(USER_KEY);
      localStorage.removeItem(CAN_SCAN_KEY);
      localStorage.removeItem(ATTENDANCE_URL_KEY);
      localStorage.removeItem(CHECK_URL_KEY);
      localStorage.removeItem(CHECK_BY_ID_URL_KEY);
      localStorage.removeItem('talafair-offline-events');
    });
  });
  try {
    displayQueueStatus(readQueue());
    syncQueue();
  } catch (error) {
    console.error('Unable to initialize saved attendance synchronization.', error);
    showStatus('Saved attendance scans could not be read from this device. Do not clear browser storage.', 'danger');
  }
})();
