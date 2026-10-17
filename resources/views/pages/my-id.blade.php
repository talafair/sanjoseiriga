@extends('layouts.app')

@section('title', ($printOnly ?? false) ? 'Print TalaFair ID' : 'My ' . ($user->isGuest() ? 'Guest' : 'Resident') . ' ID')

@section('content')
<div class="id-preview-shell">
  <div class="id-preview-heading">
    <h4 class="fw-bold mb-1">{{ ($printOnly ?? false) ? 'Print TalaFair ID' : 'Your ' . ($user->isGuest() ? 'guest' : 'resident') . ' ID' }}</h4>
    <p class="text-secondary mb-0">{{ ($printOnly ?? false) ? 'Print this existing resident ID for ' . $user->full_name . '.' : 'Officials scan this at the barangay hall. Keep a copy on your phone.' }}</p>
  </div>
  @include('pages.partials.id-card', ['user' => $user, 'qrSvg' => $qrSvg])
  <div class="d-flex gap-2 mt-3 id-card-actions">
    <button type="button" id="print-id" class="btn btn-outline-secondary flex-fill fw-semibold">
      <i class="bi bi-printer me-1"></i>Print TalaFair ID
    </button>
    <button type="button" id="download-id" class="btn btn-primary flex-fill fw-semibold">
      <i class="bi bi-download me-1"></i>Download as PNG
    </button>
  </div>
  <p id="export-status" class="form-text mt-2" role="status" aria-live="polite"></p>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
const idCard = document.getElementById('id-card');
const printButton = document.getElementById('print-id');
const downloadButton = document.getElementById('download-id');
const exportStatus = document.getElementById('export-status');

async function waitForCardAssets() {
  if (document.fonts?.ready) await document.fonts.ready;

  await Promise.all([...idCard.querySelectorAll('img')].map(async (image) => {
    if (!image.complete) {
      await new Promise((resolve, reject) => {
        image.addEventListener('load', resolve, { once: true });
        image.addEventListener('error', reject, { once: true });
      });
    }

    if (image.naturalWidth === 0 && image.dataset.imageFallback && image.src !== image.dataset.imageFallback) {
      image.src = image.dataset.imageFallback;
    }
    await image.decode();
  }));
}

async function renderCardPng() {
  await waitForCardAssets();
  const canvas = await html2canvas(idCard, {
    scale: 300 / 96,
    backgroundColor: '#ffffff',
    useCORS: true,
    imageTimeout: 10000,
  });

  return canvas.toDataURL('image/png');
}

printButton.addEventListener('click', () => window.print());
downloadButton.addEventListener('click', async () => {
  downloadButton.disabled = true;
  exportStatus.textContent = 'Preparing 300 DPI TalaFair ID image…';

  try {
    const imageData = await renderCardPng();
    const link = document.createElement('a');
    link.download = 'talafair-id-{{ $user->unique_id }}.png';
    link.href = imageData;
    link.click();
    exportStatus.textContent = 'TalaFair ID PNG downloaded.';
  } catch (error) {
    exportStatus.textContent = 'The ID image could not be prepared. Check the logo and profile photo, then try again.';
    console.error('TalaFair ID PNG export failed.', error);
  } finally {
    downloadButton.disabled = false;
  }
});
</script>
@endpush

@push('styles')
<style>
  @include('pages.partials.id-card-styles')
  @media print {
    @page { size: 85.60mm 53.98mm; margin: 0; }
    html, body { width: 85.60mm !important; height: 53.98mm !important; margin: 0 !important; padding: 0 !important; }
    body * { visibility: hidden !important; }
    #id-card, #id-card * { visibility: visible !important; }
    #id-card {
      position: absolute !important;
      inset: 0 auto auto 0 !important;
      width: 85.60mm !important;
      height: 53.98mm !important;
      margin: 0 !important;
      border-radius: 0 !important;
      box-shadow: none !important;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    .id-preview-heading, .id-card-actions, #export-status,
    .yg-topbar, .yg-navbar, .yg-footer, .mobile-nav-backdrop { display: none !important; }
  }
</style>
@endpush
