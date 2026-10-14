<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Badge;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadedImageStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_image_models_generate_urls_from_the_configured_upload_disk(): void
    {
        Storage::fake('uploaded-media', ['url' => 'https://media.example.test']);
        Config::set('filesystems.uploads_disk', 'uploaded-media');

        $expectedAvatarUrl = Storage::disk('uploaded-media')->url('avatars/person.png');
        $user = User::factory()->create(['avatar_path' => 'avatars/person.png']);
        $announcement = new Announcement(['banner_path' => 'banners/event.png']);
        $badge = new Badge(['image_path' => 'badges/community.png']);
        Setting::create(['key' => 'home_background', 'value' => 'backgrounds/home.png']);

        $this->assertSame($expectedAvatarUrl, $user->avatar_url);
        $this->assertSame(Storage::disk('uploaded-media')->url('banners/event.png'), $announcement->banner_url);
        $this->assertSame(Storage::disk('uploaded-media')->url('badges/community.png'), $badge->image_url);
        $this->assertSame(Storage::disk('uploaded-media')->url('backgrounds/home.png'), Setting::url('home_background'));
    }

    public function test_profile_picture_replacement_and_removal_use_the_configured_upload_disk(): void
    {
        Storage::fake('uploaded-media');
        Config::set('filesystems.uploads_disk', 'uploaded-media');
        $user = User::factory()->create();
        Storage::disk('uploaded-media')->put('avatars/old.png', 'existing-image');
        $user->update(['avatar_path' => 'avatars/old.png']);

        $this->actingAs($user)
            ->post(route('profile.photo.update'), ['avatar' => $this->pngUpload()])
            ->assertRedirect();

        $newPath = $user->fresh()->avatar_path;
        $this->assertNotSame('avatars/old.png', $newPath);
        Storage::disk('uploaded-media')->assertExists($newPath);
        Storage::disk('uploaded-media')->assertMissing('avatars/old.png');

        $this->actingAs($user)
            ->delete(route('profile.photo.destroy'))
            ->assertRedirect();

        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('uploaded-media')->assertMissing($newPath);
    }

    private function pngUpload(): UploadedFile
    {
        $content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/g2kAAAAASUVORK5CYII=', true);

        return UploadedFile::fake()->createWithContent('profile.png', $content);
    }
}
