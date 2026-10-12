@extends('layouts.app')

@section('title', $survey->title)

@section('content')
<div class="row justify-content-center">
	<div class="col-lg-8">
		<a href="{{ route('surveys.index') }}" class="small link-secondary"><i class="bi bi-arrow-left me-1"></i>All surveys</a>
		<div class="card yg-card mt-3">
			<div class="card-body p-4">
				<h3 class="fw-bold">{{ $survey->title }}</h3>
				<div class="small text-secondary mb-3">Due {{ $survey->due_at?->format('M j, Y g:i A') }} · {{ $survey->points }} points for submitting</div>
				@if ($survey->description)
					<p class="text-secondary">{{ $survey->description }}</p>
				@endif

				@if ($response)
					<div class="alert alert-success">You submitted this survey on {{ $response->submitted_at->format('M j, Y g:i A') }}.</div>
				@else
					<form method="POST" action="{{ route('surveys.respond', $survey) }}">
						@csrf
						<div class="alert alert-light border small"><strong>Choose one answer for each question:</strong> Strongly Disagree, Disagree, Neutral, Agree, or Strongly Agree.</div>
						@php($sectionHeadings = collect($survey->sections ?? [])->keyBy('start'))
						@foreach ($survey->questions as $index => $question)
							@if (filled($sectionHeadings->get($index)['heading'] ?? null))
								<h5 class="fw-bold mt-4 mb-3">{{ $sectionHeadings->get($index)['heading'] }}</h5>
							@endif
							<fieldset class="mb-4">
								<legend class="h6 fw-semibold">{{ $index + 1 }}. {{ $question }}</legend>
								<div class="d-flex flex-wrap gap-3">
									@foreach ([1 => 'Strongly Disagree', 2 => 'Disagree', 3 => 'Neutral', 4 => 'Agree', 5 => 'Strongly Agree'] as $value => $label)
										<div class="form-check">
											<input class="form-check-input" type="radio" name="answers[{{ $index }}]" id="answer{{ $index }}{{ $value }}" value="{{ $value }}" required>
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
