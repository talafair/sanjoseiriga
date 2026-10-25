<?php

namespace Tests\Feature;

use App\Models\TriviaAnswer;
use App\Models\TriviaTheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TriviaRankingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_trivia_rankings_section_is_visible_when_there_are_no_themes(): void
    {
        $viewer = User::factory()->create(['role' => 'resident', 'is_verified' => true]);

        $this->actingAs($viewer)
            ->get(route('games.index'))
            ->assertOk()
            ->assertSee('Trivia rankings')
            ->assertSee('No trivia themes have been published yet.');
    }

    public function test_aggregate_rankings_use_saved_trivia_points_and_share_tied_ranks(): void
    {
        $theme = $this->theme();
        $first = User::factory()->create(['name' => 'First Player', 'points' => 9000, 'is_verified' => true]);
        $tied = User::factory()->create(['name' => 'Tied Player', 'points' => 0]);
        $third = User::factory()->create(['name' => 'Third Player', 'points' => 5000]);
        $noAnswers = User::factory()->create(['name' => 'No Answers']);

        $this->answer($theme, $first, 0, 40);
        $this->answer($theme, $first, 1, 10);
        $this->answer($theme, $tied, 0, 50);
        $this->answer($theme, $third, 0, 20);

        $rankings = $this->actingAs($first)
            ->getJson(route('games.rankings', ['trivia_period' => 'all']))
            ->assertOk()
            ->json('trivia.aggregate');

        $this->assertSame([
            ['name' => 'First Player', 'points' => 50, 'rank' => 1],
            ['name' => 'Tied Player', 'points' => 50, 'rank' => 1],
            ['name' => 'Third Player', 'points' => 20, 'rank' => 3],
        ], array_map(
            fn (array $item) => ['name' => $item['name'], 'points' => $item['points'], 'rank' => $item['rank']],
            $rankings
        ));
        $this->assertNotContains($noAnswers->name, array_column($rankings, 'name'));
    }

    public function test_theme_rankings_use_saved_trivia_results_and_exclude_users_without_answers(): void
    {
        $theme = $this->theme();
        $player = User::factory()->create(['name' => 'Trivia Player', 'is_verified' => true]);
        $noAnswers = User::factory()->create(['name' => 'Not Played']);
        $this->answer($theme, $player, 0, 35);

        $rankings = $this->actingAs($player)
            ->getJson(route('games.rankings', ['trivia_period' => 'today']))
            ->assertOk()
            ->json("trivia.{$theme->id}");

        $this->assertCount(1, $rankings);
        $this->assertSame('Trivia Player', $rankings[0]['name']);
        $this->assertSame(35, $rankings[0]['points']);
        $this->assertSame(1, $rankings[0]['rank']);
        $this->assertNotSame($noAnswers->name, $rankings[0]['name']);
    }

    public function test_player_cannot_submit_a_second_answer_for_the_same_theme_question(): void
    {
        $theme = $this->theme();
        $player = User::factory()->create(['role' => 'resident', 'is_verified' => true]);
        $payload = ['question_index' => 0, 'answer' => 'Correct'];

        $this->actingAs($player)
            ->postJson(route('games.trivia.answer', $theme), $payload)
            ->assertOk()
            ->assertJsonPath('correct', true);

        $this->actingAs($player)
            ->postJson(route('games.trivia.answer', $theme), $payload)
            ->assertStatus(429);
    }

    private function theme(): TriviaTheme
    {
        return TriviaTheme::create([
            'title' => 'Test trivia',
            'base_points' => 10,
            'due_at' => now()->addDay(),
            'questions' => [
                ['question' => 'Question one?', 'answer' => 'Correct', 'choices' => ['Correct', 'Wrong one', 'Wrong two']],
                ['question' => 'Question two?', 'answer' => 'Correct', 'choices' => ['Correct', 'Wrong one', 'Wrong two']],
            ],
        ]);
    }

    private function answer(TriviaTheme $theme, User $user, int $questionIndex, int $points): void
    {
        TriviaAnswer::create([
            'trivia_theme_id' => $theme->id,
            'user_id' => $user->id,
            'question_index' => $questionIndex,
            'is_correct' => true,
            'rank' => 1,
            'points_awarded' => $points,
            'answered_at' => now(),
        ]);
    }
}
