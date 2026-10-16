<?php

namespace Tests\Feature;

use App\Models\User;
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

    public function test_public_registration_rejects_forged_official_and_superadmin_roles(): void
    {
        foreach ([
            'official' => [
                'official_group' => 'barangay_council',
                'official_position' => 'kagawad',
            ],
            'superadmin' => [],
        ] as $role => $extra) {
            $username = "forged-{$role}";

            $this->from(route('register'))
                ->post(route('register.store'), $this->registrationPayload([
                    ...$extra,
                    'role' => $role,
                    'username' => $username,
                ]))
                ->assertRedirect(route('register'))
                ->assertSessionHasErrors('role');

            $this->assertGuest();
            $this->assertDatabaseMissing('users', ['username' => $username]);
        }
    }

    public function test_public_registration_form_does_not_offer_official_role(): void
    {
        $this->get(route('register', ['role' => 'official']))
            ->assertNotFound();

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Official</a>', false);
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