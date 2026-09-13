@extends('layouts.app')

@section('title', 'My Resident ID')

@section('content')
<div class="row justify-content-center">
  <div class="col-md-7 col-lg-6">

    <h4 class="fw-bold mb-1">Your resident ID</h4>
    <p class="text-secondary">Officials scan this at the barangay hall. Keep a copy on your phone.</p>

    {{-- This whole block becomes the saved image --}}
    <div id="id-card" class="card yg-card overflow-hidden">
      <div class="card-header bg-dark text-white py-3">
        <div class="d-flex justify-content-between align-items-start gap-3">
          <div>
            <div class="small text-uppercase opacity-75" style="letter-spacing:.12em;">
              Barangay San Jose &middot; Iriga City
            </div>
            <div class="fw-bold">TalaFair Resident ID<span class="text-yg">.</span></div>
          </div>
          <span class="badge {{ $user->is_verified ? 'text-bg-success' : 'text-bg-danger' }} rounded-pill text-nowrap">
            <i class="bi {{ $user->is_verified ? 'bi-shield-check' : 'bi-shield-x' }} me-1"></i>{{ $user->is_verified ? 'Verified' : 'Unverified' }}
          </span>
        </div>
      </div>

      <div class="card-body p-4">
           <div class="row g-4 flex-nowrap id-card-main">
          <div class="col-auto text-center">
            <img src="{{ $user->avatar_url }}" alt="" width="96" height="96"
              class="rounded-3 border object-fit-cover id-card-avatar">
            <div class="mt-3 id-card-qr">{!! $qrSvg !!}</div>
          </div>

          <div class="col">
            <div class="mb-3">
              <div class="small text-uppercase text-secondary">Full name</div>
              <div class="fw-bold">{{ $user->full_name }}</div>
            </div>
            <div class="mb-3">
              <div class="small text-uppercase text-secondary">Gender</div>
              <div>{{ $user->gender_label ?? '—' }}</div>
            </div>
            <div class="mb-3">
              <div class="small text-uppercase text-secondary">Unique ID</div>
              <div class="fw-bold font-monospace fs-5 text-yg">{{ $user->unique_id }}</div>
            </div>
            <div>
              <div class="small text-uppercase text-secondary">Address</div>
              <div class="small">{{ $user->full_address }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="card-footer bg-body-secondary small text-secondary">
        Issued {{ $user->created_at->format('M j, Y') }} &middot; Property of Barangay San Jose
      </div>
    </div>

    <div class="d-flex gap-2 mt-3">
      <button type="button" id="save-id" class="btn btn-primary flex-fill fw-semibold">
        <i class="bi bi-download me-1"></i>Save to phone
      </button>
      <button type="button" id="print-id" class="btn btn-outline-secondary flex-fill fw-semibold">
        <i class="bi bi-printer me-1"></i>Print
      </button>
    </div>
    <img id="id-card-print-image" class="d-none" alt="Resident ID card">
    <p id="save-hint" class="form-text mt-2">
      On iPhone, press and hold the saved image to add it to Photos.
    </p>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
const idCard = document.getElementById('id-card');
const saveButton = document.getElementById('save-id');
const printButton = document.getElementById('print-id');
const printImage = document.getElementById('id-card-print-image');
const saveHint = document.getElementById('save-hint');

async function getIdCardImage() {
  const cardImages = [...idCard.querySelectorAll('img')];
  await Promise.all(cardImages.map((image) => image.complete
    ? Promise.resolve()
    : new Promise((resolve) => {
        image.addEventListener('load', resolve, { once: true });
        image.addEventListener('error', resolve, { once: true });
      })));

  const canvas = await html2canvas(idCard, {
    scale: 3,
    backgroundColor: '#ffffff',
    useCORS: true,
    imageTimeout: 10000,
  });

  return canvas.toDataURL('image/png');
}

async function prepareIdCardImage() {
  const imageData = await getIdCardImage();
  printImage.src = imageData;
  return imageData;
}

saveButton.addEventListener('click', async function () {

  this.disabled = true;
  this.innerHTML = 'Preparing image...';

  try {
    const imageData = await prepareIdCardImage();
    const link = document.createElement('a');
    link.download = 'talafair-id-{{ $user->unique_id }}.png';
    link.href = imageData;
    link.click();
    saveHint.textContent = 'Saved to your downloads. Open it to add it to Photos or Gallery.';
  } catch (e) {
    saveHint.textContent = 'The image could not be generated. Check that the card images are available and try again.';
  } finally {
    this.disabled = false;
    this.innerHTML = '<i class="bi bi-download me-1"></i>Save to phone';
  }
});

printButton.addEventListener('click', async function () {
  this.disabled = true;
  this.innerHTML = 'Preparing print...';

  try {
    await prepareIdCardImage();
    window.print();
  } catch (e) {
    saveHint.textContent = 'The image could not be generated. Check that the card images are available and try again.';
  } finally {
    this.disabled = false;
    this.innerHTML = '<i class="bi bi-printer me-1"></i>Print';
  }
});
</script>
@endpush

@push('styles')
<style>
  @media print {
    @page { margin: 0; }

    body * { visibility: hidden !important; }

    #id-card {
      display: none !important;
    }

    #id-card-print-image {
      visibility: visible !important;
      position: absolute;
      top: 0;
      left: 0;
      display: block !important;
      width: 86mm !important;
      max-width: 86mm;
      height: auto !important;
      margin: 0 !important;
    }
  }

  #id-card .id-card-main { align-items: flex-start; }
  #id-card .id-card-qr svg { display: block; width: 220px; height: 220px; max-width: 100%; }

  @media (max-width: 575.98px) {
    #id-card .card-body { padding: 1rem !important; }
    #id-card .id-card-main { gap: 1rem !important; }
    #id-card .id-card-avatar { width: 72px; height: 72px; }
    #id-card .id-card-qr { margin-top: .75rem !important; }
    #id-card .id-card-qr svg { width: 150px; height: 150px; }
    #id-card .card-footer { font-size: .7rem; }
  }
</style>
@endpush

