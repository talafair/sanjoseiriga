<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\EventRsvp;
use App\Models\User;
use Tests\TestCase;

class EventRsvpTest extends TestCase
{
    public function test_resident_can_submit_and_update_an_event_rsvp(): void
    {
        $resident = User::factory()->create(['role' => 'resident']);
        $event = $this->event();

        $this->actingAs($resident)
            ->post(route('announcements.rsvp', $event), ['status' => 'attending'])
            ->assertRedirect()
            ->assertSessionHas('success', 'You are on the list. See you there.');

        $this->post(route('announcements.rsvp', $event), [
            'status' => 'not_attending',
            'reason' => 'I will be away.',
        ])->assertRedirect()->assertSessionHas('success', 'Your response was recorded.');

        $this->assertDatabaseCount('event_rsvps', 1);
        $this->assertDatabaseHas('event_rsvps', [
            'announcement_id' => $event->id,
            'user_id' => $resident->id,
            'status' => 'not_attending',
            'reason' => 'I will be away.',
        ]);
    }

    public function test_negative_rsvp_requires_a_reason_and_invalid_status_is_rejected(): void
    {
        $resident = User::factory()->create(['role' => 'resident']);
        $event = $this->event();

        $this->actingAs($resident)
            ->from(route('announcements.show', $event))
            ->post(route('announcements.rsvp', $event), ['status' => 'not_attending'])
            ->assertRedirect(route('announcements.show', $event))
            ->assertSessionHasErrors('reason');

        $this->post(route('announcements.rsvp', $event), ['status' => 'maybe'])
            ->assertSessionHasErrors('status');

        $this->assertSame(0, EventRsvp::where('announcement_id', $event->id)->count());
    }

    public function test_resident_outside_the_audience_cannot_submit_an_rsvp(): void
    {
        $resident = User::factory()->create([
            'role' => 'resident',
            'is_head_of_family' => false,
        ]);
        $event = $this->event(['audiences' => ['family_heads']]);

        $this->actingAs($resident)
            ->from(route('announcements.show', $event))
            ->post(route('announcements.rsvp', $event), ['status' => 'attending'])
            ->assertRedirect(route('announcements.show', $event))
            ->assertSessionHas('error', 'This survey is for a different audience.');

        $this->assertSame(0, EventRsvp::where('announcement_id', $event->id)->count());
    }

    public function test_closed_event_survey_rejects_an_rsvp(): void
    {
        $resident = User::factory()->create(['role' => 'resident']);
        $event = $this->event(['rsvp_due_at' => now()->subMinute()]);

        $this->actingAs($resident)
            ->from(route('announcements.show', $event))
            ->post(route('announcements.rsvp', $event), ['status' => 'attending'])
            ->assertRedirect(route('announcements.show', $event))
            ->assertSessionHas('error');

        $this->assertSame(0, EventRsvp::where('announcement_id', $event->id)->count());
    }

    private function event(array $overrides = []): Announcement
    {
        return Announcement::create(array_replace([
            'title' => 'Upcoming event',
            'category' => 'events',
            'body' => 'Event details.',
            'is_event' => true,
            'event_start_at' => now()->addDay(),
            'event_end_at' => now()->addDay()->addHours(2),
            'rsvp_due_at' => now()->addHours(12),
            'audiences' => ['public'],
        ], $overrides));
    }
}
