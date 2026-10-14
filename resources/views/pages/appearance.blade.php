@extends('layouts.app')

@section('title', 'Home Background')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-7">
    <h4 class="fw-bold mb-1"><i class="bi bi-image me-2 text-yg"></i>Home page background</h4>
    <p class="text-secondary">Shown behind the greeting on every resident's home page.</p>

    <div class="card yg-card overflow-hidden">
      @if ($backgroundUrl)
        <img src="{{ $backgroundUrl }}" data-image-fallback="{{ asset('images/image-placeholder.svg') }}" alt="" class="w-100 object-fit-cover" style="height:12rem;">
      @else
        <div class="d-flex align-items-center justify-content-center text-secondary bg-body-secondary"
             style="height:12rem;">
          <span><i class="bi bi-image me-1"></i>No background set</span>
        </div>
      @endif

      <div class="card-body p-4">
        <form method="POST" action="{{ route('appearance.update') }}" enctype="multipart/form-data">
          @csrf
          <input type="file" name="background" accept="image/*" required
                 class="form-control @error('background') is-invalid @enderror">
          @error('background')<div class="invalid-feedback">{{ $message }}</div>@enderror
          <button class="btn btn-primary fw-semibold mt-3 px-4">
            <i class="bi bi-upload me-1"></i>Upload background
          </button>
        </form>

        @if ($backgroundUrl)
          <form method="POST" action="{{ route('appearance.destroy') }}" class="mt-2">
            @csrf @method('DELETE')
            <button class="btn btn-link btn-sm text-danger p-0">Remove background</button>
          </form>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
