@extends('layouts.app')

@section('title', 'Print Resident IDs')

@section('content')
<div class="bulk-id-screen">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h4 class="fw-bold mb-1">Print Resident IDs</h4>
      <p class="text-secondary mb-0">{{ $cardPages->sum(fn ($cards) => $cards->count()) }} existing TalaFair ID cards · {{ ['letter' => '8.5 × 11 in', 'long' => '8.5 × 13 in', 'a4' => 'A4'][$paperSize] }} paper</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print ID Sheet</button>
  </div>
</div>
<div id="bulk-id-print" class="bulk-id-print paper-{{ $paperSize }}">
  @foreach ($cardPages as $pageIndex => $cards)
    <section class="bulk-id-sheet" aria-label="ID print sheet {{ $pageIndex + 1 }}">
      @foreach ($cards as $card)
        @include('pages.partials.id-card', [
            'user' => $card['user'],
            'qrSvg' => $card['qrSvg'],
            'cardId' => 'id-card-' . $card['user']->id,
        ])
      @endforeach
    </section>
  @endforeach
</div>
@endsection

@push('styles')
<style>
  @include('pages.partials.id-card-styles')
  .bulk-id-print { margin: 0 auto; }
  .bulk-id-sheet {
    display: grid;
    grid-template-columns: repeat(2, 85.6mm);
    grid-auto-rows: 53.98mm;
    align-content: start;
    gap: 5mm 8mm;
    width: max-content;
    margin: 0 auto 1.5rem;
    padding: 5mm;
    background: #f1f3f2;
  }
  .bulk-id-sheet .tala-id-card { position: relative; break-inside: avoid; page-break-inside: avoid; }
  @media screen and (max-width: 700px) {
    .bulk-id-sheet { width: 100%; box-sizing: border-box; grid-template-columns: minmax(0, 85.6mm); justify-content: center; }
    .bulk-id-sheet .tala-id-card { width: min(85.6mm, calc(100vw - 4rem)); height: auto; aspect-ratio: 85.6 / 53.98; }
  }
  @media print {
    @page { size: {{ ['letter' => 'letter', 'long' => '215.9mm 330.2mm', 'a4' => 'A4'][$paperSize] }} portrait; margin: 8mm; }
    body * { visibility: hidden !important; }
    #bulk-id-print, #bulk-id-print * { visibility: visible !important; }
    #bulk-id-print { position: absolute !important; inset: 0 !important; width: 100% !important; margin: 0 !important; }
    .bulk-id-screen, .yg-topbar, .yg-navbar, .yg-footer, .mobile-nav-backdrop { display: none !important; }
    .bulk-id-sheet {
      width: 100%;
      height: {{ ['letter' => '263.4mm', 'long' => '314.2mm', 'a4' => '280.99mm'][$paperSize] }};
      box-sizing: border-box;
      grid-template-columns: repeat(2, 85.6mm);
      grid-auto-rows: 53.98mm;
      align-content: start;
      justify-content: center;
      gap: 5mm 8mm;
      margin: 0;
      padding: 0;
      background: transparent;
      break-after: page;
      page-break-after: always;
    }
    .bulk-id-sheet:last-child { break-after: auto; page-break-after: auto; }
    .bulk-id-sheet .tala-id-card {
      width: 85.6mm !important;
      height: 53.98mm !important;
      margin: 0 !important;
      border-radius: 0 !important;
      box-shadow: none !important;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    .tala-id-card, .tala-id-card * { visibility: visible !important; }
    .tala-id-card { position: relative !important; inset: auto !important; }
  }
</style>
@endpush
