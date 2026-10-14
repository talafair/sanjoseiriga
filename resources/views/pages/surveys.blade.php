@extends('layouts.app')

@section('title', 'Surveys')

@section('content')
<div class="page-header p-3 p-lg-4 mb-3"><h2 class="h4 fw-bold mb-1"><i class="bi bi-bar-chart-line me-2"></i>Community surveys</h2><p class="small mb-0 opacity-75">Share your feedback with the barangay.</p></div>
<div class="row g-3">
	@forelse ($surveys as $survey)
		<div class="col-md-6">
			<div class="card yg-card h-100">
				<div class="card-body p-4 d-flex flex-column">
					<h5 class="fw-bold">{{ $survey->title }}</h5>
					@if ($survey->description)
						<p class="text-secondary">{{ $survey->description }}</p>
					@endif
					<div class="small text-secondary mb-3">
					{{ count($survey->questions) }} questions · Due {{ $survey->due_at?->format('M j, Y g:i A') }} · {{ $survey->points }} points
						@if ($survey->event)
							· {{ $survey->event->title }}
						@endif
					</div>
					<div class="mt-auto">
						@if ($answered->contains($survey->id))
							<span class="badge badge-yg"><i class="bi bi-check-circle me-1"></i>Answered</span>
						@else
							<a href="{{ route('surveys.show', $survey) }}" class="btn btn-yg">Answer survey <i class="bi bi-arrow-right ms-1"></i></a>
						@endif
					</div>
				</div>
			</div>
		</div>
	@empty
		<div class="col-12">
			<div class="card yg-card p-5 text-center text-secondary">No surveys are available for you.</div>
		</div>
	@endforelse
</div>
@endsection
