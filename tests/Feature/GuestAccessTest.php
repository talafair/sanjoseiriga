<?php

namespace Tests\Feature;

use App\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_only_access_the_public_home_announcement_leaderboard_and_account_pages(): void
    {
        $publicAnnouncement = Announcement::create([
            'title' => 'Public community update',
            'category' => 'updates',
            'body' => 'Details for everyone.',
            'audiences' => ['public'],
        ]);
        $privateAnnouncement = Announcement::create([
            'title' => 'Officials-only update',
            'category' => 'updates',
            'body' => 'Private details.',
            'audiences' => ['officials'],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Public community update')
            ->assertDontSee('Officials-only update')
            ->assertSee('aria-disabled="true" title="Sign in to access badges."', false)
            ->assertSee('aria-disabled="true" title="Sign in to access games."', false)
            ->assertSee('aria-disabled="true" title="Sign in to access surveys."', false)
            ->assertSee('aria-disabled="true" title="Sign in to access badges.">View all', false);

        $this->get(route('announcements'))
            ->assertOk()
            ->assertSee('Public community update')
            ->assertDontSee('Officials-only update');
        $this->get(route('announcements.show', $publicAnnouncement))->assertOk();
        $this->get(route('announcements.show', $privateAnnouncement))->assertNotFound();
        $this->get(route('leaderboard'))->assertOk();
        $this->get(route('account'))->assertOk()->assertSee('Create an account');

        $this->get(route('badges.catalog'))->assertRedirect(route('login'));
        $this->get(route('games.index'))->assertRedirect(route('login'));
        $this->get(route('surveys.index'))->assertRedirect(route('login'));
    }
}
