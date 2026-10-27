<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestSurveyAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_and_submit_an_available_survey(): void
    {
        $guest = User::factory()->create([
            'role' => 'guest',
            'is_verified' => false,
            'points' => 0,
        ]);
        $survey = Survey::create([
            'title' => 'Public guest survey',
            'due_at' => now()->addDay(),
            'points' => 10,
            'questions' => ['How was the event?', 'Would you recommend it?'],
            'audience' => 'public',
            'created_by' => $guest->id,
        ]);
        $restrictedSurvey = Survey::create([
            'title' => 'Officials-only survey',
            'due_at' => now()->addDay(),
            'points' => 10,
            'questions' => ['How was the event?'],
            'audience' => 'officials',
            'created_by' => $guest->id,
        ]);

        $this->actingAs($guest)
            ->get(route('surveys.index'))
            ->assertOk()
            ->assertSeeText('Public guest survey')
            ->assertDontSeeText('Officials-only survey');

        $this->get(route('surveys.show', $survey))
            ->assertOk()
            ->assertSee('name="answers[0]" id="answer05" value="5" required', false)
            ->assertSee('name="answers[1]" id="answer15" value="5" required', false)
            ->assertDontSee('name="answers[0]" id="answer05" value="5" required checked', false)
            ->assertDontSee('name="answers[1]" id="answer15" value="5" required checked', false);
        $this->get(route('surveys.show', $restrictedSurvey))->assertForbidden();

        $this->post(route('surveys.respond', $survey), [
            'answers' => [5, 5],
        ])->assertRedirect()
            ->assertSessionHas('success', 'Your survey response was submitted.');

        $this->assertDatabaseHas('survey_responses', [
            'survey_id' => $survey->id,
            'user_id' => $guest->id,
        ]);
        $this->assertSame(10, $guest->fresh()->points);

        $this->get(route('surveys.show', $survey))
            ->assertOk()
            ->assertSeeText('You submitted this survey')
            ->assertSeeText('Your answers')
            ->assertSeeText('How was the event?')
            ->assertSeeText('Strongly Agree')
            ->assertDontSeeText('Summary by respondent group');
    }

    public function test_research_survey_requires_consent_without_collecting_demographic_profile(): void
    {
        $guest = User::factory()->create([
            'role' => 'guest',
            'is_verified' => false,
            'points' => 0,
        ]);
        $survey = Survey::create([
            'title' => 'TalaFair evaluation',
            'due_at' => now()->addDay(),
            'points' => 0,
            'questions' => ['How was your experience?'],
            'audience' => 'public',
            'research_consent_enabled' => true,
            'created_by' => $guest->id,
        ]);
        $this->actingAs($guest)
            ->get(route('surveys.show', $survey))
            ->assertOk()
            ->assertSeeText('The Researchers:')
            ->assertSeeText('Camarines Sur Polytechnic Colleges')
            ->assertSeeText('Your responses are linked to your TalaFair')
            ->assertSee('name="research_consent"', false)
            ->assertDontSeeText('Respondent demographic profile')
            ->assertDontSee('name="research_profile[age]"', false)
            ->assertDontSee('name="research_profile[user_type]"', false);

        $this->post(route('surveys.respond', $survey), [
            'answers' => [4],
        ])->assertSessionHasErrors('research_consent');

        $this->post(route('surveys.respond', $survey), [
            'answers' => [4],
            'research_consent' => '1',
        ])->assertRedirect()
            ->assertSessionHas('success', 'Your survey response was submitted.');

        $response = $survey->responses()->sole();
        $this->assertNull($response->research_profile);
        $this->assertNotNull($response->research_consent_at);
    }
}
