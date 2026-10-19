<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_with_email_or_phone_number(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.test',
            'contact_number' => '(0917) 123-4567',
            'password' => 'Password123!',
        ]);

        foreach (['login@example.test', '09171234567', '+639171234567', '639171234567'] as $login) {
            $this->post(route('login.attempt'), [
                'login' => $login,
                'password' => 'Password123!',
            ])->assertRedirect(route('home'));

            $this->assertAuthenticatedAs($user);
            auth()->logout();
        }
    }

    public function test_user_can_log_in_with_username_without_email_or_phone_number(): void
    {
        $user = User::factory()->create([
            'username' => 'username-only',
            'email' => null,
            'contact_number' => null,
            'password' => 'Password123!',
        ]);

        $this->post(route('login.attempt'), [
            'login' => $user->username,
            'password' => 'Password123!',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login.attempt'), [
                'login' => 'unknown-user',
                'password' => 'WrongPassword123!',
            ])->assertSessionHasErrors('login');
        }

        $this->post(route('login.attempt'), [
            'login' => 'unknown-user',
            'password' => 'WrongPassword123!',
        ])->assertTooManyRequests();
    }

    public function test_switch_account_logs_out_invalidates_the_session_and_protects_authenticated_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->withSession([
            'switch-account-marker' => 'must be removed',
        ]);

        $sessionId = session()->getId();
        $csrfToken = session()->token();

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertFalse(session()->has('switch-account-marker'));
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame($csrfToken, session()->token());

        $this->get(route('home'))->assertOk();
        $this->get(route('account'))->assertOk()->assertSee('Create an account');
    }

    public function test_login_and_logout_do_not_create_superadmin_data_change_notifications(): void
    {
        $superadmin = User::factory()->create(['role' => User::SUPERADMIN_ROLE]);
        $user = User::factory()->create([
            'role' => 'resident',
            'password' => 'Password123!',
        ]);
        $notificationCount = UserNotification::where('user_id', $superadmin->id)->count();

        $this->post(route('login.attempt'), [
            'login' => $user->username,
            'password' => 'Password123!',
            'remember' => '1',
        ])->assertRedirect(route('home'));

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertSame(
            $notificationCount,
            UserNotification::where('user_id', $superadmin->id)->count()
        );
    }

    public function test_successful_login_can_save_only_a_display_profile_when_requested(): void
    {
        $user = User::factory()->create([
            'role' => 'resident',
            'password' => 'Password123!',
        ]);

        $this->post(route('login.attempt'), [
            'login' => $user->username,
            'password' => 'Password123!',
            'save_account' => '1',
        ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('savedAccountProfile', [
                'username' => $user->username,
                'name' => $user->full_name,
                'avatar' => $user->avatar_url,
                'role' => 'Resident',
            ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('talafair.savedAccounts.v1')
            ->assertSee('localStorage.setItem');
    }

    public function test_saved_account_can_be_added_during_registration_only_when_opted_in(): void
    {
        $this->post(route('register.store'), $this->registrationPayload([
            'save_account' => '1',
        ]))->assertRedirect(route('id-card.show'));

        $user = User::where('username', 'registered-user')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame([
            'username' => $user->username,
            'name' => $user->full_name,
            'avatar' => $user->avatar_url,
            'role' => 'Resident',
        ], session('savedAccountProfile'));
    }

    public function test_add_account_logs_out_and_offers_to_save_the_next_profile(): void
    {
        $user = User::factory()->create(['role' => 'resident']);

        $this->actingAs($user)->withSession(['private-marker' => 'discard'])
            ->post(route('saved-accounts.add'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('save_account_after_login', true);

        $this->assertGuest();
        $this->assertFalse(session()->has('private-marker'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="save_account"', false)
            ->assertSee('value="1" id="saveAccount"', false)
            ->assertSee('checked', false);
    }

    public function test_selecting_a_saved_account_logs_out_before_requiring_its_password(): void
    {
        $current = User::factory()->create([
            'role' => 'resident',
            'username' => 'current-resident',
            'password' => 'CurrentPassword123!',
        ]);
        $saved = User::factory()->create([
            'role' => 'resident',
            'username' => 'saved-resident',
            'password' => 'SavedPassword123!',
        ]);

        $this->actingAs($current)->withSession(['private-marker' => 'discard']);
        $oldSessionId = session()->getId();

        $this->post(route('switch-account'), [
            'saved_login' => $saved->username,
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('pending_saved_login', $saved->username);

        $this->assertGuest();
        $this->assertFalse(session()->has('private-marker'));
        $this->assertNotSame($oldSessionId, session()->getId());

        $loggedOutSessionId = session()->getId();
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('id="loginField"', false)
            ->assertSee('value="' . $saved->username . '"', false)
            ->assertSee('readonly', false);

        $this->post(route('login.attempt'), [
            'login' => $saved->username,
            'password' => 'CurrentPassword123!',
        ])->assertSessionHasErrors('login');
        $this->assertGuest();

        $this->post(route('login.attempt'), [
            'login' => $saved->username,
            'password' => 'SavedPassword123!',
        ])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($saved);
        $this->assertNotSame($loggedOutSessionId, session()->getId());
    }

    public function test_forged_saved_username_cannot_authenticate_a_different_account(): void
    {
        $current = User::factory()->create(['role' => 'resident']);
        $other = User::factory()->create([
            'role' => 'resident',
            'username' => 'real-account',
            'password' => 'RealPassword123!',
        ]);

        $this->actingAs($current)
            ->post(route('switch-account'), ['saved_login' => 'unknown-saved-account'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('pending_saved_login', '');

        $this->assertGuest();
        $this->post(route('login.attempt'), [
            'login' => $other->username,
            'password' => 'RealPassword123!',
        ])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_resident_credentials_cannot_switch_into_an_official_or_administrator_account(): void
    {
        $resident = User::factory()->create([
            'role' => 'resident',
            'username' => 'switch-resident',
            'password' => 'ResidentPassword123!',
        ]);
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'username' => 'switch-official',
            'password' => 'OfficialPassword123!',
            'is_verified' => true,
        ]);
        $administrator = User::factory()->create([
            'role' => User::SUPERADMIN_ROLE,
            'username' => 'switch-administrator',
            'password' => 'AdministratorPassword123!',
        ]);

        $this->actingAs($resident)
            ->post(route('switch-account'), ['saved_login' => $official->username])
            ->assertRedirect(route('login'));

        $this->post(route('login.attempt'), [
            'login' => $resident->username,
            'password' => 'ResidentPassword123!',
        ])->assertSessionHasErrors('login');
        $this->assertGuest();

        $this->get(route('login', ['another' => 1]));
        $this->actingAs($resident)
            ->post(route('switch-account'), ['saved_login' => $administrator->username])
            ->assertRedirect(route('login'));

        $this->post(route('login.attempt'), [
            'login' => $resident->username,
            'password' => 'ResidentPassword123!',
        ])->assertSessionHasErrors('login');
        $this->assertGuest();

        $this->post(route('login.attempt'), [
            'login' => $administrator->username,
            'password' => 'AdministratorPassword123!',
        ])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($administrator);
    }

    public function test_switching_to_an_unverified_official_does_not_bypass_verification(): void
    {
        $resident = User::factory()->create(['role' => 'resident']);
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'username' => 'unverified-switch-target',
            'password' => 'OfficialPassword123!',
            'is_verified' => false,
        ]);

        $this->actingAs($resident)
            ->post(route('switch-account'), ['saved_login' => $official->username])
            ->assertRedirect(route('login'));

        $this->post(route('login.attempt'), [
            'login' => $official->username,
            'password' => 'OfficialPassword123!',
        ])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($official);

        $this->get(route('prizes.index'))->assertRedirect(route('account'));
    }

    public function test_registration_accepts_a_valid_email(): void
    {
        $this->post(route('register.store'), $this->registrationPayload([
            'email' => 'new-user@example.test',
        ]))->assertRedirect(route('id-card.show'));

        $this->assertDatabaseHas('users', [
            'username' => 'registered-user',
            'email' => 'new-user@example.test',
        ]);
    }

    public function test_registration_succeeds_without_email_and_username_login_still_works(): void
    {
        $this->post(route('register.store'), $this->registrationPayload([
            'email' => '',
        ]))
            ->assertRedirect(route('id-card.show'));

        $user = User::where('username', 'registered-user')->firstOrFail();
        $this->assertNull($user->email);

        auth()->logout();
        $this->post(route('login.attempt'), [
            'login' => $user->username,
            'password' => 'Password123!',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_public_registration_rejects_forged_superadmin_role(): void
    {
        $this->from(route('register'))
            ->post(route('register.store'), $this->registrationPayload([
                'role' => 'superadmin',
                'username' => 'forged-superadmin',
            ]))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('role');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['username' => 'forged-superadmin']);
    }

    public function test_login_and_registration_offer_official_signup(): void
    {
        $this->get(route('register', ['role' => 'official']))
            ->assertOk()
            ->assertSee('Official Registration')
            ->assertSee('name="official_group"', false)
            ->assertSee('name="official_position"', false)
            ->assertSee('must be verified by a Barangay Administrator');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('register', ['role' => 'official']), false)
            ->assertSee('Official');
    }

    public function test_official_registration_creates_an_unverified_official_account(): void
    {
        $payload = $this->registrationPayload([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'username' => 'new-official',
        ]);
        unset($payload['is_head_of_family']);

        $this->post(route('register.store'), $payload)
            ->assertRedirect(route('id-card.show'));

        $official = User::where('username', 'new-official')->firstOrFail();
        $this->assertAuthenticatedAs($official);
        $this->assertDatabaseHas('users', [
            'id' => $official->id,
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => false,
        ]);
    }

    public function test_official_registration_rejects_a_position_from_another_group(): void
    {
        $this->from(route('register', ['role' => 'official']))
            ->post(route('register.store'), $this->registrationPayload([
                'role' => 'official',
                'official_group' => 'barangay_council',
                'official_position' => 'sk_chairman',
                'username' => 'invalid-official',
            ]))
            ->assertRedirect(route('register', ['role' => 'official']))
            ->assertSessionHasErrors('official_position');

        $this->assertDatabaseMissing('users', ['username' => 'invalid-official']);
    }

    public function test_guest_registration_uses_a_separate_zone_based_id_without_household_fields(): void
    {
        $this->get(route('register', ['role' => 'guest']))
            ->assertOk()
            ->assertSee('Zone number')
            ->assertSee('starts with GZ')
            ->assertDontSee('>Household</h6>', false)
            ->assertDontSee('name="head_of_family_id"', false)
            ->assertDontSee('name="is_head_of_family"', false);

        $payload = $this->registrationPayload([
            'role' => 'guest',
            'birthdate' => '',
            'zone' => '2',
            'barangay' => 'Guest Village',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'country' => 'Philippines',
            'postal_code' => '4431',
            'username' => 'registered-guest',
        ]);
        unset($payload['is_head_of_family']);

        $this->post(route('register.store'), $payload)
            ->assertRedirect(route('id-card.show'));

        $guest = User::where('username', 'registered-guest')->firstOrFail();
        $this->assertAuthenticatedAs($guest);
        $this->assertStringStartsWith('GZ2-', $guest->unique_id);
        $this->assertFalse($guest->is_head_of_family);
        $this->assertNull($guest->head_of_family_id);
        $this->assertNull($guest->head_of_family_name);
    }

    public function test_registration_rejects_an_invalid_email(): void
    {
        $this->from(route('register'))
            ->post(route('register.store'), $this->registrationPayload([
                'email' => 'not-an-email',
            ]))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['username' => 'registered-user']);
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);

        $this->from(route('register'))
            ->post(route('register.store'), $this->registrationPayload([
                'email' => 'taken@example.test',
            ]))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['username' => 'registered-user']);
    }

    public function test_user_can_request_and_complete_a_password_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.test']);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $this->post(route('password.reset.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_password_reset_request_for_an_email_less_account_fails_safely(): void
    {
        Notification::fake();
        User::factory()->create([
            'username' => 'no-email-user',
            'email' => null,
        ]);

        $this->post(route('password.email'), ['email' => 'no-email-user@example.test'])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_official_can_update_profile_without_an_email(): void
    {
        $user = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'email' => 'official-profile@example.test',
        ]);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => 'Profile',
                'last_name' => 'Official',
                'sex_at_birth' => 'female',
                'preferred_gender_identity' => 'woman',
                'birthdate' => '1990-01-01',
                'house_no' => '10',
                'street' => 'Main Street',
                'zone' => '1',
                'email' => '',
                'official_group' => 'barangay_council',
                'official_position' => 'kagawad',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => null,
        ]);
    }

    public function test_registration_form_marks_email_as_optional(): void
    {
        $this->get(route('register', ['role' => 'resident']))
            ->assertOk()
            ->assertSee('Email <span class="text-secondary fw-normal">(optional)</span>', false)
            ->assertDontSee('id="email" name="email" type="email" required', false);
    }

    private function registrationPayload(array $overrides = []): array
    {
        return array_replace([
            'role' => 'resident',
            'first_name' => 'Registered',
            'last_name' => 'User',
            'sex_at_birth' => 'female',
            'preferred_gender_identity' => 'woman',
            'birthdate' => '1990-01-01',
            'is_student' => '0',
            'is_out_of_school_youth' => '0',
            'is_pwd' => '0',
            'is_4ps_member' => '0',
            'is_solo_parent' => '0',
            'is_sjtoda' => '0',
            'is_bpc' => '0',
            'is_head_of_family' => '1',
            'house_no' => '10',
            'street' => 'Main Street',
            'zone' => '1',
            'username' => 'registered-user',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ], $overrides);
    }
}