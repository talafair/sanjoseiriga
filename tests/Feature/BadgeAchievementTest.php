<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Badge;
use App\Models\GameRun;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\BadgeService;
use Tests\TestCase;

class BadgeAchievementTest extends TestCase
{
    public function test_automatic_points_badge_is_awarded_once_and_notifies_the_user(): void
    {
        $user = User::factory()->create(['points' => 100]);
        $badge = $this->badge([
            'name' => 'Point milestone',
            'category' => 'milestones',
            'condition_key' => 'points',
            'points_required' => 100,
        ]);

        BadgeService::awardEligible($user);
        BadgeService::awardEligible($user);

        $this->assertDatabaseHas('badge_user', [
            'badge_id' => $badge->id,
            'user_id' => $user->id,
            'awarded_by' => null,
        ]);
        $this->assertSame(1, $user->badges()->whereKey($badge->id)->count());
        $this->assertSame(1, UserNotification::where('user_id', $user->id)->where('title', 'Badge unlocked')->count());
    }

    public function test_limited_badge_is_only_awarded_up_to_its_total(): void
    {
        $first = User::factory()->create(['points' => 100]);
        $second = User::factory()->create(['points' => 100]);
        $badge = $this->badge([
            'name' => 'Limited milestone',
            'category' => 'milestones',
            'condition_key' => 'points',
            'points_required' => 50,
            'limited_total' => 1,
        ]);

        BadgeService::awardEligible($first);
        BadgeService::awardEligible($second);

        $this->assertDatabaseHas('badge_user', [
            'badge_id' => $badge->id,
            'user_id' => $first->id,
        ]);
        $this->assertDatabaseMissing('badge_user', [
            'badge_id' => $badge->id,
            'user_id' => $second->id,
        ]);
    }

    public function test_early_bird_badge_records_the_attendance_rank(): void
    {
        $user = User::factory()->create();
        $badge = $this->badge([
            'name' => 'Early bird',
            'category' => 'attendance',
            'condition_key' => 'early_bird',
            'limited_total' => 3,
        ]);

        BadgeService::awardEligible($user, attendancePosition: 2);

        $this->assertDatabaseHas('badge_user', [
            'badge_id' => $badge->id,
            'user_id' => $user->id,
            'award_rank' => 2,
        ]);
    }

    public function test_game_badge_requires_the_user_to_be_the_leading_player(): void
    {
        $leader = User::factory()->create();
        $other = User::factory()->create();
        GameRun::create([
            'user_id' => $leader->id,
            'game' => 'puzzle',
            'played_on' => today(),
            'stage_scores' => [],
            'total_score' => 100,
        ]);
        GameRun::create([
            'user_id' => $other->id,
            'game' => 'puzzle',
            'played_on' => today(),
            'stage_scores' => [],
            'total_score' => 50,
        ]);
        $badge = $this->badge([
            'name' => 'Weekly game leader',
            'category' => 'games',
            'condition_key' => 'best_player_week',
        ]);

        BadgeService::awardGameBadges($leader);
        BadgeService::awardGameBadges($other);

        $this->assertDatabaseHas('badge_user', [
            'badge_id' => $badge->id,
            'user_id' => $leader->id,
        ]);
        $this->assertDatabaseMissing('badge_user', [
            'badge_id' => $badge->id,
            'user_id' => $other->id,
        ]);
    }

    public function test_official_can_manually_award_a_badge_without_duplicate_pivots_or_notifications(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => true,
        ]);
        $recipient = User::factory()->create(['role' => 'resident']);

        $this->actingAs($official)
            ->post(route('badges.store'), [
                'name' => 'Community helper',
                'description' => 'Recognized for helping.',
                'points_required' => 0,
                'category' => 'special',
                'rarity' => 'rare',
                'award_method' => 'official',
                'condition_key' => 'manual',
                'recipients' => [$recipient->id, $recipient->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Badge added.');

        $badge = Badge::where('name', 'Community helper')->firstOrFail();
        $this->assertSame(1, $badge->users()->whereKey($recipient->id)->count());
        $this->assertDatabaseHas('badge_user', [
            'badge_id' => $badge->id,
            'user_id' => $recipient->id,
            'awarded_by' => $official->id,
        ]);
        $this->assertSame(1, UserNotification::where('user_id', $recipient->id)->where('title', 'Badge awarded')->count());
    }

    public function test_new_automatic_badge_is_awarded_to_residents_who_already_qualify(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => true,
        ]);
        $attendee = User::factory()->create(['role' => 'resident']);
        $guestAttendee = User::factory()->create(['role' => 'guest']);
        $nonAttendee = User::factory()->create(['role' => 'resident']);
        $otherEventAttendee = User::factory()->create(['role' => 'resident']);
        $event = Announcement::create([
            'title' => 'Past community event',
            'category' => 'events',
            'body' => 'Attendance test.',
            'is_event' => true,
            'event_start_at' => now()->subDay(),
        ]);
        $otherEvent = Announcement::create([
            'title' => 'Different past event',
            'category' => 'events',
            'body' => 'Unrelated attendance test.',
            'is_event' => true,
            'event_start_at' => now()->subDays(2),
        ]);
        Attendance::create([
            'announcement_id' => $event->id,
            'user_id' => $attendee->id,
            'scanned_at' => now()->subDay(),
        ]);
        Attendance::create([
            'announcement_id' => $event->id,
            'user_id' => $guestAttendee->id,
            'scanned_at' => now()->subDay(),
        ]);
        Attendance::create([
            'announcement_id' => $otherEvent->id,
            'user_id' => $otherEventAttendee->id,
            'scanned_at' => now()->subDays(2),
        ]);

        $this->actingAs($official)
            ->post(route('badges.store'), [
                'name' => 'Community attendee',
                'points_required' => 0,
                'category' => 'attendance',
                'rarity' => 'common',
                'award_method' => 'automatic',
                'condition_key' => 'event_attendance',
                'announcement_id' => $event->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Badge added.');

        $badge = Badge::where('name', 'Community attendee')->firstOrFail();
        $this->assertDatabaseHas('badge_user', [
            'badge_id' => $badge->id,
            'user_id' => $attendee->id,
            'awarded_by' => null,
        ]);
        $this->assertDatabaseHas('badge_user', [
            'badge_id' => $badge->id,
            'user_id' => $guestAttendee->id,
            'awarded_by' => null,
        ]);
        $this->assertDatabaseMissing('badge_user', [
            'badge_id' => $badge->id,
            'user_id' => $nonAttendee->id,
        ]);
        $this->assertDatabaseMissing('badge_user', [
            'badge_id' => $badge->id,
            'user_id' => $otherEventAttendee->id,
        ]);
        $this->assertSame(1, UserNotification::where('user_id', $attendee->id)->where('title', 'Badge unlocked')->count());
    }

    private function badge(array $overrides = []): Badge
    {
        return Badge::create(array_replace([
            'name' => 'Achievement',
            'description' => 'Achievement test.',
            'points_required' => 0,
            'category' => 'special',
            'rarity' => 'common',
            'award_method' => 'automatic',
            'condition_key' => 'manual',
        ], $overrides));
    }
}
