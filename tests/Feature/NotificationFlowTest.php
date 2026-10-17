<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Survey;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_bell_shows_unread_count_and_opening_notification_marks_it_read(): void
    {
        $user = User::factory()->create();
        $notification = UserNotification::create([
            'user_id' => $user->id,
            'title' => 'Account update',
            'body' => 'Your account was updated.',
        ]);

        UserNotification::insert(collect(range(1, 9))->map(fn () => [
            'user_id' => $user->id,
            'title' => 'Another update',
            'body' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('9+', false);

        $this->actingAs($user)
            ->get(route('notifications.open', $notification))
            ->assertOk()
            ->assertSee('Your account was updated.');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_notifications_use_bootstrap_pagination_like_the_system_log(): void
    {
        $user = User::factory()->create();
        UserNotification::insert(collect(range(1, 16))->map(fn ($index) => [
            'user_id' => $user->id,
            'title' => "Update {$index}",
            'created_at' => now()->subMinutes($index),
            'updated_at' => now()->subMinutes($index),
        ])->all());

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('aria-label="Notification pagination"', false)
            ->assertSee('class="pagination"', false)
            ->assertSee('page=2');

        $this->get(route('notifications.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('aria-label="Notification pagination"', false)
            ->assertSee('rel="prev"', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_announcement_notifications_open_the_linked_announcement(): void
    {
        $user = User::factory()->create();
        $announcement = $this->announcement();
        $notification = UserNotification::create([
            'user_id' => $user->id,
            'announcement_id' => $announcement->id,
            'title' => 'New event: ' . $announcement->title,
            'body' => 'You are invited to this event.',
        ]);

        $this->actingAs($user)
            ->get(route('notifications.open', $notification))
            ->assertRedirect(route('announcements.show', $announcement));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_survey_notifications_open_the_linked_survey_when_user_is_eligible(): void
    {
        $user = User::factory()->create();
        $survey = $this->survey($user);
        $notification = UserNotification::create([
            'user_id' => $user->id,
            'survey_id' => $survey->id,
            'title' => 'New survey: ' . $survey->title,
            'body' => 'Share your feedback.',
        ]);

        $this->actingAs($user)
            ->get(route('notifications.open', $notification))
            ->assertRedirect(route('surveys.show', $survey));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_deleted_announcement_keeps_notification_and_shows_unavailable_message(): void
    {
        $user = User::factory()->create();
        $announcement = $this->announcement();
        $notification = UserNotification::create([
            'user_id' => $user->id,
            'announcement_id' => $announcement->id,
            'title' => 'New event: ' . $announcement->title,
            'body' => 'Event invitation details.',
        ]);

        $announcement->delete();

        $this->assertDatabaseHas('user_notifications', [
            'id' => $notification->id,
            'announcement_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('notifications.open', $notification))
            ->assertOk()
            ->assertSee('This update is no longer available.')
            ->assertSee('Event invitation details.');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_deleted_or_inaccessible_survey_shows_unavailable_message(): void
    {
        $user = User::factory()->create();
        $survey = $this->survey($user);
        $notification = UserNotification::create([
            'user_id' => $user->id,
            'survey_id' => $survey->id,
            'title' => 'New survey: ' . $survey->title,
            'body' => 'Share your feedback.',
        ]);

        $survey->delete();

        $this->actingAs($user)
            ->get(route('notifications.open', $notification))
            ->assertOk()
            ->assertSee('This update is no longer available.')
            ->assertSee('Share your feedback.');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_outside_survey_audience_cannot_open_its_details(): void
    {
        $user = User::factory()->create(['role' => 'resident']);
        $creator = User::factory()->create();
        $survey = $this->survey($creator, 'officials');
        $notification = UserNotification::create([
            'user_id' => $user->id,
            'survey_id' => $survey->id,
            'title' => 'New survey: ' . $survey->title,
            'body' => 'Share your feedback.',
        ]);

        $this->actingAs($user)
            ->get(route('notifications.open', $notification))
            ->assertOk()
            ->assertSee('This update is no longer available.')
            ->assertDontSee('Survey details.');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_notification_displays_its_own_details_and_relevant_destination(): void
    {
        $user = User::factory()->create();

        foreach ([
            ['title' => 'New trivia published', 'destination' => 'Open Trivia', 'route' => route('games.index')],
            ['title' => 'Badge unlocked', 'destination' => 'View badges', 'route' => route('badges.catalog')],
        ] as $index => $case) {
            $notification = UserNotification::create([
                'user_id' => $user->id,
                'title' => $case['title'],
                'body' => "Details for notification {$index}.",
            ]);

            $this->actingAs($user)
                ->get(route('notifications.open', $notification))
                ->assertOk()
                ->assertSee($case['title'])
                ->assertSee("Details for notification {$index}.")
                ->assertSee($case['destination'])
                ->assertSee($case['route'], false);

            $this->assertNotNull($notification->fresh()->read_at);
        }
    }

    public function test_account_notifications_open_the_users_account_page(): void
    {
        $user = User::factory()->create();
        foreach (['Account created by an official', 'Account information updated'] as $title) {
            $notification = UserNotification::create([
                'user_id' => $user->id,
                'title' => $title,
                'body' => 'Your account information was updated.',
            ]);

            $this->actingAs($user)
                ->get(route('notifications.open', $notification))
                ->assertRedirect(route('account'));

            $this->assertNotNull($notification->fresh()->read_at);
        }
    }

    public function test_deleted_record_audit_notifications_explain_that_details_are_unavailable(): void
    {
        $user = User::factory()->create();

        foreach (['Deleted Announcement', 'Deleted Survey'] as $title) {
            $notification = UserNotification::create([
                'user_id' => $user->id,
                'title' => $title,
                'body' => "{$title} audit details.",
            ]);

            $this->actingAs($user)
                ->get(route('notifications.open', $notification))
                ->assertOk()
                ->assertSee('This update is no longer available.')
                ->assertSee("{$title} audit details.");
        }
    }

    public function test_user_cannot_open_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = UserNotification::create([
            'user_id' => $owner->id,
            'title' => 'Private update',
        ]);

        $this->actingAs($otherUser)
            ->get(route('notifications.open', $notification))
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    private function announcement(): Announcement
    {
        return Announcement::create([
            'title' => 'Community meeting',
            'category' => 'events',
            'body' => 'Meeting details.',
        ]);
    }

    private function survey(User $creator, string $audience = 'public'): Survey
    {
        return Survey::create([
            'title' => 'Community feedback',
            'description' => 'Survey details.',
            'questions' => ['How was it?'],
            'audience' => $audience,
            'created_by' => $creator->id,
        ]);
    }
}
