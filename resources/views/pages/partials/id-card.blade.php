<article id="{{ $cardId ?? 'id-card' }}" class="card yg-card overflow-hidden tala-id-card">
  <header class="tala-id-header">
    <div class="tala-id-brand">
      <img src="{{ asset('images/talafair-logo.png') }}" alt="TalaFair logo" class="tala-id-logo">
      <div>
        <div class="tala-id-location">Barangay San Jose · Iriga City</div>
        <div class="tala-id-title">TalaFair {{ $user->isGuest() ? 'Guest' : 'Resident' }} ID<span class="text-yg">.</span></div>
      </div>
    </div>
    <span class="badge {{ $user->is_verified ? 'text-bg-success' : 'text-bg-danger' }} rounded-pill tala-id-status">
      <i class="bi {{ $user->is_verified ? 'bi-shield-check' : 'bi-shield-x' }} me-1"></i>{{ $user->is_verified ? 'Verified' : 'Unverified' }}
    </span>
  </header>
  <div class="tala-id-content">
    <img src="{{ $user->avatar_url }}" data-image-fallback="{{ asset('images/default-avatar.svg') }}" alt="{{ $user->full_name }} profile photo" class="tala-id-avatar">
    <div class="tala-id-information">
      <div class="tala-id-name">{{ $user->full_name }}</div>
      <div class="tala-id-detail"><span>Gender</span>{{ $user->gender_label ?? '—' }}</div>
      <div class="tala-id-unique">{{ $user->unique_id }}</div>
      <div class="tala-id-address">{{ $user->full_address }}</div>
    </div>
    <div class="tala-id-qr" aria-label="Signed resident QR code">{!! $qrSvg !!}</div>
  </div>
  <footer class="tala-id-footer">
    <span>Issued {{ $user->created_at->format('M j, Y') }}</span>
    <span>Property of Barangay San Jose</span>
  </footer>
</article>
