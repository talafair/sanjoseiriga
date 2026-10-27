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

    public function test_announcement_banner_url_uses_the_configured_public_disk_url(): void
    {
        Config::set('filesystems.uploads_disk', 'public');
        Config::set('filesystems.disks.public.url', 'https://media.example.test/storage');
        Storage::forgetDisk('public');

        $announcement = new Announcement(['banner_path' => 'banners/event.png']);

        $this->assertSame(
            Storage::disk('public')->url('banners/event.png'),
            $announcement->banner_url
        );
        $this->assertNull((new Announcement(['banner_path' => null]))->banner_url);
    }

    public function test_badge_image_url_uses_the_configured_public_disk_url(): void
    {
        Config::set('filesystems.uploads_disk', 'public');
        Config::set('filesystems.disks.public.url', 'https://media.example.test/storage');
        Storage::forgetDisk('public');

        $badge = new Badge(['image_path' => 'badges/community.png']);

        $this->assertSame(
            Storage::disk('public')->url('badges/community.png'),
            $badge->image_url
        );
        $this->assertNull((new Badge(['image_path' => null]))->image_url);
    }

    public function test_badge_image_upload_uses_configured_disk_and_renders_disk_url(): void
    {
        Storage::fake('uploaded-media', ['url' => 'https://media.example.test']);
        Config::set('filesystems.uploads_disk', 'uploaded-media');
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => true,
        ]);

        $this->actingAs($official)
            ->post(route('badges.store'), [
                'name' => 'Community helper',
                'description' => 'Recognized for helping.',
                'points_required' => 0,
                'category' => 'special',
                'rarity' => 'rare',
                'award_method' => 'official',
                'condition_key' => 'manual',
                'image' => $this->pngUpload(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Badge added.');

        $badge = Badge::where('name', 'Community helper')->firstOrFail();
        $this->assertStringStartsWith('badges/', $badge->image_path);
        Storage::disk('uploaded-media')->assertExists($badge->image_path);
        $this->assertSame(
            Storage::disk('uploaded-media')->url($badge->image_path),
            $badge->image_url
        );

        $this->actingAs(User::factory()->create(['role' => 'resident', 'is_verified' => true]))
            ->get(route('badges.catalog'))
            ->assertOk()
            ->assertSee('src="' . $badge->image_url . '"', false);
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

        return UploadedFile::fake()->createWithContent('badge.png', $content);
    }
}
