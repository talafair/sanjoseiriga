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
            'email' => 'username-only@example.test',
            'contact_number' => null,
            'password' => 'Password123!',
        ]);

        $this->post(route('login.attempt'), [
            'login' => $user->username,
            'password' => 'Password123!',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
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
}