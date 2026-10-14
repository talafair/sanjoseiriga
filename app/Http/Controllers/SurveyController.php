<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\PushNotificationDelivery;
use App\Services\SuperadminNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SurveyController extends Controller
{
    public function index(Request $request): View
    {
        $surveys = Survey::with('event')->latest()->get();
        $answered = SurveyResponse::where('user_id', $request->user()->id)->pluck('survey_id');

        return view('pages.surveys', compact('surveys', 'answered'));
    }

    public function manage(): View
    {
        return view('pages.surveys-manage', [
            'surveys' => Survey::with(['event', 'responses'])->latest()->get()->each(function (Survey $survey) {
                $survey->notified_count = $this->audienceQuery($survey)->count();
            }),
            'events' => Announcement::where('is_event', true)->latest('event_start_at')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_at' => ['required', 'date', 'after:now'],
            'points' => ['required', 'integer', 'min:0', 'max:100000'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.heading' => ['nullable', 'string', 'max:255'],
            'sections.*.questions' => ['required', 'array', 'min:1'],
            'sections.*.questions.*' => ['required', 'string', 'max:500'],
            'audience' => ['required', 'in:public,youth,senior,family_heads,officials,event_attendees'],
            'event_id' => ['nullable', 'exists:announcements,id'],
            'suggestion_enabled' => ['nullable', 'boolean'],
        ]);

        $questionData = $this->flattenQuestionSections($data['sections']);
        if ($data['audience'] === 'event_attendees') {
            $request->validate(['event_id' => ['required', 'exists:announcements,id']]);
            abort_unless(Announcement::whereKey($data['event_id'])->where('is_event', true)->exists(), 422, 'Select an event.');
        } else {
            $data['event_id'] = null;
        }

        $survey = Survey::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_at' => $data['due_at'],
            'questions' => $questionData['questions'],
            'sections' => $questionData['sections'],
            'suggestion_enabled' => $request->boolean('suggestion_enabled'),
            'points' => $data['points'],
            'audience' => $data['audience'],
            'event_id' => $data['event_id'],
            'created_by' => $request->user()->id,
        ]);

        $this->notifyAudience($survey);

        return back()->with('success', 'Survey published.');
    }

    public function edit(Survey $survey): View
    {
        return view('pages.survey-edit', [
            'survey' => $survey,
            'questionSections' => $this->questionSectionsForEditing($survey),
            'events' => Announcement::where('is_event', true)->latest('event_start_at')->get(),
        ]);
    }

    public function update(Request $request, Survey $survey): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_at' => ['required', 'date', 'after:now'],
            'points' => ['required', 'integer', 'min:0', 'max:100000'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.heading' => ['nullable', 'string', 'max:255'],
            'sections.*.questions' => ['required', 'array', 'min:1'],
            'sections.*.questions.*' => ['required', 'string', 'max:500'],
            'audience' => ['required', 'in:public,youth,senior,family_heads,officials,event_attendees'],
            'event_id' => ['nullable', 'exists:announcements,id'],
            'suggestion_enabled' => ['nullable', 'boolean'],
        ]);

        $questionData = $this->flattenQuestionSections($data['sections']);
        if ($data['audience'] === 'event_attendees') {
            abort_unless(Announcement::whereKey($data['event_id'] ?? null)->where('is_event', true)->exists(), 422, 'Select an event.');
        } else {
            $data['event_id'] = null;
        }

        $survey->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_at' => $data['due_at'],
            'points' => $data['points'],
            'questions' => $questionData['questions'],
            'sections' => $questionData['sections'],
            'suggestion_enabled' => $request->boolean('suggestion_enabled'),
            'audience' => $data['audience'],
            'event_id' => $data['event_id'],
        ]);

        return redirect()->route('surveys.manage')->with('success', 'Survey updated.');
    }

    public function destroy(Survey $survey): RedirectResponse
    {
        $survey->delete();

        return back()->with('success', 'Survey deleted.');
    }

    public function show(Request $request, Survey $survey): View
    {
        abort_unless($request->user()->isOfficial() || $this->audienceQuery($survey)->whereKey($request->user()->id)->exists(), 403);

        return view('pages.survey-show', [
            'survey' => $survey,
            'response' => SurveyResponse::where('survey_id', $survey->id)->where('user_id', $request->user()->id)->first(),
        ]);
    }

    public function respond(Request $request, Survey $survey): RedirectResponse
    {
        abort_if($survey->due_at && $survey->due_at->isPast(), 410, 'This survey is closed.');
        abort_unless($this->audienceQuery($survey)->whereKey($request->user()->id)->exists(), 403);
        abort_if(SurveyResponse::where('survey_id', $survey->id)->where('user_id', $request->user()->id)->exists(), 409, 'You already answered this survey.');

        $data = $request->validate([
            'answers' => ['required', 'array', 'size:' . count($survey->questions)],
            'answers.*' => ['required', 'integer', 'between:1,5'],
            'suggestion' => [$survey->suggestion_enabled ? 'nullable' : 'prohibited', 'string', 'max:2000'],
        ]);

        $response = SurveyResponse::create([
            'survey_id' => $survey->id,
            'user_id' => $request->user()->id,
            'answers' => array_values($data['answers']),
            'suggestion' => $data['suggestion'] ?? null,
            'submitted_at' => now(),
        ]);
        SuperadminNotificationService::notifyDataChange($response, 'created');

        if ($survey->points > 0) {
            $request->user()->increment('points', $survey->points);
        }

        return back()->with('success', 'Your survey response was submitted.');
    }

    private function audienceQuery(Survey $survey)
    {
        return $survey->audienceQuery();
    }

    private function flattenQuestionSections(array $sections): array
    {
        $questions = [];
        $sectionHeadings = [];

        foreach ($sections as $section) {
            $sectionHeadings[] = [
                'heading' => trim($section['heading'] ?? ''),
                'start' => count($questions),
            ];
            foreach ($section['questions'] as $question) {
                $questions[] = $question;
            }
        }

        return ['questions' => $questions, 'sections' => $sectionHeadings];
    }

    private function questionSectionsForEditing(Survey $survey): array
    {
        $questions = $survey->questions ?? [];
        $sectionMarkers = array_values($survey->sections ?: [['heading' => '', 'start' => 0]]);

        return collect($sectionMarkers)->map(function (array $section, int $index) use ($sectionMarkers, $questions) {
            $start = (int) $section['start'];
            $nextStart = isset($sectionMarkers[$index + 1]) ? (int) $sectionMarkers[$index + 1]['start'] : count($questions);

            return [
                'heading' => $section['heading'] ?? '',
                'questions' => array_slice($questions, $start, $nextStart - $start),
            ];
        })->all();
    }

    private function notifyAudience(Survey $survey): void
    {
        $query = $this->audienceQuery($survey);
        $query->select('id')->chunkById(500, function ($users) use ($survey) {
            UserNotification::insert($users->map(fn ($user) => [
                'user_id' => $user->id,
                'survey_id' => $survey->id,
                'title' => 'New survey: ' . $survey->title,
                'body' => 'Share your feedback in the new community survey.',
                'created_by' => $survey->created_by,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all());
            PushNotificationDelivery::queueForRecipients(
                $users->modelKeys(),
                'New survey: ' . $survey->title,
                'Share your feedback in the new community survey.',
                route('surveys.show', $survey)
            );
        });
    }
}
