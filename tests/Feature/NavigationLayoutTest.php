<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_sees_the_resident_navigation_without_official_management_items(): void
    {
        $resident = User::factory()->create([
            'role' => 'resident',
            'is_verified' => true,
        ]);

        $response = $this->actingAs($resident)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('id="mainNav"', false)
            ->assertSee('id="navToggle"', false)
            ->assertSee('aria-label="Close navigation"', false)
            ->assertSee('Home')
            ->assertSee('Announcements')
            ->assertSee('Spin the Wheel')
            ->assertSeeInOrder(['Appearance', 'Light Mode', 'Dark Mode', 'Switch Account', 'Logout'])
            ->assertSee('id="switchAccountPopover"', false)
            ->assertSee('id="switchAccountToggle"', false)
            ->assertSee('id="switchAccountAvatars"', false)
            ->assertSee('renderButtonAvatars', false)
            ->assertSee('const switchUrl = ', false)
            ->assertSee('action="' . route('saved-accounts.add') . '"', false)
            ->assertSee('action="' . route('logout') . '" method="POST"', false)
            ->assertSee('role="switch"', false)
            ->assertDontSee('Manage surveys');

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_official_sees_management_navigation(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'barangay_captain',
            'is_verified' => true,
        ]);

        $this->actingAs($official)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Manage')
            ->assertSee('Manage surveys')
            ->assertSee('System log')
            ->assertSeeInOrder(['Appearance', 'Light Mode', 'Dark Mode', 'Switch Account', 'Logout']);
    }

    public function test_unverified_official_sees_disabled_management_links_and_verification_reminder(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => false,
            'points' => 0,
        ]);

        $this->actingAs($official)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Your official account is unverified')
            ->assertSee('Verification is required to access official features.')
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('Account')
            ->assertSee('Switch Account')
            ->assertSee('Logout');

        $this->actingAs($official)
            ->get(route('account'))
            ->assertOk()
            ->assertSee('Unverified official');
    }

    public function test_superadmin_sees_management_navigation(): void
    {
        $superadmin = User::factory()->create(['role' => User::SUPERADMIN_ROLE]);

        $this->actingAs($superadmin)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Manage')
            ->assertSee('Manage surveys')
            ->assertSeeInOrder(['Appearance', 'Light Mode', 'Dark Mode', 'Switch Account', 'Logout']);
    }

    public function test_personnel_official_does_not_see_protected_management_navigation(): void
    {
        $personnel = User::factory()->create([
            'role' => 'official',
            'official_group' => 'personnel',
        ]);

        $this->actingAs($personnel)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('Manage surveys')
            ->assertDontSee('Manage</span>')
            ->assertSeeInOrder(['Appearance', 'Light Mode', 'Dark Mode', 'Switch Account', 'Logout']);
    }

    public function test_switch_account_popover_is_in_the_sidebar_and_uses_device_local_profiles(): void
    {
        $user = User::factory()->create(['role' => 'resident']);

        $response = $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('switchAccountPopover', false)
            ->assertSee('Add Account')
            ->assertSee('No accounts saved on this device yet.')
            ->assertSee('Switch Account')
            ->assertSee('switch-account-remove', false)
            ->assertSee('talafair.savedAccounts.v1')
            ->assertSee('saved_login')
            ->assertDontSee('saved-accounts.index');

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_switch_account_action_requires_authentication(): void
    {
        $this->post(route('switch-account'), ['saved_login' => 'some-account'])
            ->assertRedirect(route('login'));
    }

    public function test_login_responses_are_not_cached_by_the_service_worker(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('Pragma', 'no-cache')
            ->assertSee('Remember this account on this device')
            ->assertSee('id="togglePassword"', false)
            ->assertSee('id="signInButton"', false)
            ->assertSee('Signing in');

        $response = $this->get(route('login'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->get('/saved-accounts')->assertNotFound();
    }
}
