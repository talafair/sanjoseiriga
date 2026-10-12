<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveySummaryExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_can_view_detailed_survey_summary_with_responses(): void
    {
        /** @var User $official */
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay',
            'official_position' => 'captain',
        ]);

        $survey = Survey::create([
            'title' => 'Community event feedback',
            'description' => 'Tell us how the event went.',
            'due_at' => now()->addDays(7),
            'points' => 10,
            'questions' => ['Question one', 'Question two'],
            'sections' => [
                ['heading' => 'Experience', 'start' => 0],
                ['heading' => 'Suggestions', 'start' => 1],
            ],
            'suggestion_enabled' => true,
            'audience' => 'public',
            'created_by' => $official->id,
        ]);

        SurveyResponse::create([
            'survey_id' => $survey->id,
            'user_id' => $official->id,
            'answers' => [5, 3],
            'suggestion' => 'Great event',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($official)->get(route('surveys.manage'));

        $response->assertOk();
        $response->assertSeeText('Overall average / 5');
        $response->assertSeeText('Question one');
        $response->assertSeeText('Experience');
        $response->assertSeeText('Suggestions');
        $response->assertSeeText('4.00');
        $response->assertSeeText('Survey responses');
        $response->assertDontSee('Download DOC');
    }

    public function test_official_can_publish_and_edit_survey_question_sections(): void
    {
        /** @var User $official */
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay',
            'official_position' => 'captain',
        ]);

        $this->actingAs($official)->post(route('surveys.store'), [
            'title' => 'Sectioned survey',
            'description' => 'A longer survey description.',
            'due_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'points' => 10,
            'audience' => 'public',
            'sections' => [
                ['heading' => 'Event planning', 'questions' => ['Question one', 'Question two']],
                ['heading' => 'Venue', 'questions' => ['Question three']],
            ],
            'suggestion_enabled' => '1',
        ])->assertSessionHasNoErrors();

        $survey = Survey::where('title', 'Sectioned survey')->firstOrFail();
        $this->assertSame(['Question one', 'Question two', 'Question three'], $survey->questions);
        $this->assertSame([
            ['heading' => 'Event planning', 'start' => 0],
            ['heading' => 'Venue', 'start' => 2],
        ], $survey->sections);

        $this->get(route('surveys.show', $survey))
            ->assertOk()
            ->assertSeeText('Event planning')
            ->assertSeeText('Venue');

        $this->get(route('surveys.edit', $survey))
            ->assertOk()
            ->assertSee('value="Question three"', false)
            ->assertSee('value="Venue"', false);

        $this->put(route('surveys.update', $survey), [
            'title' => 'Updated sectioned survey',
            'description' => 'Updated description.',
            'due_at' => now()->addDays(10)->format('Y-m-d H:i:s'),
            'points' => 15,
            'audience' => 'public',
            'sections' => [
                ['heading' => 'Planning', 'questions' => ['Updated question']],
            ],
            'suggestion_enabled' => '1',
        ])->assertRedirect(route('surveys.manage'));

        $survey->refresh();
        $this->assertSame(['Updated question'], $survey->questions);
        $this->assertSame([['heading' => 'Planning', 'start' => 0]], $survey->sections);
    }
}
