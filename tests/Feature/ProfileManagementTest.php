<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    public function test_user_can_change_password_with_the_current_password(): void
    {
        $user = User::factory()->create(['password' => 'CurrentPassword123!']);

        $this->actingAs($user)
            ->put(route('password.update'), [
                'current_password' => 'CurrentPassword123!',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'title' => 'Password changed',
        ]);
    }

    public function test_password_change_rejects_an_incorrect_current_password(): void
    {
        $user = User::factory()->create(['password' => 'CurrentPassword123!']);

        $this->actingAs($user)
            ->from(route('account'))
            ->put(route('password.update'), [
                'current_password' => 'WrongPassword123!',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertRedirect(route('account'))
            ->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->assertTrue(Hash::check('CurrentPassword123!', $user->fresh()->password));
        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $user->id,
            'title' => 'Password changed',
        ]);
    }

    public function test_resident_cannot_edit_profile_details(): void
    {
        $resident = User::factory()->create([
            'role' => 'resident',
            'first_name' => 'Original',
        ]);

        $this->actingAs($resident)
            ->patch(route('profile.update'), [])
            ->assertForbidden();

        $this->assertSame('Original', $resident->fresh()->first_name);
    }

    public function test_profile_photo_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['avatar_path' => null]);

        $this->actingAs($user)
            ->from(route('account'))
            ->post(route('profile.photo.update'), [
                'avatar' => UploadedFile::fake()->create('profile.txt', 1, 'text/plain'),
            ])
            ->assertRedirect(route('account'))
            ->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->avatar_path);
        $this->assertSame(0, UserNotification::where('user_id', $user->id)->count());
        Storage::disk('public')->assertDirectoryEmpty('avatars');
    }
}
