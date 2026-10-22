<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\EventRaffleEntry;
use App\Models\GameRun;
use App\Models\PointTransaction;
use App\Models\SpinHistory;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\SuperadminNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperadminAccountPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_account_page_only_shows_the_requested_sections(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::SUPERADMIN_ROLE,
            'unique_id' => 'ADMIN-100',
            'is_verified' => true,
        ]);

        $this->actingAs($superadmin)
            ->get(route('account'))
            ->assertOk()
            ->assertSee('Profile picture')
            ->assertSee('Change password')
            ->assertSee('Install TalaFair')
            ->assertSee('Need help?')
            ->assertDontSee('ADMIN-100')
            ->assertDontSee('View my ID card')
            ->assertDontSee('Account settings')
            ->assertDontSee('My badges')
            ->assertDontSee('Points history')
            ->assertDontSee('My household');
    }

    public function test_superadmin_cannot_access_resident_id_or_qr_endpoints(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::SUPERADMIN_ROLE,
            'unique_id' => 'ADMIN-100',
        ]);

        $this->actingAs($superadmin)
            ->get(route('id-card.show'))
            ->assertNotFound();

        $this->actingAs($superadmin)
            ->get(route('id-card.qr'))
            ->assertNotFound();
    }

    public function test_official_profile_edit_persists_classifications_and_updates_zone_based_id(): void
    {
        $year = now()->format('y');
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'zone' => '1',
            'unique_id' => "Z1-{$year}000000001",
        ]);
        User::factory()->create([
            'role' => 'resident',
            'zone' => '2',
            'unique_id' => "Z2-{$year}000000123",
        ]);

        $this->actingAs($official)
            ->patch(route('profile.update'), [
                'first_name' => 'Profile',
                'middle_name' => '',
                'last_name' => 'Official',
                'suffix' => '',
                'sex_at_birth' => 'female',
                'preferred_gender_identity' => 'woman',
                'birthdate' => '1990-01-01',
                'contact_number' => '09170000000',
                'house_no' => '10',
                'street' => 'Main Street',
                'zone' => '2',
                'email' => $official->email,
                'official_group' => 'barangay_council',
                'official_position' => 'kagawad',
                'is_sjtoda' => '1',
                'is_bpc' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $official->id,
            'is_sjtoda' => true,
            'is_bpc' => true,
        ]);
        $this->assertSame("Z2-{$year}000000124", $official->fresh()->unique_id);
    }

    public function test_official_audience_notifications_include_superadmins_but_not_personnel(): void
    {
        $superadmin = User::factory()->create(['role' => User::SUPERADMIN_ROLE]);
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'is_verified' => true,
        ]);
        $personnel = User::factory()->create(['role' => 'official', 'official_group' => 'personnel']);
        $resident = User::factory()->create(['role' => 'resident']);

        $announcement = Announcement::create([
            'title' => 'Official notice',
            'category' => 'updates',
            'body' => 'Notice for officials.',
            'audiences' => ['officials'],
        ]);

        $recipientIds = $announcement->audienceQuery()->pluck('users.id');

        $this->assertTrue($recipientIds->contains($superadmin->id));
        $this->assertTrue($recipientIds->contains($official->id));
        $this->assertFalse($recipientIds->contains($personnel->id));
        $this->assertFalse($recipientIds->contains($resident->id));
    }

    public function test_superadmins_do_not_receive_user_update_notifications_but_keep_other_data_change_notifications(): void
    {
        $superadmin = User::factory()->create(['role' => User::SUPERADMIN_ROLE]);
        $actor = User::factory()->create(['role' => 'official']);
        $target = User::factory()->create(['role' => 'resident']);

        $this->actingAs($actor);
        $target->update(['points' => 25]);

        $announcement = Announcement::create([
            'title' => 'Community update',
            'category' => 'updates',
            'body' => 'An update for residents.',
            'audiences' => ['public'],
        ]);

        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $superadmin->id,
            'title' => 'Updated User',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $actor->id,
            'auditable_type' => User::class,
            'auditable_id' => $target->id,
            'action' => 'updated',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $superadmin->id,
            'title' => 'Created Announcement',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $superadmin->id,
            'body' => 'Announcement "' . $announcement->title . '" was created by ' . $actor->full_name . '.',
        ]);
    }

    public function test_high_volume_game_and_points_records_are_audited_without_admin_notifications(): void
    {
        $superadmin = User::factory()->create(['role' => User::SUPERADMIN_ROLE]);
        $actor = User::factory()->create(['role' => 'official']);
        $resident = User::factory()->create(['role' => 'resident']);
        $event = Announcement::create([
            'title' => 'Community event',
            'category' => 'events',
            'body' => 'Event for attendance.',
            'is_event' => true,
            'event_start_at' => now()->subHour(),
            'event_end_at' => now()->addHour(),
            'qr_token' => 'admin-notification-test',
        ]);
        $notificationCount = UserNotification::count();

        $this->actingAs($actor);
        $records = [
            SpinHistory::create([
                'user_id' => $resident->id,
                'prize_label' => '10 points',
                'prize_type' => 'points',
                'amount' => 10,
            ]),
            GameRun::create([
                'user_id' => $resident->id,
                'game' => 'memory',
                'played_on' => today(),
                'stage_scores' => [10, 20],
                'total_score' => 30,
            ]),
            EventRaffleEntry::create([
                'announcement_id' => $event->id,
                'user_id' => $resident->id,
                'is_early' => false,
                'points_snapshot' => 10,
                'weight' => 1,
            ]),
            PointTransaction::create([
                'user_id' => $resident->id,
                'announcement_id' => $event->id,
                'type' => 'event_attendance',
                'description' => 'Event attendance',
                'base_points' => 10,
                'multiplier' => 1,
                'points_awarded' => 10,
            ]),
        ];

        $this->assertDatabaseCount('user_notifications', $notificationCount);
        foreach ($records as $record) {
            $this->assertDatabaseHas('audit_logs', [
                'auditable_type' => $record::class,
                'auditable_id' => $record->id,
                'action' => 'created',
            ]);
        }
        $this->assertSame(4, AuditLog::where('user_id', $actor->id)->count());
    }

    public function test_superadmin_notification_service_rejects_unsupported_data_change_actions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported data change action: restored');

        SuperadminNotificationService::notifyDataChange(new User(), 'restored');
    }
}
