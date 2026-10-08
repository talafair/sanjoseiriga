<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HomePageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_home_shows_user_data_and_only_relevant_active_announcements(): void
    {
        $now = Carbon::parse('2026-10-08 12:00:00');
        $this->travelTo($now);

        $resident = User::factory()->create([
            'name' => 'Resident User',
            'first_name' => 'Resident',
            'role' => 'resident',
            'is_verified' => true,
            'points' => 1234,
        ]);
        $otherUser = User::factory()->create();

        $resident->appNotifications()->create(['title' => 'Unread for resident']);
        $otherUser->appNotifications()->create(['title' => 'Unread for another user']);

        $this->event('Public event', ['public'], $now->copy()->addHour(), $now->copy()->addHours(3));
        $this->event('Officials-only event', ['officials'], $now->copy()->addHour(), $now->copy()->addHours(3));
        $this->event('Expired featured event', ['public'], $now->copy()->subHours(6), null, true);

        $this->actingAs($resident)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Kumusta, Resident')
            ->assertSee('1,234 points earned')
            ->assertSee('Public event')
            ->assertSee('1 open')
            ->assertSee('<span class="notification-badge">', false)
            ->assertSee("\n                            1\n", false)
            ->assertDontSee('Level ')
            ->assertDontSee('Officials-only event')
            ->assertDontSee('Expired featured event')
            ->assertDontSee('Verified resident');
    }

    public function test_official_and_superadmin_home_can_see_official_events(): void
    {
        $now = Carbon::parse('2026-10-08 12:00:00');
        $this->travelTo($now);
        $this->event('Officials-only event', ['officials'], $now->copy()->addHour(), $now->copy()->addHours(3));

        foreach (['official', 'superadmin'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'is_verified' => true,
            ]);

            $this->actingAs($user)
                ->get(route('home'))
                ->assertOk()
                ->assertSee('Officials-only event')
                ->assertSee('Verified account')
                ->assertDontSee('Verified resident');
        }
    }

    public function test_leaderboard_does_not_render_an_unbacked_user_level(): void
    {
        $user = User::factory()->create(['username' => 'points-player', 'points' => 300]);

        $this->actingAs($user)
            ->get(route('leaderboard'))
            ->assertOk()
            ->assertSee('points-player')
            ->assertSee('300')
            ->assertDontSee('Level');
    }

    public function test_home_renders_when_no_gamification_or_announcement_records_exist(): void
    {
        $user = User::factory()->create(['role' => 'resident']);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('No wheel winners yet.')
            ->assertSee('No badges are available yet.')
            ->assertSee('No announcements yet.')
            ->assertDontSee('0 open');
    }

    private function event(string $title, array $audiences, Carbon $startsAt, ?Carbon $endsAt, bool $featured = false): Announcement
    {
        return Announcement::create([
            'title' => $title,
            'category' => 'events',
            'body' => "{$title} details",
            'is_featured' => $featured,
            'is_event' => true,
            'event_start_at' => $startsAt,
            'event_end_at' => $endsAt,
            'audiences' => $audiences,
        ]);
    }
}
