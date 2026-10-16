<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficialVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_official_can_log_in_and_reach_account_but_not_official_features(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => false,
            'points' => 0,
        ]);

        $this->post(route('login.attempt'), [
            'login' => $official->username,
            'password' => 'password',
        ])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($official);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Your official account is unverified');

        $this->get(route('account'))
            ->assertOk()
            ->assertSee('Unverified official');

        $this->get(route('prizes.index'))
            ->assertRedirect(route('account'))
            ->assertSessionHas('error');

        $this->get(route('attendance.history'))
            ->assertRedirect(route('account'))
            ->assertSessionHas('error');

        $this->get(route('spin'))
            ->assertRedirect(route('account'))
            ->assertSessionHas('error');
    }

    public function test_unverified_official_cannot_submit_official_actions_or_record_attendance(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => false,
            'points' => 0,
        ]);
        $resident = User::factory()->create([
            'role' => 'resident',
            'is_verified' => false,
        ]);

        $this->actingAs($official)
            ->post(route('prizes.store'), [])
            ->assertRedirect(route('account'));

        $this->actingAs($official)
            ->patch(route('users.verification.toggle', $official))
            ->assertRedirect(route('account'));

        $this->assertDatabaseHas('users', [
            'id' => $official->id,
            'is_verified' => false,
        ]);

        $this->actingAs($official)
            ->postJson(route('attendance.check'), [
                'token' => 'forged-event-token',
                'latitude' => 13.426208,
                'longitude' => 123.398747,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('message', 'Your account must be verified before attendance can be recorded.');

        $this->assertDatabaseHas('users', [
            'id' => $resident->id,
            'is_verified' => false,
        ]);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_verified_officials_and_superadmins_keep_official_access(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => true,
            'points' => 0,
        ]);
        $superadmin = User::factory()->create([
            'role' => User::SUPERADMIN_ROLE,
            'is_verified' => false,
            'points' => 0,
        ]);

        $this->actingAs($official)
            ->get(route('prizes.index'))
            ->assertOk();

        $this->actingAs($official)
            ->get(route('spin'))
            ->assertOk();

        $this->actingAs($superadmin)
            ->get(route('prizes.index'))
            ->assertOk();
    }

    public function test_verified_residents_keep_verified_only_access(): void
    {
        $resident = User::factory()->create([
            'role' => 'resident',
            'is_verified' => true,
            'points' => 0,
        ]);

        $this->actingAs($resident)
            ->get(route('spin'))
            ->assertOk();
    }

    public function test_unverified_residents_can_log_in_but_get_reminders_and_cannot_use_verified_features(): void
    {
        $resident = User::factory()->create([
            'role' => 'resident',
            'is_verified' => false,
            'points' => 0,
        ]);

        $this->post(route('login.attempt'), [
            'login' => $resident->username,
            'password' => 'password',
        ])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($resident);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Your resident account is unverified')
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('Spin the Wheel')
            ->assertSee('A Barangay Official must verify your resident account');

        $this->get(route('account'))
            ->assertOk()
            ->assertSee('Unverified resident');

        $this->patch(route('users.verification.toggle', $resident))
            ->assertRedirect(route('announcements'));
        $this->assertDatabaseHas('users', [
            'id' => $resident->id,
            'is_verified' => false,
        ]);

        $this->get(route('attendance.scanner'))
            ->assertRedirect(route('account'))
            ->assertSessionHas('error');

        $this->get(route('attendance.history'))
            ->assertRedirect(route('account'))
            ->assertSessionHas('error');

        $this->get(route('spin'))
            ->assertRedirect(route('account'))
            ->assertSessionHas('error');

        $this->postJson(route('attendance.check'), [
            'token' => 'forged-event-token',
            'latitude' => 13.426208,
            'longitude' => 123.398747,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Your resident account must be verified by a Barangay Official before attendance can be recorded.');

        $this->get(route('announcements'))->assertOk();
        $this->get(route('notifications.index'))->assertOk();
    }
}
