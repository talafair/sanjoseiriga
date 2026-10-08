<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
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

    public function test_official_audience_notifications_include_superadmins_but_not_personnel(): void
    {
        $superadmin = User::factory()->create(['role' => User::SUPERADMIN_ROLE]);
        $official = User::factory()->create(['role' => 'official', 'official_group' => 'barangay_council']);
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

    public function test_superadmins_receive_notifications_for_system_data_changes(): void
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

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $superadmin->id,
            'title' => 'Updated User',
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
}
