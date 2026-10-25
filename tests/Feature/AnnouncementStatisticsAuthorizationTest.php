<?php

namespace Tests\Feature;

use App\Http\Controllers\AnnouncementController;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\EventRsvp;
use App\Models\EventSubstitution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementStatisticsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_statistics_calculate_audience_rsvp_and_check_in_totals(): void
    {
        $attending = User::factory()->create(['role' => 'resident']);
        $notAttending = User::factory()->create(['role' => 'resident']);
        User::factory()->create(['role' => 'resident']);
        $officialAttendee = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'is_verified' => true,
        ]);
        $event = Announcement::create([
            'title' => 'Statistics totals event',
            'category' => 'events',
            'body' => 'Event details.',
            'is_event' => true,
            'event_start_at' => now()->subHour(),
            'event_end_at' => now()->addHour(),
            'audiences' => ['public'],
        ]);

        EventRsvp::create([
            'announcement_id' => $event->id,
            'user_id' => $attending->id,
            'status' => 'attending',
            'responded_at' => now(),
        ]);
        EventRsvp::create([
            'announcement_id' => $event->id,
            'user_id' => $notAttending->id,
            'status' => 'not_attending',
            'reason' => 'Unavailable.',
            'responded_at' => now(),
        ]);
        Attendance::create([
            'announcement_id' => $event->id,
            'user_id' => $attending->id,
            'user_category' => 'resident',
            'scanned_at' => now(),
            'is_early' => true,
        ]);
        Attendance::create([
            'announcement_id' => $event->id,
            'user_id' => $officialAttendee->id,
            'user_category' => 'official',
            'scanned_at' => now(),
        ]);

        $stats = app(AnnouncementController::class)->statistics($event, true);

        $this->assertSame(4, $stats['audience']);
        $this->assertSame(1, $stats['attending']);
        $this->assertSame(1, $stats['not_attending']);
        $this->assertSame(2, $stats['no_response']);
        $this->assertSame(1, $stats['resident_scanned']);
        $this->assertSame(1, $stats['official_scanned']);
        $this->assertSame(2, $stats['scanned']);
        $this->assertSame(1, $stats['early']);
        $this->assertSame(1, $stats['on_time']);
        $this->assertSame(100.0, $stats['turnout_rate']);
        $this->assertSame('Unavailable.', $stats['reasons']->first()->reason);
    }

    public function test_residents_guests_and_personnel_can_only_view_aggregate_statistics(): void
    {
        [$event, $attendee] = $this->eventWithAttendance();

        EventRsvp::create([
            'announcement_id' => $event->id,
            'user_id' => $attendee->id,
            'status' => 'not_attending',
            'reason' => 'Private attendance reason',
            'responded_at' => now(),
        ]);

        $viewers = [
            User::factory()->create(['role' => 'resident']),
            User::factory()->create(['role' => 'guest']),
            User::factory()->create(['role' => 'official', 'official_group' => 'personnel']),
            User::factory()->create(['role' => 'official', 'official_group' => 'barangay_council', 'is_verified' => false]),
        ];

        foreach ($viewers as $viewer) {
            $this->actingAs($viewer)
                ->get(route('announcements.statistics', [$event, 'user_name' => $attendee->full_name]))
                ->assertOk()
                ->assertSee('Attendance statistics')
                ->assertSee('Total checked in')
                ->assertDontSee('Check-in log')
                ->assertDontSee($attendee->full_name)
                ->assertDontSee($attendee->unique_id)
                ->assertDontSee('Private attendance reason');
        }
    }

    public function test_barangay_officials_and_superadmins_can_view_attendee_details(): void
    {
        [$event, $attendee] = $this->eventWithAttendance();
        $viewers = [
            User::factory()->create(['role' => 'official', 'official_group' => 'barangay_council', 'is_verified' => true]),
            User::factory()->create(['role' => User::SUPERADMIN_ROLE]),
        ];

        foreach ($viewers as $viewer) {
            $this->actingAs($viewer)
                ->get(route('announcements.statistics', $event))
                ->assertOk()
                ->assertSee('Check-in log')
                ->assertSee($attendee->full_name)
                ->assertSee($attendee->unique_id);
        }
    }

    public function test_residents_guests_and_personnel_cannot_access_attendance_exports(): void
    {
        [$event] = $this->eventWithAttendance();
        $viewers = [
            User::factory()->create(['role' => 'resident']),
            User::factory()->create(['role' => 'guest']),
            User::factory()->create(['role' => 'official', 'official_group' => 'personnel']),
        ];
        $exportRoutes = [
            'announcements.summary.download',
            'announcements.summary.print',
            'announcements.attendance.download',
            'announcements.attendance.print',
        ];

        foreach ($viewers as $viewer) {
            foreach ($exportRoutes as $routeName) {
                $this->actingAs($viewer)
                    ->get(route($routeName, $event))
                    ->assertRedirect(route('announcements'));
            }
        }
    }

    public function test_barangay_officials_and_superadmins_can_access_attendance_exports(): void
    {
        [$event] = $this->eventWithAttendance();
        $viewers = [
            User::factory()->create(['role' => 'official', 'official_group' => 'barangay_council', 'is_verified' => true]),
            User::factory()->create(['role' => User::SUPERADMIN_ROLE]),
        ];
        $exportRoutes = [
            'announcements.summary.download',
            'announcements.summary.print',
            'announcements.attendance.download',
            'announcements.attendance.print',
        ];

        foreach ($viewers as $viewer) {
            foreach ($exportRoutes as $routeName) {
                $this->actingAs($viewer)
                    ->get(route($routeName, $event))
                    ->assertOk()
                    ->assertHeader('content-type', 'application/pdf');
            }
        }
    }

    public function test_officials_can_view_event_audience_members_without_a_check_in(): void
    {
        [$event, $checkedIn] = $this->eventWithAttendance();
        $notCheckedIn = User::factory()->create([
            'role' => 'resident',
            'first_name' => 'Absent',
            'last_name' => 'Resident',
            'unique_id' => 'Z2-26-000000123',
        ]);
        $guest = User::factory()->create([
            'role' => 'guest',
            'first_name' => 'Absent',
            'last_name' => 'Guest',
            'unique_id' => 'GZ2-26-000000123',
        ]);
        $event->update(['allow_guest_scanning' => true]);
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'is_verified' => true,
        ]);

        $this->actingAs($official)
            ->get(route('announcements.not-checked-in', $event))
            ->assertOk()
            ->assertSee($notCheckedIn->full_name)
            ->assertSee($guest->full_name)
            ->assertDontSee($checkedIn->full_name);
    }

    public function test_not_checked_in_page_does_not_include_guests_when_guest_scanning_is_disabled(): void
    {
        [$event] = $this->eventWithAttendance();
        $guest = User::factory()->create([
            'role' => 'guest',
            'first_name' => 'Excluded',
            'last_name' => 'Guest',
        ]);
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'is_verified' => true,
        ]);

        $this->actingAs($official)
            ->get(route('announcements.not-checked-in', $event))
            ->assertOk()
            ->assertDontSee($guest->full_name);
    }

    public function test_public_event_no_show_list_excludes_officials_and_superadmins(): void
    {
        [$event] = $this->eventWithAttendance();
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'first_name' => 'Not',
            'last_name' => 'Attendee',
            'is_verified' => true,
        ]);
        $personnel = User::factory()->create([
            'role' => 'official',
            'official_group' => 'personnel',
            'first_name' => 'Not',
            'last_name' => 'Audience',
            'is_verified' => true,
        ]);
        $superadmin = User::factory()->create([
            'role' => User::SUPERADMIN_ROLE,
            'first_name' => 'Not',
            'last_name' => 'Resident',
        ]);
        $viewer = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'is_verified' => true,
        ]);

        $this->actingAs($viewer)
            ->get(route('announcements.not-checked-in', $event))
            ->assertOk()
            ->assertDontSee($official->full_name)
            ->assertDontSee($personnel->full_name)
            ->assertDontSee($superadmin->full_name);
    }

    public function test_event_targeted_to_officials_includes_officials_in_no_show_list(): void
    {
        [$event] = $this->eventWithAttendance();
        $event->update(['audiences' => ['officials']]);
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'first_name' => 'Expected',
            'last_name' => 'Official',
            'is_verified' => true,
        ]);
        $viewer = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'is_verified' => true,
        ]);

        $this->actingAs($viewer)
            ->get(route('announcements.not-checked-in', $event))
            ->assertOk()
            ->assertSee($official->full_name);
    }

    public function test_only_officials_can_view_who_has_not_checked_in(): void
    {
        [$event] = $this->eventWithAttendance();
        $resident = User::factory()->create(['role' => 'resident']);

        $this->actingAs($resident)
            ->get(route('announcements.not-checked-in', $event))
            ->assertRedirect(route('announcements'));
    }

    public function test_not_checked_in_list_includes_assigned_substitutes_outside_the_audience(): void
    {
        [$event] = $this->eventWithAttendance();
        $event->update(['audiences' => ['students']]);
        $head = User::factory()->create([
            'role' => 'resident',
            'is_head_of_family' => true,
        ]);
        $substitute = User::factory()->create([
            'role' => 'resident',
            'first_name' => 'Assigned',
            'last_name' => 'Substitute',
            'is_student' => false,
        ]);
        $unrelated = User::factory()->create([
            'role' => 'resident',
            'first_name' => 'Unrelated',
            'last_name' => 'Resident',
            'is_student' => false,
        ]);
        EventSubstitution::create([
            'announcement_id' => $event->id,
            'family_head_id' => $head->id,
            'substitute_user_id' => $substitute->id,
        ]);
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'is_verified' => true,
        ]);

        $this->actingAs($official)
            ->get(route('announcements.not-checked-in', $event))
            ->assertOk()
            ->assertSee($substitute->full_name)
            ->assertDontSee($unrelated->full_name);
    }

    private function eventWithAttendance(): array
    {
        $attendee = User::factory()->create([
            'role' => 'resident',
            'first_name' => 'Private',
            'last_name' => 'Attendee',
            'unique_id' => 'Z2-26-987654321',
        ]);

        $event = Announcement::create([
            'title' => 'Statistics privacy event',
            'category' => 'events',
            'body' => 'Event details',
            'is_event' => true,
            'event_start_at' => now()->subHour(),
            'event_end_at' => now()->addHour(),
            'audiences' => ['public'],
            'raffle_enabled' => true,
        ]);

        Attendance::create([
            'announcement_id' => $event->id,
            'user_id' => $attendee->id,
            'user_category' => 'resident',
            'attendance_method' => 'event_qr_scan',
            'scanned_at' => now(),
            'points_awarded' => 10,
        ]);

        return [$event, $attendee];
    }
}
