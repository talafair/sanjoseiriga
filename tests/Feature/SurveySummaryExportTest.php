<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Models\UserNotification;
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
            'is_verified' => true,
        ]);

        $survey = Survey::create([
            'title' => 'Community event feedback',
            'description' => 'Tell us how the event went.',
            'due_at' => now()->addDays(7),
            'points' => 10,
            'questions' => ['Question one', 'Question two', 'Question three'],
            'sections' => [
                ['heading' => 'Experience', 'start' => 0],
                ['heading' => 'Suggestions', 'start' => 2],
            ],
            'suggestion_enabled' => true,
            'audience' => 'public',
            'created_by' => $official->id,
        ]);

        SurveyResponse::create([
            'survey_id' => $survey->id,
            'user_id' => $official->id,
            'answers' => [5, 5, 1],
            'suggestion' => 'Great event',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($official)->get(route('surveys.manage'));

        $response->assertOk();
        $response->assertSeeText('Overall weighted mean / 5');
        $response->assertSeeText('Question one');
        $response->assertSeeText('Experience');
        $response->assertSeeText('Suggestions');
        $response->assertSeeText('5.00');
        $response->assertSeeText('1.00');
        $response->assertSeeText('3.67');
        $response->assertSeeText('Survey responses');
        $response->assertDontSee('Download DOC');
    }

    public function test_official_summary_separates_resident_and_official_results(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay',
            'official_position' => 'captain',
            'is_verified' => true,
        ]);
        $admin = User::factory()->create(['role' => 'superadmin']);
        $personnel = User::factory()->create([
            'role' => 'official',
            'official_group' => 'personnel',
            'is_verified' => true,
        ]);
        $resident = User::factory()->create(['role' => 'resident']);
        $guest = User::factory()->create(['role' => 'guest']);
        $survey = Survey::create([
            'title' => 'Split result survey',
            'due_at' => now()->addDays(7),
            'points' => 0,
            'questions' => ['Question one', 'Question two'],
            'audience' => 'public',
            'created_by' => $official->id,
        ]);

        foreach ([
            [$resident, [5, 4]],
            [$official, [5, 5]],
            [$admin, [4, 5]],
            [$personnel, [3, 5]],
            [$guest, [1, 1]],
        ] as [$respondent, $answers]) {
            SurveyResponse::create([
                'survey_id' => $survey->id,
                'user_id' => $respondent->id,
                'answers' => $answers,
                'submitted_at' => now(),
            ]);
        }

        $this->actingAs($official)->get(route('surveys.manage'))
            ->assertOk()
            ->assertSeeText('Summary by respondent group')
            ->assertSeeText('Residents and Guests')
            ->assertSeeText('Officials and Personnel')
            ->assertDontSeeText('Officials & admins')
            ->assertSeeText('2.75')
            ->assertSeeText('4.50');
    }

    public function test_survey_summary_includes_respondent_age_and_gender_demographics(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay',
            'official_position' => 'captain',
            'is_verified' => true,
        ]);
        $guest = User::factory()->create([
            'role' => 'guest',
            'birthdate' => now()->subYears(29)->toDateString(),
            'gender' => 'female',
            'zone' => '4',
            'barangay' => 'San Isidro',
            'city' => 'Iriga City',
        ]);
        $survey = Survey::create([
            'title' => 'Guest demographics survey',
            'due_at' => now()->addDays(7),
            'points' => 0,
            'questions' => ['Question one'],
            'audience' => 'public',
            'created_by' => $official->id,
        ]);
        SurveyResponse::create([
            'survey_id' => $survey->id,
            'user_id' => $guest->id,
            'answers' => [5],
            'submitted_at' => now(),
        ]);

        $this->actingAs($official)->get(route('surveys.manage'))
            ->assertOk()
            ->assertSeeText('Respondent demographics')
            ->assertSeeText('25-34')
            ->assertSeeText('Female')
            ->assertSeeText('Zone 4')
            ->assertSeeText('Barangay: San Isidro')
            ->assertSeeText('City/Municipality: Iriga City');
    }

    public function test_survey_print_summary_includes_research_profile_with_optional_names(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay',
            'official_position' => 'captain',
            'is_verified' => true,
        ]);
        $respondent = User::factory()->create(['role' => 'guest']);
        $survey = Survey::create([
            'title' => 'Research profile survey',
            'due_at' => now()->addDays(7),
            'points' => 0,
            'questions' => ['Question one'],
            'audience' => 'public',
            'research_consent_enabled' => true,
            'created_by' => $official->id,
        ]);
        SurveyResponse::create([
            'survey_id' => $survey->id,
            'user_id' => $respondent->id,
            'answers' => [5],
            'research_profile' => [
                'name' => 'Optional Research Name',
                'age' => 29,
                'sex' => 'female',
                'used_talafair' => 'yes',
                'user_type' => 'guest',
                'signature' => null,
            ],
            'research_consent_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($official)->get(route('surveys.manage'))
            ->assertOk()
            ->assertSee('Include names in print summary')
            ->assertSee('data-include-respondent-names="'.$survey->id.'"', false)
            ->assertSee('respondent-name-print', false)
            ->assertSee('respondent-anonymous-print', false)
            ->assertSee('research-profile-name-print', false)
            ->assertSeeText('Research profile:')
            ->assertSeeText('Optional Research Name');
    }

    public function test_official_can_publish_and_edit_survey_question_sections(): void
    {
        /** @var User $official */
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay',
            'official_position' => 'captain',
            'is_verified' => true,
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
            'research_consent_enabled' => '1',
        ])->assertSessionHasNoErrors();

        $survey = Survey::where('title', 'Sectioned survey')->firstOrFail();
        $this->assertTrue($survey->research_consent_enabled);
        $this->assertSame(['Question one', 'Question two', 'Question three'], $survey->questions);
        $this->assertSame([
            ['heading' => 'Event planning', 'start' => 0],
            ['heading' => 'Venue', 'start' => 2],
        ], $survey->sections);

        $this->get(route('surveys.show', $survey))
            ->assertOk()
            ->assertSeeText('Event planning')
            ->assertSeeText('Venue')
            ->assertSeeText('Strongly Agree, Agree, Neutral, Disagree, or Strongly Disagree')
            ->assertSee('<legend class="h6 fw-semibold">1. Question one</legend>', false)
            ->assertSee('<legend class="h6 fw-semibold">2. Question two</legend>', false)
            ->assertSee('<legend class="h6 fw-semibold">1. Question three</legend>', false)
            ->assertSee('value="5"', false)
            ->assertSee('>Strongly Agree</label>', false)
            ->assertSee('value="1"', false)
            ->assertSee('>Strongly Disagree</label>', false);

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
            'research_consent_enabled' => '1',
        ])->assertRedirect(route('surveys.manage'));

        $survey->refresh();
        $this->assertTrue($survey->research_consent_enabled);
        $this->assertSame(['Updated question'], $survey->questions);
        $this->assertSame([['heading' => 'Planning', 'start' => 0]], $survey->sections);
    }

    public function test_official_can_publish_a_survey_with_more_than_twenty_questions(): void
    {
        /** @var User $official */
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay',
            'official_position' => 'captain',
            'is_verified' => true,
        ]);
        $questions = array_map(fn ($number) => 'Question '.$number, range(1, 21));

        $this->actingAs($official)->post(route('surveys.store'), [
            'title' => 'Long survey',
            'due_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'points' => 0,
            'audience' => 'public',
            'sections' => [
                ['heading' => 'All questions', 'questions' => $questions],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertCount(21, Survey::where('title', 'Long survey')->firstOrFail()->questions);
    }

    public function test_official_can_remind_unanswered_survey_audience_anytime_while_survey_is_open(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'barangay_captain',
            'is_verified' => true,
        ]);
        $pendingUser = User::factory()->create(['role' => 'resident']);
        $answeredUser = User::factory()->create(['role' => 'resident']);
        $survey = Survey::create([
            'title' => 'Community feedback',
            'due_at' => now()->addDays(2),
            'points' => 0,
            'questions' => ['Question one'],
            'audience' => 'public',
            'created_by' => $official->id,
        ]);
        SurveyResponse::create([
            'survey_id' => $survey->id,
            'user_id' => $answeredUser->id,
            'answers' => [5],
            'submitted_at' => now(),
        ]);
        SurveyResponse::create([
            'survey_id' => $survey->id,
            'user_id' => $official->id,
            'answers' => [5],
            'submitted_at' => now(),
        ]);

        $this->actingAs($official)->get(route('surveys.show', $survey))
            ->assertOk()
            ->assertDontSee('Remind the audience');

        $this->actingAs($official)->get(route('surveys.manage'))
            ->assertOk()
            ->assertSee('Remind the audience')
            ->assertSee('Remind audience to complete this survey')
            ->assertSee('Optional note');

        $this->actingAs($official)->post(route('surveys.remind', $survey), [
            'kind' => 'survey_closing',
            'message' => 'Please answer before it closes.',
        ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Survey reminder sent to 1 audience member(s) who have not responded.');

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $pendingUser->id,
            'survey_id' => $survey->id,
            'title' => 'Reminder: complete survey — Community feedback',
            'body' => 'Please answer before it closes.',
            'created_by' => $official->id,
        ]);
        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $answeredUser->id,
            'survey_id' => $survey->id,
        ]);
    }

    public function test_survey_reminders_are_rejected_after_the_survey_closes(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'barangay_captain',
            'is_verified' => true,
        ]);
        $survey = Survey::create([
            'title' => 'Community feedback',
            'due_at' => now()->subSecond(),
            'points' => 0,
            'questions' => ['Question one'],
            'audience' => 'public',
            'created_by' => $official->id,
        ]);

        $this->actingAs($official)
            ->post(route('surveys.remind', $survey), ['kind' => 'survey_closing'])
            ->assertRedirect()
            ->assertSessionHas('error', 'Reminders can only be sent while the survey is open.');

        $this->assertDatabaseMissing('user_notifications', [
            'survey_id' => $survey->id,
            'title' => 'Reminder: complete survey — Community feedback',
        ]);
    }

    public function test_survey_rejects_responses_at_or_after_its_closing_time(): void
    {
        $resident = User::factory()->create(['role' => 'resident', 'is_verified' => true]);
        $dueAt = now()->addHour();
        $survey = Survey::create([
            'title' => 'Closing survey',
            'due_at' => $dueAt,
            'points' => 10,
            'questions' => ['Question one'],
            'audience' => 'public',
            'created_by' => $resident->id,
        ]);

        $this->actingAs($resident)->get(route('surveys.show', $survey))
            ->assertOk()
            ->assertSee('name="answers[0]"', false);

        $this->travelTo($dueAt);

        $this->actingAs($resident)->get(route('surveys.show', $survey))
            ->assertOk()
            ->assertSee('Responses are no longer accepted.')
            ->assertDontSee('name="answers[0]"', false)
            ->assertDontSee('Submit response');

        $this->post(route('surveys.respond', $survey), [
            'answers' => [5],
        ])->assertGone();

        $this->travelBack();
        $this->travelTo($dueAt->copy()->addSecond());

        $this->post(route('surveys.respond', $survey), [
            'answers' => [5],
        ])->assertGone();

        $this->assertDatabaseMissing('survey_responses', [
            'survey_id' => $survey->id,
            'user_id' => $resident->id,
        ]);
    }
}
