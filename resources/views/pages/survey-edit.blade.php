@extends('layouts.app')

@section('title', 'Edit Survey')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-9">
    <a href="{{ route('surveys.manage') }}" class="btn btn-outline-secondary btn-sm rounded-3 mb-3"><i class="bi bi-arrow-left me-1"></i>Manage surveys</a>
    <div class="card yg-card mt-3">
      <div class="card-body p-4">
        <h3 class="fw-bold mb-4">Edit survey</h3>
        <form method="POST" action="{{ route('surveys.update', $survey) }}">
          @csrf
          @method('PUT')
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Title</label>
              <input name="title" class="form-control" value="{{ old('title', $survey->title) }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Description</label>
              <textarea name="description" class="form-control" rows="4">{{ old('description', $survey->description) }}</textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Due date and time</label>
              <input type="datetime-local" name="due_at" class="form-control" value="{{ old('due_at', $survey->due_at?->format('Y-m-d\TH:i')) }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Points for answering</label>
              <input type="number" name="points" class="form-control" min="0" max="100000" value="{{ old('points', $survey->points) }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Audience</label>
              <select name="audience" id="surveyAudience" class="form-select" required>
                @foreach (['event_attendees' => 'Attendees of an event', 'public' => 'All residents', 'youth' => 'Youth', 'senior' => 'Senior citizens', 'family_heads' => 'Family heads', 'officials' => 'Officials'] as $value => $label)
                  <option value="{{ $value }}" @selected(old('audience', $survey->audience) === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6" id="surveyEventWrap">
              <label class="form-label fw-semibold">Event</label>
              <select name="event_id" id="surveyEvent" class="form-select">
                <option value="">Choose the event attended</option>
                @foreach ($events as $event)
                  <option value="{{ $event->id }}" @selected((string) old('event_id', $survey->event_id) === (string) $event->id)>{{ $event->title }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Question sections</label>
              @php
                $formSections = old('sections', $questionSections);
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
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="suggestion_enabled" value="1" id="suggestionEnabled" @checked(old('suggestion_enabled', $survey->suggestion_enabled))>
                <label class="form-check-label" for="suggestionEnabled">Include an optional suggestion box</label>
              </div>
            </div>
          </div>
          <button class="btn btn-yg mt-4"><i class="bi bi-check-circle me-1"></i>Save changes</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const audience = document.getElementById('surveyAudience');
  const eventWrap = document.getElementById('surveyEventWrap');
  const event = document.getElementById('surveyEvent');
  const sections = document.getElementById('surveySections');
  const addSection = document.getElementById('addSurveySection');
  const syncAudience = () => {
    const active = audience.value === 'event_attendees';
    eventWrap.hidden = !active;
    event.required = active;
  };
  audience.addEventListener('change', syncAudience);
  syncAudience();

  const renumber = () => {
    const sectionItems = sections.querySelectorAll('[data-section]');
    sectionItems.forEach((section, sectionIndex) => {
      let questionNumber = 1;
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
