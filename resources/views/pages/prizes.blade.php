@extends('layouts.app')

@section('title', 'Manage Prizes')

@section('content')

    {{-- Header --}}
    <div class="page-header p-3 p-lg-4 mb-3">
        <div class="row align-items-center position-relative" style="z-index: 1;">
            <div class="col">
                <h2 class="h4 fw-bold mb-1"><i class="bi bi-gift-fill me-2"></i>Manage Prizes</h2>
                <p class="small mb-0 opacity-75">Edit the prizes on the spin wheel — changes apply instantly</p>
            </div>
            <div class="col-auto d-flex gap-2">
                <a href="{{ route('spin') }}" class="btn btn-yg btn-sm fw-semibold rounded-3">
                    <i class="bi bi-eye me-1"></i>Show wheel
                </a>
                @if ($prizes->count() < $maxPrizes)
                    <button class="btn btn-yg btn-sm fw-semibold rounded-3" data-bs-toggle="modal" data-bs-target="#addPrizeModal">
                        <i class="bi bi-plus-circle me-1"></i>Add Prize
                    </button>
                @endif
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger rounded-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $errors->first() }}
        </div>
    @endif

    {{-- Prize table --}}
    <div class="card yg-card overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr class="small text-secondary text-uppercase">
                        <th class="ps-4 py-3" style="width: 80px;">Color</th>
                        <th class="py-3">Label</th>
                        <th class="py-3">Type</th>
                        <th class="pe-4 py-3 text-end" style="width: 160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($prizes as $prize)
                        <tr>
                            <td class="ps-4">
                                <span class="prize-dot d-inline-block align-middle" style="background: {{ $prize->color }}; width: 26px; height: 26px; border-radius: 8px;"></span>
                            </td>
                            <td class="fw-semibold">
                                <i class="bi {{ $prize->icon }} text-secondary me-2"></i>{{ $prize->label }}
                            </td>
                            <td>
                                @if ($prize->prize_type === 'none')
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill"><i class="bi {{ $prize->icon }} me-1"></i>No Prize</span>
                                @else
                                    <span class="badge badge-gold rounded-pill"><i class="bi {{ $prize->icon }} me-1"></i>{{ $prize->type_label }}</span>
                                @endif
                            </td>
                            <td class="pe-4 text-end">
                                <button class="btn btn-yg-outline btn-sm me-1" data-bs-toggle="modal" data-bs-target="#editPrizeModal{{ $prize->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('prizes.destroy', $prize) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Remove “{{ $prize->label }}” from the wheel?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-3">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <p class="small text-secondary mt-3">
        <i class="bi bi-info-circle me-1"></i>The wheel holds between 2 and {{ $maxPrizes }} prizes. Segments are sized equally, so every prize has the same odds.
    </p>

    {{-- Add prize modal --}}
    <div class="modal fade" id="addPrizeModal" tabindex="-1" aria-labelledby="addPrizeLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 20px;">
                <form action="{{ route('prizes.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="addPrizeLabel"><i class="bi bi-plus-circle me-2 text-yg"></i>Add Prize</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        @include('pages.partials.prize-fields', ['prize' => null, 'suffix' => 'add'])
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light fw-semibold rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-yg"><i class="bi bi-plus-circle me-2"></i>Add Prize</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit prize modals --}}
    @foreach ($prizes as $prize)
        <div class="modal fade" id="editPrizeModal{{ $prize->id }}" tabindex="-1" aria-labelledby="editPrizeLabel{{ $prize->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0" style="border-radius: 20px;">
                    <form action="{{ route('prizes.update', $prize) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold" id="editPrizeLabel{{ $prize->id }}"><i class="bi bi-pencil-square me-2 text-yg"></i>Edit Prize</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            @include('pages.partials.prize-fields', ['prize' => $prize, 'suffix' => $prize->id])
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light fw-semibold rounded-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-yg"><i class="bi bi-check-circle me-2"></i>Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

@endsection
