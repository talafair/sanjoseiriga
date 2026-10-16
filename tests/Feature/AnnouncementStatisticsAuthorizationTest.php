<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\EventRsvp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementStatisticsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

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
