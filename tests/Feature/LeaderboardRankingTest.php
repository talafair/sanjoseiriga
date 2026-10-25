<?php

namespace Tests\Feature;

use App\Models\GameRun;
use App\Models\User;
use Tests\TestCase;

class LeaderboardRankingTest extends TestCase
{
    public function test_family_leaderboard_aggregates_head_and_member_points(): void
    {
        $head = User::factory()->create([
            'first_name' => 'Ranking',
            'last_name' => 'Household',
            'role' => 'resident',
            'is_head_of_family' => true,
            'points' => 30,
        ]);
        $member = User::factory()->create([
            'first_name' => 'Ranking',
            'last_name' => 'Member',
            'role' => 'resident',
            'is_head_of_family' => false,
            'head_of_family_id' => $head->id,
            'points' => 70,
        ]);

        $this->get(route('leaderboard', ['ranking' => 'family']))
            ->assertOk()
            ->assertSee('Family Rankings')
            ->assertSee($head->full_name)
            ->assertSee($member->full_name)
            ->assertSee('100');
    }

    public function test_arcade_rankings_are_sorted_and_respect_the_selected_period(): void
    {
        $viewer = User::factory()->create([
            'role' => 'resident',
            'is_verified' => true,
        ]);
        $currentLeader = User::factory()->create(['name' => 'Current Leader']);
        $currentRunnerUp = User::factory()->create(['name' => 'Current Runner']);
        $oldLeader = User::factory()->create(['name' => 'Old Leader']);

        $this->gameRun($currentLeader, today(), 90);
        $this->gameRun($currentRunnerUp, today(), 50);
        $this->gameRun($oldLeader, today()->subDays(8), 500);

        $rankings = $this->actingAs($viewer)
            ->getJson(route('games.arcade.rankings', [
                'game' => 'catch-star',
                'period' => 'weekly',
            ]))
            ->assertOk()
            ->json('rankings');

        $this->assertSame(['Current Leader', 'Current Runner'], array_column($rankings, 'name'));
        $this->assertSame([1, 2], array_column($rankings, 'rank'));

        $allTime = $this->getJson(route('games.arcade.rankings', [
            'game' => 'catch-star',
            'period' => 'all',
        ]))->assertOk()->json('rankings');

        $this->assertSame('Old Leader', $allTime[0]['name']);
    }

    public function test_arcade_rankings_reject_unsupported_games(): void
    {
        $viewer = User::factory()->create([
            'role' => 'resident',
            'is_verified' => true,
        ]);

        $this->actingAs($viewer)
            ->getJson(route('games.arcade.rankings', ['game' => 'unknown']))
            ->assertNotFound();
    }

    private function gameRun(User $user, $playedOn, int $score): void
    {
        GameRun::create([
            'user_id' => $user->id,
            'game' => 'catch-star',
            'played_on' => $playedOn,
            'stage_scores' => [],
            'total_score' => $score,
        ]);
    }
}
