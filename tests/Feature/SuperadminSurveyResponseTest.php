<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperadminSurveyResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_record_an_eligible_residents_answers_and_award_them_points(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::SUPERADMIN_ROLE,
            'points' => 0,
        ]);
        $resident = User::factory()->create([
            'role' => 'resident',
            'is_verified' => true,
            'points' => 5,
        ]);
        $alreadyAnswered = User::factory()->create([
            'role' => 'resident',
            'is_verified' => true,
        ]);
        $guest = User::factory()->create([
            'role' => 'guest',
            'is_verified' => false,
        ]);
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'is_verified' => true,
            'points' => 0,
        ]);
        $unverifiedOfficial = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'is_verified' => false,
        ]);
        $personnel = User::factory()->create([
            'role' => 'official',
            'official_group' => 'personnel',
            'is_verified' => true,
        ]);
        $survey = $this->survey($superadmin);

        SurveyResponse::create([
            'survey_id' => $survey->id,
            'user_id' => $alreadyAnswered->id,
            'answers' => [4, 3],
            'submitted_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->get(route('surveys.manage'))
            ->assertOk()
            ->assertSee('Record response for resident, guest, or official')
            ->assertSee('<option value="' . $resident->id . '"', false)
            ->assertSee('<option value="' . $guest->id . '"', false)
            ->assertSee('<option value="' . $official->id . '"', false)
            ->assertSee('<option value="' . $personnel->id . '"', false)
            ->assertDontSee('<option value="' . $unverifiedOfficial->id . '"', false)
            ->assertDontSee('<option value="' . $alreadyAnswered->id . '"', false)
            ->assertSee('name="answers[0]" id="adminAnswer' . $survey->id . '05" value="5" required checked', false)
            ->assertSee('name="answers[1]" id="adminAnswer' . $survey->id . '15" value="5" required checked', false);

        $this->post(route('surveys.responses.store', $survey), [
            'respondent_user_id' => $resident->id,
            'answers' => [5, 2],
            'suggestion' => 'Please add more activities.',
        ])->assertRedirect()
            ->assertSessionHas('success', "Survey response recorded for {$resident->full_name}.");

        $response = SurveyResponse::where('survey_id', $survey->id)
            ->where('user_id', $resident->id)
            ->sole();
        $this->assertSame([5, 2], $response->answers);
        $this->assertSame('Please add more activities.', $response->suggestion);
        $this->assertSame(25, $resident->fresh()->points);
        $this->assertSame(0, $superadmin->fresh()->points);
        $this->assertArrayNotHasKey('created_by', $response->getAttributes());

        $this->post(route('surveys.responses.store', $survey), [
            'respondent_user_id' => $guest->id,
            'answers' => [4, 4],
        ])->assertRedirect()
            ->assertSessionHas('success', "Survey response recorded for {$guest->full_name}.");
        $this->assertSame(20, $guest->fresh()->points);

        $this->post(route('surveys.responses.store', $survey), [
            'respondent_user_id' => $official->id,
            'answers' => [3, 4],
        ])->assertRedirect()
            ->assertSessionHas('success', "Survey response recorded for {$official->full_name}.");
        $this->assertSame(20, $official->fresh()->points);

        $this->post(route('surveys.responses.store', $survey), [
            'respondent_user_id' => $personnel->id,
            'answers' => [4, 5],
        ])->assertRedirect()
            ->assertSessionHas('success', "Survey response recorded for {$personnel->full_name}.");
        $this->assertSame(20, $personnel->fresh()->points);

        $this->post(route('surveys.responses.store', $survey), [
            'respondent_user_id' => $resident->id,
            'answers' => [5, 2],
        ])->assertConflict();

        $this->assertSame(25, $resident->fresh()->points);
        $this->assertSame(20, $guest->fresh()->points);
        $this->assertSame(20, $official->fresh()->points);
        $this->assertSame(20, $personnel->fresh()->points);
        $this->assertDatabaseCount('survey_responses', 5);
    }

    public function test_verified_official_cannot_record_responses_on_behalf_of_residents(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'is_verified' => true,
        ]);
        $resident = User::factory()->create([
            'role' => 'resident',
            'is_verified' => true,
        ]);
        $survey = $this->survey($official);

        $this->actingAs($official)
            ->post(route('surveys.responses.store', $survey), [
                'respondent_user_id' => $resident->id,
                'answers' => [5, 2],
            ])->assertForbidden();

        $this->assertDatabaseCount('survey_responses', 0);
        $this->assertSame(0, $resident->fresh()->points);
    }

    public function test_superadmin_cannot_submit_for_residents_outside_the_survey_audience(): void
    {
        $superadmin = User::factory()->create(['role' => User::SUPERADMIN_ROLE]);
        $resident = User::factory()->create([
            'role' => 'resident',
            'is_verified' => true,
        ]);
        $survey = $this->survey($superadmin, 'officials');

        $this->actingAs($superadmin)
            ->post(route('surveys.responses.store', $survey), [
                'respondent_user_id' => $resident->id,
                'answers' => [5, 2],
            ])->assertForbidden();

        $this->assertDatabaseCount('survey_responses', 0);
    }

    private function survey(User $creator, string $audience = 'public'): Survey
    {
        return Survey::create([
            'title' => 'Resident feedback',
            'due_at' => now()->addDay(),
            'points' => 20,
            'questions' => ['Question one', 'Question two'],
            'sections' => [['heading' => 'Feedback', 'start' => 0]],
            'suggestion_enabled' => true,
            'audience' => $audience,
            'created_by' => $creator->id,
        ]);
    }
}
