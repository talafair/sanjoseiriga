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
            'questions' => ['How was the event?'],
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
            ->assertSee('name="answers[0]"', false);
        $this->get(route('surveys.show', $restrictedSurvey))->assertForbidden();

        $this->post(route('surveys.respond', $survey), [
            'answers' => [5],
        ])->assertRedirect()
            ->assertSessionHas('success', 'Your survey response was submitted.');

        $this->assertDatabaseHas('survey_responses', [
            'survey_id' => $survey->id,
            'user_id' => $guest->id,
        ]);
        $this->assertSame(10, $guest->fresh()->points);

        $this->get(route('surveys.show', $survey))
            ->assertOk()
            ->assertSeeText('You submitted this survey');
    }
}
