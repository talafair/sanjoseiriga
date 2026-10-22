@extends('layouts.app')

@section('title', $survey->title)

@section('content')
<div class="row justify-content-center">
	<div class="col-lg-8">
		<a href="{{ route('surveys.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 mb-3"><i class="bi bi-arrow-left me-1"></i>All surveys</a>
		<div class="card yg-card mt-3">
			<div class="card-body p-4">
				<h3 class="fw-bold">{{ $survey->title }}</h3>
				<div class="small text-secondary mb-3">Due {{ $survey->due_at?->format('M j, Y g:i A') }} · {{ $survey->points }} points for submitting</div>
				@if ($survey->description)
					<p class="text-secondary">{{ $survey->description }}</p>
				@endif

				@if ($response)
					<div class="alert alert-success">You submitted this survey on {{ $response->submitted_at->format('M j, Y g:i A') }}.</div>
				@elseif ($survey->due_at && now()->greaterThanOrEqualTo($survey->due_at))
					<div class="alert alert-secondary mb-0" role="status">
						This survey closed on {{ $survey->due_at->format('M j, Y g:i A') }}. Responses are no longer accepted.
					</div>
				@else
					<form method="POST" action="{{ route('surveys.respond', $survey) }}">
						@csrf
						<div class="alert alert-light border small"><strong>Choose one answer for each question:</strong> Strongly Agree, Agree, Neutral, Disagree, or Strongly Disagree.</div>
						@php
							$sectionMarkers = array_values($survey->sections ?: [['heading' => 'Questions', 'start' => 0]]);
							$sectionHeadings = collect($sectionMarkers)->mapWithKeys(fn ($section, $sectionIndex) => [
								(int) $section['start'] => filled($section['heading'] ?? null) ? $section['heading'] : 'Section ' . ($sectionIndex + 1),
							]);
							$questionNumber = 0;
						@endphp
						@foreach ($survey->questions as $index => $question)
							@if ($sectionHeadings->has($index))
								@php
									$questionNumber = 0;
								@endphp
								<h5 class="fw-bold mt-4 mb-3">{{ $sectionHeadings->get($index) }}</h5>
							@endif
							@php
								$questionNumber++;
							@endphp
							<fieldset class="mb-4">
								<legend class="h6 fw-semibold">{{ $questionNumber }}. {{ $question }}</legend>
								<div class="d-flex flex-wrap gap-3">
									@foreach ([5 => 'Strongly Agree', 4 => 'Agree', 3 => 'Neutral', 2 => 'Disagree', 1 => 'Strongly Disagree'] as $value => $label)
										<div class="form-check">
											<input class="form-check-input" type="radio" name="answers[{{ $index }}]" id="answer{{ $index }}{{ $value }}" value="{{ $value }}" required @checked(old('answers.' . $index) !== null && (int) old('answers.' . $index) === $value)>
											<label class="form-check-label" for="answer{{ $index }}{{ $value }}">{{ $label }}</label>
										</div>
									@endforeach
								</div>
							</fieldset>
						@endforeach
						@if ($survey->suggestion_enabled)
							<div class="mb-3">
								<label for="suggestion" class="form-label fw-semibold">Suggestion box <span class="text-secondary fw-normal">(optional)</span></label>
								<textarea id="suggestion" name="suggestion" class="form-control" rows="4" placeholder="Share an idea or suggestion"></textarea>
							</div>
						@endif
						<button class="btn btn-yg"><i class="bi bi-send me-1"></i>Submit response</button>
					</form>
				@endif
			</div>
		</div>
	</div>
</div>
@endsection
