@extends('layouts.app')

@section('title', 'Manage Surveys')

@section('content')
<div class="page-header p-3 p-lg-4 mb-3"><h2 class="h4 fw-bold mb-1"><i class="bi bi-bar-chart-line me-2"></i>Community surveys</h2><p class="small mb-0 opacity-75">Publish questions for residents and guests to answer using a Likert scale.</p></div>

<div class="card yg-card mb-4"><div class="card-body p-4">
  <h5 class="fw-bold mb-3">Publish a survey</h5>
  <form method="POST" action="{{ route('surveys.store') }}" id="surveyForm">@csrf
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label fw-semibold">Title</label><input name="title" class="form-control" value="{{ old('title') }}" placeholder="Community event feedback" required></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Description <span class="text-secondary fw-normal">(optional)</span></label><textarea name="description" class="form-control" rows="4" placeholder="Tell residents what this survey is about">{{ old('description') }}</textarea></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Due date and time</label><input type="datetime-local" name="due_at" class="form-control" value="{{ old('due_at') }}" required></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Points for answering</label><input type="number" name="points" class="form-control" min="0" max="100000" value="{{ old('points', 0) }}" required><div class="form-text">Fixed points awarded after submission. No bonus.</div></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Audience</label><select name="audience" id="surveyAudience" class="form-select" required><option value="event_attendees">Attendees of an event</option><option value="public">All residents</option><option value="youth">Youth</option><option value="senior">Senior citizens</option><option value="family_heads">Family heads</option><option value="officials">Officials</option></select></div>
      <div class="col-md-6" id="surveyEventWrap"><label class="form-label fw-semibold">Event</label><select name="event_id" id="surveyEvent" class="form-select" required><option value="">Choose the event attended</option>@foreach ($events as $event)<option value="{{ $event->id }}">{{ $event->title }}{{ $event->event_start_at ? ' · ' . $event->event_start_at->format('M j, Y') : '' }}</option>@endforeach</select></div>
      <div class="col-12">
        <label class="form-label fw-semibold">Question sections</label>
        @php
          $formSections = old('sections', [['heading' => '', 'questions' => ['']]]);
        @endphp
        <div id="surveySections">
          @foreach ($formSections as $sectionIndex => $section)
            @php
              $questionNumber = 1;
            @endphp
            <section class="survey-question-section border rounded-3 p-3 mb-3" data-section>
              <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                <label class="form-label fw-semibold mb-0">Section header</label>
                <button type="button" class="btn btn-outline-danger btn-sm" data-remove-section>Remove section</button>
              </div>
              <input name="sections[{{ $sectionIndex }}][heading]" class="form-control mb-3" value="{{ $section['heading'] ?? '' }}" placeholder="For example: Event organization" maxlength="255">
              <div data-section-questions>
                @foreach ($section['questions'] ?? [] as $question)
                  <div class="input-group mb-2" data-question>
                    <span class="input-group-text" data-question-number>{{ $questionNumber++ }}</span>
                    <input name="sections[{{ $sectionIndex }}][questions][]" class="form-control" value="{{ $question }}" placeholder="Add a question" required>
                    <button type="button" class="btn btn-outline-danger" data-remove-question aria-label="Remove question"><i class="bi bi-x-lg"></i></button>
                  </div>
                @endforeach
              </div>
              <button type="button" class="btn btn-outline-success btn-sm" data-add-question><i class="bi bi-plus-circle me-1"></i>Add question</button>
            </section>
          @endforeach
        </div>
        <button type="button" id="addSurveySection" class="btn btn-outline-success btn-sm"><i class="bi bi-plus-circle me-1"></i>Add section</button>
        @error('sections')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
      </div>
      <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="suggestion_enabled" value="1" id="suggestionEnabled" checked><label class="form-check-label" for="suggestionEnabled">Include an optional suggestion box</label></div></div>
    </div>
    <button class="btn btn-yg mt-3"><i class="bi bi-send me-1"></i>Publish survey</button>
  </form>
</div></div>

<div class="card yg-card">
  <div class="card-header py-3 fw-bold">Published surveys</div>
  <div class="list-group list-group-flush">
    @forelse ($surveys as $survey)
      <div class="list-group-item">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <div class="fw-semibold">{{ $survey->title }}</div>
            <div class="small text-secondary">
              {{ count($survey->questions) }} questions · Due {{ $survey->due_at?->format('M j, Y g:i A') }}
              · {{ $survey->audience === 'event_attendees' ? 'Event attendees' : ucfirst(str_replace('_', ' ', $survey->audience)) }}
            </div>
          </div>
          <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="#surveySummary{{ $survey->id }}">Summary</a>
            <a class="btn btn-light btn-sm" href="{{ route('surveys.edit', $survey) }}" title="Edit survey"><i class="bi bi-pencil"></i></a>
            <form method="POST" action="{{ route('surveys.destroy', $survey) }}" onsubmit="return confirm('Delete this survey and its responses?');">
              @csrf @method('DELETE')
              <button class="btn btn-outline-danger btn-sm" title="Delete survey"><i class="bi bi-trash"></i></button>
            </form>
          </div>
        </div>
        @if ($survey->due_at && now()->lessThan($survey->due_at))
          <form method="POST" action="{{ route('surveys.remind', $survey) }}" class="border-top mt-3 pt-3">
            @csrf
            <label class="form-label fw-semibold" for="surveyReminderMessage{{ $survey->id }}">Remind the audience</label>
            <select name="kind" class="form-select" aria-label="Reminder type" required>
              <option value="survey_closing">Remind audience to complete this survey</option>
            </select>
            <input id="surveyReminderMessage{{ $survey->id }}" name="message" class="form-control mt-2" maxlength="500"
                   placeholder="Optional note" value="{{ old('message') }}">
            <button class="btn btn-primary btn-sm fw-semibold mt-2">Send reminder</button>
          </form>
        @endif
        @if (auth()->user()->isSuperadmin())
          <div class="border border-primary-subtle rounded-3 bg-primary-subtle p-3 mt-3">
            @if ($survey->due_at && now()->lessThan($survey->due_at) && $survey->respondent_options->isNotEmpty())
              <button class="btn btn-primary fw-semibold px-3 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#recordSurveyResponse{{ $survey->id }}" aria-expanded="false" aria-controls="recordSurveyResponse{{ $survey->id }}">
                <i class="bi bi-person-lines-fill me-2" aria-hidden="true"></i>Record response for resident or guest
                <i class="bi bi-chevron-down ms-1" aria-hidden="true"></i>
              </button>
              <p class="small text-body-secondary mt-2 mb-0">Enter a resident’s or guest’s answers on their behalf and award them the survey points.</p>
              <div class="collapse mt-3" id="recordSurveyResponse{{ $survey->id }}">
                <form method="POST" action="{{ route('surveys.responses.store', $survey) }}" class="bg-body border rounded-3 p-3">
                  @csrf
                  <div class="mb-3">
                    <label class="form-label fw-semibold" for="respondentUser{{ $survey->id }}">Resident or guest</label>
                    <select class="form-select" id="respondentUser{{ $survey->id }}" name="respondent_user_id" required>
                      <option value="">Select a resident or guest who has not answered</option>
                      @foreach ($survey->respondent_options as $respondent)
                        <option value="{{ $respondent->id }}">{{ $respondent->full_name }} · {{ ucfirst($respondent->role) }}{{ $respondent->unique_id ? ' · ' . $respondent->unique_id : '' }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="alert alert-light border small">Enter their answers as provided. Survey points will be awarded to their account.</div>
                  @php
                    $recordSectionMarkers = array_values($survey->sections ?: [['heading' => 'Questions', 'start' => 0]]);
                    $recordSectionHeadings = collect($recordSectionMarkers)->mapWithKeys(fn ($section, $sectionIndex) => [
                        (int) $section['start'] => filled($section['heading'] ?? null) ? $section['heading'] : 'Section ' . ($sectionIndex + 1),
                    ]);
                    $recordQuestionNumber = 0;
                  @endphp
                  @foreach ($survey->questions as $questionIndex => $question)
                    @if ($recordSectionHeadings->has($questionIndex))
                      @php
                        $recordQuestionNumber = 0;
                      @endphp
                      <h6 class="fw-bold mt-4">{{ $recordSectionHeadings->get($questionIndex) }}</h6>
                    @endif
                    @php
                      $recordQuestionNumber++;
                    @endphp
                    <fieldset class="mb-3">
                      <legend class="h6">{{ $recordQuestionNumber }}. {{ $question }}</legend>
                      <div class="d-flex flex-wrap gap-3">
                        @foreach ([5 => 'Strongly Agree', 4 => 'Agree', 3 => 'Neutral', 2 => 'Disagree', 1 => 'Strongly Disagree'] as $value => $label)
                          <div class="form-check">
                            <input class="form-check-input" type="radio" name="answers[{{ $questionIndex }}]" id="adminAnswer{{ $survey->id }}{{ $questionIndex }}{{ $value }}" value="{{ $value }}" required>
                            <label class="form-check-label" for="adminAnswer{{ $survey->id }}{{ $questionIndex }}{{ $value }}">{{ $label }}</label>
                          </div>
                        @endforeach
                      </div>
                    </fieldset>
                  @endforeach
                  @if ($survey->suggestion_enabled)
                    <div class="mb-3">
                      <label class="form-label fw-semibold" for="adminSuggestion{{ $survey->id }}">Suggestion <span class="text-secondary fw-normal">(optional)</span></label>
                      <textarea class="form-control" id="adminSuggestion{{ $survey->id }}" name="suggestion" rows="3" maxlength="2000"></textarea>
                    </div>
                  @endif
                  <button class="btn btn-yg"><i class="bi bi-check2 me-1"></i>Save resident response</button>
                </form>
              </div>
            @else
              <span class="small text-secondary">
                @if ($survey->due_at && now()->greaterThanOrEqualTo($survey->due_at))
                  This survey is closed.
                @else
                  No eligible residents or guests remain to answer.
                @endif
              </span>
            @endif
          </div>
        @endif
      </div>
    @empty
      <div class="list-group-item text-secondary">No surveys published yet.</div>
    @endforelse
  </div>
</div>

@foreach ($surveys as $survey)
  @php
    $responses = $survey->responses;
    $suggestions = $responses->filter(fn ($response) => filled($response->suggestion));
    $sectionMarkers = array_values($survey->sections ?: [['heading' => 'Questions', 'start' => 0]]);
    $sectionHeadings = collect($sectionMarkers)->mapWithKeys(fn ($section, $sectionIndex) => [
        (int) $section['start'] => filled($section['heading'] ?? null) ? $section['heading'] : 'Section ' . ($sectionIndex + 1),
    ]);
    $sectionSummaries = collect($sectionMarkers)->map(function ($section, $sectionIndex) use ($sectionMarkers, $survey, $responses) {
        $start = (int) $section['start'];
        $end = isset($sectionMarkers[$sectionIndex + 1]) ? (int) $sectionMarkers[$sectionIndex + 1]['start'] : count($survey->questions);
        $ratings = $responses->flatMap(fn ($response) => array_slice($response->answers ?? [], $start, $end - $start))
            ->filter(fn ($rating) => $rating !== null);

        return [
            'heading' => filled($section['heading'] ?? null) ? $section['heading'] : 'Section ' . ($sectionIndex + 1),
            'questions' => $end - $start,
            'ratings' => $ratings->count(),
            'mean' => $ratings->count() ? $ratings->avg() : null,
        ];
    });
    $weightedRatingCount = $sectionSummaries->sum('ratings');
    $overallWeightedMean = $weightedRatingCount
        ? $sectionSummaries->sum(fn ($section) => ($section['mean'] ?? 0) * $section['ratings']) / $weightedRatingCount
        : null;
    $respondentDetails = $responses->sortBy('submitted_at')->map(function ($response) use ($survey) {
        return [
            'user' => $response->user?->full_name ?? 'Unknown resident',
            'unique_id' => $response->user?->unique_id ?? '—',
            'submitted_at' => $response->submitted_at?->format('M j, Y g:i A') ?? '—',
            'answers' => $response->answers ?? [],
        ];
    })->values();
    $demographicRespondents = $responses->map(fn ($response) => $response->user)->filter();
    $ageBands = [
        'Under 18' => [0, 17],
        '18-24' => [18, 24],
        '25-34' => [25, 34],
        '35-44' => [35, 44],
        '45-54' => [45, 54],
        '55-64' => [55, 64],
        '65+' => [65, null],
    ];
    $ageCounts = collect($ageBands)->map(fn ($range) => $demographicRespondents
        ->filter(fn ($user) => $user->age !== null && $user->age >= $range[0] && ($range[1] === null || $user->age <= $range[1]))
        ->count());
    $ageCounts['Not specified'] = $demographicRespondents->filter(fn ($user) => $user->age === null)->count();
    $genderCounts = collect([
        'Female' => 'female',
        'Male' => 'male',
        'Others' => 'others',
    ])->map(fn ($gender) => $demographicRespondents->filter(fn ($user) => $user->gender === $gender)->count());
    $genderCounts['Not specified'] = $demographicRespondents->filter(fn ($user) => ! in_array($user->gender, ['female', 'male', 'others'], true))->count();
  @endphp
  <div class="card yg-card mt-4 survey-summary-print" id="surveySummary{{ $survey->id }}">
    <div class="card-header py-3 d-flex justify-content-between align-items-center gap-2 flex-wrap">
      <span class="fw-bold">{{ $survey->title }} summary</span>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge badge-soft">Due {{ $survey->due_at?->format('M j, Y g:i A') }}</span>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="printSurveySummary({{ $survey->id }})">Print summary</button>
      </div>
    </div>
    <div class="card-body p-4">
      <div class="row g-3 mb-4"><div class="col-6 col-md-3"><div class="bg-body-secondary rounded-3 p-3"><div class="h4 fw-bold mb-0">{{ number_format($survey->notified_count) }}</div><div class="small text-secondary">Notified</div></div></div><div class="col-6 col-md-3"><div class="bg-body-secondary rounded-3 p-3"><div class="h4 fw-bold mb-0">{{ $responses->count() }}</div><div class="small text-secondary">Answered</div></div></div><div class="col-6 col-md-3"><div class="bg-body-secondary rounded-3 p-3"><div class="h4 fw-bold mb-0">{{ max(0, $survey->notified_count - $responses->count()) }}</div><div class="small text-secondary">Not answered</div></div></div><div class="col-6 col-md-3"><div class="bg-body-secondary rounded-3 p-3"><div class="h4 fw-bold mb-0">{{ $overallWeightedMean !== null ? number_format($overallWeightedMean, 2) : '—' }}</div><div class="small text-secondary">Overall weighted mean / 5</div></div></div></div>
      <h6 class="fw-bold">Respondent demographics</h6>
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="table-responsive">
            <table class="table table-sm align-middle border rounded overflow-hidden">
              <thead class="table-light"><tr><th>Age group</th><th>Respondents</th></tr></thead>
              <tbody>
                @foreach ($ageCounts as $ageGroup => $count)
                  <tr><td>{{ $ageGroup }}</td><td>{{ $count }}</td></tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
        <div class="col-md-6">
          <div class="table-responsive">
            <table class="table table-sm align-middle border rounded overflow-hidden">
              <thead class="table-light"><tr><th>Gender</th><th>Respondents</th></tr></thead>
              <tbody>
                @foreach ($genderCounts as $gender => $count)
                  <tr><td>{{ $gender }}</td><td>{{ $count }}</td></tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <h6 class="fw-bold">Section means</h6>
      <div class="table-responsive mb-4">
        <table class="table table-sm align-middle border rounded overflow-hidden">
          <thead class="table-light">
            <tr><th>Section</th><th>Questions</th><th>Ratings</th><th>Mean / 5</th></tr>
          </thead>
          <tbody>
            @foreach ($sectionSummaries as $section)
              <tr>
                <td>{{ $section['heading'] }}</td>
                <td>{{ $section['questions'] }}</td>
                <td>{{ $section['ratings'] }}</td>
                <td>{{ $section['mean'] !== null ? number_format($section['mean'], 2) : '—' }}</td>
              </tr>
            @endforeach
            <tr class="table-light fw-bold">
              <td colspan="3">Overall weighted mean</td>
              <td>{{ $overallWeightedMean !== null ? number_format($overallWeightedMean, 2) : '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="table-responsive mb-4">
        <table class="table table-sm align-middle border rounded overflow-hidden">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Question</th>
              <th>Average</th>
              <th>Responses</th>
              <th>Strongly Disagree</th>
              <th>Disagree</th>
              <th>Neutral</th>
              <th>Agree</th>
              <th>Strongly Agree</th>
            </tr>
          </thead>
          <tbody>
            @php
              $questionNumber = 0;
            @endphp
            @foreach ($survey->questions as $index => $question)
              @if ($sectionHeadings->has($index))
                @php
                  $questionNumber = 0;
                @endphp
                <tr class="table-light"><th colspan="9">{{ $sectionHeadings->get($index) }}</th></tr>
              @endif
              @php
                $questionNumber++;
                $ratings = $responses->map(fn ($response) => $response->answers[$index] ?? null)->filter(fn ($rating) => $rating !== null);
                $distribution = [1 => $ratings->filter(fn ($rating) => (int) $rating === 1)->count(), 2 => $ratings->filter(fn ($rating) => (int) $rating === 2)->count(), 3 => $ratings->filter(fn ($rating) => (int) $rating === 3)->count(), 4 => $ratings->filter(fn ($rating) => (int) $rating === 4)->count(), 5 => $ratings->filter(fn ($rating) => (int) $rating === 5)->count()];
              @endphp
              <tr>
                <td>{{ $questionNumber }}</td>
                <td>{{ $question }}</td>
                <td>{{ $ratings->count() ? number_format($ratings->avg(), 2) : '—' }} / 5</td>
                <td>{{ $ratings->count() }}</td>
                <td>{{ $distribution[1] }}</td>
                <td>{{ $distribution[2] }}</td>
                <td>{{ $distribution[3] }}</td>
                <td>{{ $distribution[4] }}</td>
                <td>{{ $distribution[5] }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @if ($survey->suggestion_enabled)
        <hr><h6 class="fw-bold">Suggestions</h6>
        @forelse ($suggestions as $response)<div class="border-bottom py-2 small">{{ $response->suggestion }}</div>@empty<div class="small text-secondary">No suggestions yet.</div>@endforelse
      @endif

      @if ($respondentDetails->isNotEmpty())
        <hr>
        <h6 class="fw-bold">Survey responses</h6>
        @foreach ($respondentDetails as $respondent)
          <div class="border rounded p-3 mb-3">
            <div class="fw-semibold">{{ $respondent['user'] }}</div>
            <div class="small text-secondary">Unique ID: {{ $respondent['unique_id'] }} · Submitted: {{ $respondent['submitted_at'] }}</div>
            <ul class="mb-0 mt-2 small ps-3">
              @php
                $questionNumber = 0;
              @endphp
              @foreach ($survey->questions as $index => $question)
                @if ($sectionHeadings->has($index))
                  @php
                    $questionNumber = 0;
                  @endphp
                  <li class="list-unstyled fw-bold mt-2">{{ $sectionHeadings->get($index) }}</li>
                @endif
                @php
                  $questionNumber++;
                @endphp
                <li><span class="fw-semibold">{{ $questionNumber }}. {{ $question }}</span> — {{ $respondent['answers'][$index] ?? 'No answer' }} / 5</li>
              @endforeach
            </ul>
          </div>
        @endforeach
      @endif
    </div>
  </div>
@endforeach
@endsection

@push('scripts')
<script>
function printSurveySummary(surveyId) {
  const target = document.getElementById('surveySummary' + surveyId);
  if (!target) return;

  const others = document.querySelectorAll('.survey-summary-print');
  const root = document.documentElement;
  const originalTheme = root.dataset.theme;
  const originalBootstrapTheme = root.dataset.bsTheme;
  const originalColorScheme = root.style.colorScheme;
  const themeColor = document.querySelector('meta[name="theme-color"]');
  const originalThemeColor = themeColor?.content;

  document.body.classList.add('print-mode');
  root.dataset.theme = 'light';
  root.dataset.bsTheme = 'light';
  root.style.colorScheme = 'light';
  if (themeColor) themeColor.content = '#F8F9FA';
  others.forEach((section) => {
    section.classList.toggle('active', section === target);
  });

  let restoreTimer;
  const restorePrintState = () => {
    window.removeEventListener('afterprint', restorePrintState);
    window.clearTimeout(restoreTimer);
    document.body.classList.remove('print-mode');
    others.forEach((section) => section.classList.remove('active'));

    if (originalTheme === undefined) delete root.dataset.theme;
    else root.dataset.theme = originalTheme;
    if (originalBootstrapTheme === undefined) delete root.dataset.bsTheme;
    else root.dataset.bsTheme = originalBootstrapTheme;
    root.style.colorScheme = originalColorScheme;
    if (themeColor && originalThemeColor !== undefined) themeColor.content = originalThemeColor;
  };

  window.addEventListener('afterprint', restorePrintState, { once: true });
  restoreTimer = window.setTimeout(restorePrintState, 1000);
  window.print();
}

(() => {
  const audience = document.getElementById('surveyAudience'); const eventWrap = document.getElementById('surveyEventWrap'); const event = document.getElementById('surveyEvent'); const sections = document.getElementById('surveySections'); const addSection = document.getElementById('addSurveySection');
  if (audience) {
    audience.addEventListener('change', () => { const eventAttendees = audience.value === 'event_attendees'; eventWrap.hidden = !eventAttendees; event.required = eventAttendees; });
  }
  if (!sections || !addSection) return;

  const renumber = () => {
    let questionNumber = 1;
    const sectionItems = sections.querySelectorAll('[data-section]');
    sectionItems.forEach((section, sectionIndex) => {
      section.querySelector('input[name$="[heading]"]').name = `sections[${sectionIndex}][heading]`;
      section.querySelectorAll('[data-question]').forEach((question) => {
        question.querySelector('[data-question-number]').textContent = questionNumber++;
        question.querySelector('input').name = `sections[${sectionIndex}][questions][]`;
      });
      section.querySelector('[data-remove-section]').hidden = sectionItems.length === 1;
    });
  };

  const addQuestion = (section) => {
    const question = document.createElement('div');
    question.className = 'input-group mb-2';
    question.dataset.question = '';
    question.innerHTML = '<span class="input-group-text" data-question-number></span><input class="form-control" placeholder="Add a question" required><button type="button" class="btn btn-outline-danger" data-remove-question aria-label="Remove question"><i class="bi bi-x-lg"></i></button>';
    section.querySelector('[data-section-questions]').append(question);
    renumber();
    question.querySelector('input').focus();
  };

  const createSection = () => {
    const section = document.createElement('section');
    section.className = 'survey-question-section border rounded-3 p-3 mb-3';
    section.dataset.section = '';
    section.innerHTML = '<div class="d-flex justify-content-between align-items-center gap-2 mb-3"><label class="form-label fw-semibold mb-0">Section header</label><button type="button" class="btn btn-outline-danger btn-sm" data-remove-section>Remove section</button></div><input name="sections[0][heading]" class="form-control mb-3" placeholder="For example: Event organization" maxlength="255"><div data-section-questions></div><button type="button" class="btn btn-outline-success btn-sm" data-add-question><i class="bi bi-plus-circle me-1"></i>Add question</button>';
    sections.append(section);
    addQuestion(section);
    section.querySelector('input[name$="[heading]"]').focus();
  };

  addSection.addEventListener('click', createSection);
  sections.addEventListener('click', (event) => {
    const section = event.target.closest('[data-section]');
    if (!section) return;
    if (event.target.closest('[data-add-question]')) addQuestion(section);
    if (event.target.closest('[data-remove-question]')) {
      const questionItems = section.querySelectorAll('[data-question]');
      if (questionItems.length > 1) event.target.closest('[data-question]').remove();
      renumber();
    }
    if (event.target.closest('[data-remove-section]') && sections.querySelectorAll('[data-section]').length > 1) {
      section.remove();
      renumber();
    }
  });
  renumber();
})();
</script>
@endpush

@push('styles')
<style>
  @media print {
    body * { visibility: hidden; }
    .survey-summary-print, .survey-summary-print * { visibility: visible; }
    .survey-summary-print {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
      max-width: 100%;
      margin: 0 !important;
      box-shadow: none !important;
      border: none !important;
    }
    body:not(.print-mode) .survey-summary-print { display: none !important; }
    body.print-mode .survey-summary-print:not(.active) { display: none !important; }
    .survey-summary-print .btn,
    .survey-summary-print .d-none-print,
    .survey-summary-print .list-group,
    .survey-summary-print form,
    .survey-summary-print .page-header {
      display: none !important;
    }
  }
</style>
@endpush
