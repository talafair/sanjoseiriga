<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\EventRsvp;
use App\Models\EventFacilitator;
use App\Models\EventSubstitution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_unique_id_records_attendance_through_shared_flow(): void
    {
        [$official, $resident, $event] = $this->attendanceSetup();

        $response = $this->actingAs($official)->postJson(route('attendance.check-by-id'), [
            'announcement_id' => $event->id,
            'unique_id' => $resident->unique_id,
            'latitude' => config('talafair.barangay_lat'),
            'longitude' => config('talafair.barangay_lng'),
        ]);

        $response->assertOk()->assertJsonPath('ok', true);
        $this->assertDatabaseHas('attendances', [
            'announcement_id' => $event->id,
            'user_id' => $resident->id,
            'points_awarded' => 100,
        ]);
        $this->assertSame(100, $resident->fresh()->points);
    }

    public function test_scanner_page_has_no_scan_error_before_a_qr_is_received(): void
    {
        [, $resident] = $this->attendanceSetup();

        $this->actingAs($resident)
            ->get(route('attendance.scanner'))
            ->assertOk()
            ->assertDontSee('Invalid event QR code. Please scan the QR code for the correct event.');
    }

    public function test_signed_resident_qr_uses_the_same_attendance_processing(): void
    {
        [$official, $resident, $event] = $this->attendanceSetup();
        $payload = json_encode([
            'id' => $resident->unique_id,
            'sig' => substr(hash_hmac('sha256', $resident->unique_id, config('app.key')), 0, 16),
        ]);

        $response = $this->actingAs($official)->postJson(route('attendance.check'), [
            'token' => $payload,
            'announcement_id' => $event->id,
            'latitude' => config('talafair.barangay_lat'),
            'longitude' => config('talafair.barangay_lng'),
        ]);

        $response->assertOk()->assertJsonPath('resident', $resident->full_name);
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_wrong_event_qr_is_rejected_with_a_clear_message(): void
    {
        [, $resident, $event] = $this->attendanceSetup();

        $response = $this->actingAs($resident)->postJson(route('attendance.check'), [
            'token' => 'not-the-event-qr',
            'announcement_id' => $event->id,
            'latitude' => config('talafair.barangay_lat'),
            'longitude' => config('talafair.barangay_lng'),
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('message', 'Invalid event QR code. Please scan this event\'s QR code.');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_correct_event_qr_outside_the_radius_does_not_record_attendance(): void
    {
        [, $resident, $event] = $this->attendanceSetup();

        $response = $this->actingAs($resident)->postJson(route('attendance.check'), [
            'token' => $event->qr_token,
            'announcement_id' => $event->id,
            'latitude' => (float) config('talafair.barangay_lat') + 1,
            'longitude' => config('talafair.barangay_lng'),
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('ok', false)
            ->assertJsonFragment(['message' => 'Attendance was not recorded. You are about 111,195 m from the venue. Move within 300 m of the venue and scan again.']);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_correct_event_qr_and_location_record_attendance_successfully(): void
    {
        [, $resident, $event] = $this->attendanceSetup();

        $response = $this->actingAs($resident)->postJson(route('attendance.check'), [
            'token' => $event->qr_token,
            'announcement_id' => $event->id,
            'latitude' => config('talafair.barangay_lat'),
            'longitude' => config('talafair.barangay_lng'),
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('message', 'Attendance recorded successfully. You earned +100 points.');
        $this->assertDatabaseHas('attendances', [
            'announcement_id' => $event->id,
            'user_id' => $resident->id,
        ]);
    }

    public function test_official_event_qr_attendance_awards_base_points(): void
    {
        [$official, , $event] = $this->attendanceSetup();

        $response = $this->actingAs($official)->postJson(route('attendance.check'), [
            'token' => $event->qr_token,
            'announcement_id' => $event->id,
            'latitude' => config('talafair.barangay_lat'),
            'longitude' => config('talafair.barangay_lng'),
        ]);

        $response->assertOk()->assertJsonPath('breakdown.total', 100);
        $this->assertSame(100, $official->fresh()->points);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $official->id,
            'user_category' => 'official',
            'points_awarded' => 100,
        ]);
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $official->id,
            'announcement_id' => $event->id,
            'type' => 'event_attendance',
            'base_points' => 100,
            'multiplier' => 1.00,
            'points_awarded' => 100,
        ]);
    }

    public function test_resident_guest_and_official_see_their_own_event_attendance_confirmation(): void
    {
        [$official, $resident, $event] = $this->attendanceSetup();
        $guest = User::factory()->create([
            'role' => 'guest',
            'is_verified' => false,
        ]);
        $event->update(['allow_guest_scanning' => true]);

        $this->actingAs($guest)
            ->get(route('announcements.show', $event))
            ->assertOk()
            ->assertDontSee('Your attendance was recorded for this event.');

        foreach ([$resident, $guest, $official] as $attendee) {
            $this->actingAs($attendee)
                ->postJson(route('attendance.check'), [
                    'token' => $event->qr_token,
                    'announcement_id' => $event->id,
                    'latitude' => config('talafair.barangay_lat'),
                    'longitude' => config('talafair.barangay_lng'),
                ])
                ->assertOk()
                ->assertJsonPath('ok', true);

            $this->get(route('announcements.show', $event))
                ->assertOk()
                ->assertSee('Your attendance was recorded for this event.')
                ->assertSee('Checked in')
                ->assertSee('points earned');
        }
    }

    public function test_official_early_event_qr_attendance_awards_ten_percent_bonus(): void
    {
        [$official, , $event] = $this->attendanceSetup();
        $event->update([
            'event_start_at' => now()->addHour(),
            'event_end_at' => now()->addHours(2),
            'qr_expires_at' => now()->addHours(2),
        ]);

        $this->actingAs($official)->postJson(route('attendance.check'), [
            'token' => $event->qr_token,
            'announcement_id' => $event->id,
            'latitude' => config('talafair.barangay_lat'),
            'longitude' => config('talafair.barangay_lng'),
        ])->assertOk()->assertJsonPath('breakdown.total', 110);

        $this->assertSame(110, $official->fresh()->points);
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $official->id,
            'announcement_id' => $event->id,
            'base_points' => 100,
            'multiplier' => 1.10,
            'points_awarded' => 110,
        ]);
    }

    public function test_assigned_personnel_can_record_own_event_qr_attendance(): void
    {
        [, , $event] = $this->attendanceSetup();
        $personnel = User::factory()->create([
            'role' => 'official',
            'official_group' => 'personnel',
            'is_verified' => true,
            'points' => 0,
        ]);
        EventFacilitator::create([
            'announcement_id' => $event->id,
            'user_id' => $personnel->id,
        ]);

        $this->actingAs($personnel)->postJson(route('attendance.check'), [
            'token' => $event->qr_token,
            'announcement_id' => $event->id,
            'latitude' => config('talafair.barangay_lat'),
            'longitude' => config('talafair.barangay_lng'),
        ])->assertOk()->assertJsonPath('breakdown.total', 100);

        $this->assertSame(100, $personnel->fresh()->points);
    }

    public function test_unassigned_personnel_cannot_record_event_qr_attendance(): void
    {
        [, , $event] = $this->attendanceSetup();
        $personnel = User::factory()->create([
            'role' => 'official',
            'official_group' => 'personnel',
            'is_verified' => true,
        ]);

        $this->actingAs($personnel)->postJson(route('attendance.check'), [
            'token' => $event->qr_token,
            'announcement_id' => $event->id,
            'latitude' => config('talafair.barangay_lat'),
            'longitude' => config('talafair.barangay_lng'),
        ])->assertUnprocessable()->assertJsonPath('ok', false);
    }

    public function test_confirmed_yes_adds_points_after_successful_check_in(): void
    {
        [, $resident, $event] = $this->attendanceSetup();
        $event->update([
            'confirmation_points' => 25,
            'rsvp_due_at' => now()->addHour(),
        ]);

        $this->actingAs($resident)->post(route('announcements.rsvp', $event), [
            'status' => 'attending',
        ])->assertRedirect();

        $this->assertSame(0, $resident->fresh()->points);

        $this->actingAs($resident)->postJson(route('attendance.check'), [
            'token' => $event->qr_token,
            'announcement_id' => $event->id,
            'latitude' => config('talafair.barangay_lat'),
            'longitude' => config('talafair.barangay_lng'),
        ])->assertOk()->assertJsonPath('breakdown.total', 125);

        $this->assertSame(125, $resident->fresh()->points);
    }

    public function test_resident_can_attend_without_confirmation_without_confirmation_points(): void
    {
        [, $resident, $event] = $this->attendanceSetup();
        $event->update(['confirmation_points' => 25]);

        $this->actingAs($resident)->postJson(route('attendance.check'), [
            'token' => $event->qr_token,
            'announcement_id' => $event->id,
            'latitude' => config('talafair.barangay_lat'),
            'longitude' => config('talafair.barangay_lng'),
        ])->assertOk()->assertJsonPath('breakdown.total', 100);

        $this->assertSame(100, $resident->fresh()->points);
    }

    public function test_yes_confirmation_without_attendance_awards_no_points(): void
    {
        [, $resident, $event] = $this->attendanceSetup();
        $event->update([
            'confirmation_points' => 25,
            'rsvp_due_at' => now()->addHour(),
        ]);

        $this->actingAs($resident)->post(route('announcements.rsvp', $event), [
            'status' => 'attending',
        ])->assertRedirect();

        $this->assertSame(0, $resident->fresh()->points);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_duplicate_attendance_does_not_award_points_twice(): void
    {
        [, $resident, $event] = $this->attendanceSetup();
        $event->update(['confirmation_points' => 25]);
        $payload = [
            'token' => $event->qr_token,
            'announcement_id' => $event->id,
            'latitude' => config('talafair.barangay_lat'),
            'longitude' => config('talafair.barangay_lng'),
        ];

        $this->actingAs($resident)->postJson(route('attendance.check'), $payload)->assertOk();
        $this->actingAs($resident)->postJson(route('attendance.check'), $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This resident has already been recorded for this event.');

        $this->assertDatabaseCount('attendances', 1);
        $this->assertSame(100, $resident->fresh()->points);
    }

    public function test_attendance_is_blocked_before_the_two_hour_window(): void
    {
        [, $resident, $event] = $this->attendanceSetup();
        Carbon::setTestNow($event->event_start_at->copy()->subHours(2)->subSecond());

        try {
            $response = $this->actingAs($resident)->postJson(route('attendance.check'), [
                'token' => $event->qr_token,
                'announcement_id' => $event->id,
                'latitude' => config('talafair.barangay_lat'),
                'longitude' => config('talafair.barangay_lng'),
            ]);

            $response->assertUnprocessable()
                ->assertJsonPath('message', 'Attendance scanning is not available yet. Scanning opens 2 hours before the event starts at ' . $event->scanOpensAt()->format('M j, Y g:i A') . '.');
            $this->assertDatabaseCount('attendances', 0);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_attendance_is_allowed_exactly_at_the_two_hour_window(): void
    {
        [, $resident, $event] = $this->attendanceSetup();
        Carbon::setTestNow($event->event_start_at->copy()->subHours(2));

        try {
            $this->actingAs($resident)->postJson(route('attendance.check'), [
                'token' => $event->qr_token,
                'announcement_id' => $event->id,
                'latitude' => config('talafair.barangay_lat'),
                'longitude' => config('talafair.barangay_lng'),
            ])->assertOk()->assertJsonPath('ok', true);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_resident_outside_the_event_audience_is_rejected(): void
    {
        [, $resident, $event] = $this->attendanceSetup();
        $event->update(['audiences' => ['family_heads']]);

        $this->actingAs($resident)->postJson(route('attendance.check'), [
            'token' => $event->qr_token,
            'announcement_id' => $event->id,
            'latitude' => config('talafair.barangay_lat'),
            'longitude' => config('talafair.barangay_lng'),
        ])->assertUnprocessable()->assertJsonPath('message', 'You are not eligible to attend this event.');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_invalid_or_duplicate_manual_id_is_rejected_without_extra_points(): void
    {
        [$official, $resident, $event] = $this->attendanceSetup();
        $payload = [
            'announcement_id' => $event->id,
            'unique_id' => $resident->unique_id,
            'latitude' => config('talafair.barangay_lat'),
            'longitude' => config('talafair.barangay_lng'),
        ];

        $this->actingAs($official)->postJson(route('attendance.check-by-id'), [
            ...$payload,
            'unique_id' => 'not-a-real-id',
        ])->assertUnprocessable()->assertJsonPath('ok', false);

        $this->actingAs($official)->postJson(route('attendance.check-by-id'), $payload)->assertOk();
        $this->actingAs($official)->postJson(route('attendance.check-by-id'), $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This resident has already been recorded for this event.');

        $this->assertDatabaseCount('attendances', 1);
        $this->assertSame(100, $resident->fresh()->points);
    }

    public function test_only_officials_can_use_manual_unique_id_fallback(): void
    {
        [, $resident, $event] = $this->attendanceSetup();

        $this->actingAs($resident)->postJson(route('attendance.check-by-id'), [
            'announcement_id' => $event->id,
            'unique_id' => $resident->unique_id,
            'latitude' => config('talafair.barangay_lat'),
            'longitude' => config('talafair.barangay_lng'),
        ])->assertForbidden();
    }

    public function test_assigned_substitute_can_answer_the_event_rsvp(): void
    {
        [, $substitute, $event] = $this->substituteSetup();

        $this->actingAs($substitute)
            ->get(route('announcements.show', $event))
            ->assertOk()
            ->assertSee('You were assigned to attend this event as a substitute.');

        $this->actingAs($substitute)->post(route('announcements.rsvp', $event), [
            'status' => 'attending',
        ])->assertRedirect();

        $this->assertDatabaseHas('event_rsvps', [
            'announcement_id' => $event->id,
            'user_id' => $substitute->id,
            'status' => 'attending',
        ]);
    }

    public function test_assigned_substitute_can_change_their_answer_to_no(): void
    {
        [, $substitute, $event] = $this->substituteSetup();

        EventRsvp::create([
            'announcement_id' => $event->id,
            'user_id' => $substitute->id,
            'status' => 'attending',
            'responded_at' => now(),
        ]);

        $this->actingAs($substitute)->post(route('announcements.rsvp', $event), [
            'status' => 'not_attending',
            'reason' => 'I am unavailable.',
        ])->assertRedirect();

        $this->assertDatabaseHas('event_rsvps', [
            'announcement_id' => $event->id,
            'user_id' => $substitute->id,
            'status' => 'not_attending',
            'reason' => 'I am unavailable.',
        ]);
    }

    private function attendanceSetup(): array
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'barangay_captain',
            'is_verified' => true,
        ]);
        $resident = User::factory()->create([
            'role' => 'resident',
            'is_verified' => true,
            'unique_id' => 'Z2-26-000000001',
            'points' => 0,
        ]);
        $event = Announcement::create([
            'title' => 'Test event',
            'category' => 'events',
            'body' => 'Attendance test',
            'is_event' => true,
            'event_start_at' => now()->subHour(),
            'event_end_at' => now()->addHour(),
            'qr_token' => 'event-token-' . $resident->id,
            'qr_expires_at' => now()->addHour(),
            'base_points' => 100,
            'audiences' => ['public'],
            'venue_lat' => config('talafair.barangay_lat'),
            'venue_lng' => config('talafair.barangay_lng'),
            'geofence_radius' => 300,
        ]);

        return [$official, $resident, $event];
    }

    private function substituteSetup(): array
    {
        [$official, $substitute, $event] = $this->attendanceSetup();
        $head = User::factory()->create([
            'role' => 'resident',
            'is_head_of_family' => true,
        ]);

        $event->update([
            'audiences' => ['family_heads'],
            'rsvp_due_at' => now()->addHour(),
        ]);
        EventSubstitution::create([
            'announcement_id' => $event->id,
            'family_head_id' => $head->id,
            'substitute_user_id' => $substitute->id,
        ]);

        return [$official, $substitute, $event];
    }
}
