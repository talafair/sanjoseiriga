<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\EventFacilitator;
use App\Models\UserNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_announcement_does_not_require_or_store_event_data(): void
    {
        $official = $this->official();
        $resident = User::factory()->create();

        $this->actingAs($official)
            ->post(route('announcements.store'), [
                'title' => 'Barangay update',
                'category' => 'updates',
                'body' => 'Regular announcement content.',
            ])
            ->assertRedirect();

        $announcement = Announcement::query()->latest('id')->firstOrFail();

        $this->assertFalse($announcement->is_event);
        $this->assertSame(0, $announcement->confirmation_points);
        $this->assertSame(0, $announcement->base_points);
        $this->assertSame(0, $announcement->participation_points);
        $this->assertNull($announcement->qr_token);
        $this->assertNull($announcement->event_start_at);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $resident->id,
            'announcement_id' => $announcement->id,
            'title' => 'New announcement: Barangay update',
        ]);
    }

    public function test_event_creation_stores_confirmation_points_and_generates_qr_token(): void
    {
        $official = $this->official();

        $this->actingAs($official)
            ->post(route('announcements.store'), [
                'title' => 'Community meeting',
                'category' => 'events',
                'body' => 'Event details.',
                'is_event' => '1',
                'event_start_at' => now()->addDay()->format('Y-m-d H:i'),
                'event_end_at' => now()->addDay()->addHours(2)->format('Y-m-d H:i'),
                'rsvp_due_at' => now()->addDay()->subHour()->format('Y-m-d H:i'),
                'audiences' => ['public'],
                'base_points' => 50,
                'confirmation_points' => 15,
                'venue_lat' => 14.1,
                'venue_lng' => 122.9,
                'geofence_radius' => 300,
            ])
            ->assertRedirect();

        $announcement = Announcement::query()->latest('id')->firstOrFail();

        $this->assertTrue($announcement->is_event);
        $this->assertSame(15, $announcement->confirmation_points);
        $this->assertNotNull($announcement->qr_token);
    }

    public function test_event_can_be_published_without_facilitators(): void
    {
        $official = $this->official();

        $this->actingAs($official)->post(route('announcements.store'), $this->eventPayload())->assertRedirect();

        $event = Announcement::query()->latest('id')->firstOrFail();
        $this->assertCount(0, $event->facilitators);
    }

    public function test_new_classification_audiences_notify_only_eligible_users_and_survive_edit_without_audience_input(): void
    {
        $official = $this->official();
        $sjtoda = User::factory()->create(['is_sjtoda' => true]);
        $bpc = User::factory()->create(['is_bpc' => true]);
        $other = User::factory()->create();
        $payload = [...$this->eventPayload(), 'audiences' => ['sjtoda', 'bpc']];

        $this->actingAs($official)
            ->post(route('announcements.store'), $payload)
            ->assertRedirect();

        $event = Announcement::query()->latest('id')->firstOrFail();
        $this->assertSame(['sjtoda', 'bpc'], $event->audiences);
        $this->actingAs($official)
            ->get(route('announcements.index'))
            ->assertOk()
            ->assertSee('SJTODA')
            ->assertSee('BPC')
            ->assertSee('data-audience-all="add"', false)
            ->assertSee('data-audience-group="add"', false);
        $this->assertTrue($sjtoda->fresh()->belongsToAudience($event->audiences));
        $this->assertTrue($bpc->fresh()->belongsToAudience($event->audiences));
        $this->assertFalse($other->fresh()->belongsToAudience($event->audiences));
        $this->assertEqualsCanonicalizing(
            [$sjtoda->id, $bpc->id],
            $event->audienceQuery()->pluck('users.id')->all()
        );
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $sjtoda->id,
            'announcement_id' => $event->id,
            'title' => 'New event: Community meeting',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $bpc->id,
            'announcement_id' => $event->id,
            'title' => 'New event: Community meeting',
        ]);
        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $other->id,
            'announcement_id' => $event->id,
        ]);

        $updatePayload = array_diff_key($payload, ['audiences' => true]);
        $this->actingAs($official)
            ->put(route('announcements.update', $event), $updatePayload)
            ->assertRedirect();

        $this->assertSame(['sjtoda', 'bpc'], $event->fresh()->audiences);
    }

    public function test_event_updates_notify_the_current_audience_and_no_op_edits_do_not_duplicate_notifications(): void
    {
        $official = $this->official();
        $sjtoda = User::factory()->create(['is_sjtoda' => true]);
        $bpc = User::factory()->create(['is_bpc' => true]);
        $other = User::factory()->create();

        $this->actingAs($official)->post(route('announcements.store'), [
            ...$this->eventPayload(),
            'audiences' => ['sjtoda'],
        ])->assertRedirect();
        $event = Announcement::query()->latest('id')->firstOrFail();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $sjtoda->id,
            'announcement_id' => $event->id,
            'title' => 'New event: Community meeting',
        ]);

        $updatedPayload = [
            ...$this->eventPayload(),
            'title' => 'Updated community meeting',
            'body' => 'Updated event details.',
            'audiences' => ['bpc'],
        ];
        $this->actingAs($official)
            ->put(route('announcements.update', $event), $updatedPayload)
            ->assertRedirect();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $bpc->id,
            'announcement_id' => $event->id,
            'title' => 'Event updated: Updated community meeting',
            'body' => 'The event details have changed. Please review the latest information.',
        ]);
        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $other->id,
            'announcement_id' => $event->id,
            'title' => 'Event updated: Updated community meeting',
        ]);

        $this->actingAs($official)
            ->put(route('announcements.update', $event), $updatedPayload)
            ->assertRedirect();

        $this->assertSame(1, UserNotification::where('user_id', $bpc->id)
            ->where('announcement_id', $event->id)
            ->where('title', 'Event updated: Updated community meeting')
            ->count());
    }

    public function test_regular_announcement_edits_notify_users(): void
    {
        $official = $this->official();
        $resident = User::factory()->create();

        $this->actingAs($official)->post(route('announcements.store'), [
            'title' => 'Service update',
            'category' => 'updates',
            'body' => 'Initial details.',
        ])->assertRedirect();
        $announcement = Announcement::query()->latest('id')->firstOrFail();

        $this->actingAs($official)->put(route('announcements.update', $announcement), [
            'title' => 'Service update',
            'category' => 'updates',
            'body' => 'Revised details.',
        ])->assertRedirect();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $resident->id,
            'announcement_id' => $announcement->id,
            'title' => 'Announcement updated: Service update',
            'body' => 'The announcement details have changed. Please review the latest information.',
        ]);
    }

    public function test_event_facilitators_can_be_added_without_duplicates(): void
    {
        $official = $this->official();
        $facilitator = $this->official('event.personnel');
        $secondFacilitator = $this->official('event.personnel.second');

        $this->actingAs($official)->post(route('announcements.store'), [
            ...$this->eventPayload(),
            'facilitator_ids' => [$facilitator->id, $facilitator->id, $secondFacilitator->id],
        ])->assertRedirect();

        $event = Announcement::query()->latest('id')->firstOrFail();
        $this->assertDatabaseHas('event_facilitators', [
            'announcement_id' => $event->id,
            'user_id' => $facilitator->id,
            'assigned_by' => $official->id,
        ]);
        $this->assertSame(2, EventFacilitator::where('announcement_id', $event->id)->count());
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $facilitator->id,
            'announcement_id' => $event->id,
            'title' => 'Event facilitator assignment',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $secondFacilitator->id,
            'announcement_id' => $event->id,
            'title' => 'Event facilitator assignment',
        ]);
    }

    public function test_event_facilitators_can_be_updated_and_removed(): void
    {
        $official = $this->official();
        $first = $this->official('event.first');
        $second = $this->official('event.second');

        $this->actingAs($official)->post(route('announcements.store'), [
            ...$this->eventPayload(),
            'facilitator_ids' => [$first->id],
        ])->assertRedirect();
        $event = Announcement::query()->latest('id')->firstOrFail();

        $this->actingAs($official)->put(route('announcements.update', $event), [
            ...$this->eventPayload(),
            'facilitator_ids' => [$second->id],
        ])->assertRedirect();

        $this->assertDatabaseMissing('event_facilitators', ['announcement_id' => $event->id, 'user_id' => $first->id]);
        $this->assertDatabaseHas('event_facilitators', ['announcement_id' => $event->id, 'user_id' => $second->id]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $second->id,
            'announcement_id' => $event->id,
            'title' => 'Event facilitator assignment',
        ]);
        $this->assertSame(1, UserNotification::where('user_id', $first->id)
            ->where('announcement_id', $event->id)
            ->where('title', 'Event facilitator assignment')
            ->count());

        $this->actingAs($official)->put(route('announcements.update', $event), $this->eventPayload())->assertRedirect();
        $this->assertDatabaseCount('event_facilitators', 0);
    }

    private function eventPayload(): array
    {
        return [
            'title' => 'Community meeting',
            'category' => 'events',
            'body' => 'Event details.',
            'is_event' => '1',
            'event_start_at' => now()->addDay()->format('Y-m-d H:i'),
            'event_end_at' => now()->addDay()->addHours(2)->format('Y-m-d H:i'),
            'rsvp_due_at' => now()->addDay()->subHour()->format('Y-m-d H:i'),
            'audiences' => ['public'],
            'base_points' => 50,
            'venue_lat' => 14.1,
            'venue_lng' => 122.9,
            'geofence_radius' => 300,
        ];
    }

    private function official(string $username = 'official'): User
    {
        return User::factory()->create([
            'username' => $username,
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => true,
        ]);
    }
}
