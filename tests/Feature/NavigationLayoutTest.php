<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AuditLog;
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

    public function test_unverified_guest_sees_disabled_links_for_unavailable_features(): void
    {
        $guest = User::factory()->create([
            'role' => 'guest',
            'is_verified' => false,
        ]);

        $this->actingAs($guest)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('aria-disabled="true" title="Badges are not available to guest accounts."', false)
            ->assertSee('aria-disabled="true" title="Games are not available to guest accounts."', false)
            ->assertSee('aria-disabled="true" title="Surveys are not available to guest accounts."', false)
            ->assertDontSee('href="' . route('badges.catalog') . '"', false)
            ->assertDontSee('href="' . route('games.index') . '"', false)
            ->assertDontSee('href="' . route('surveys.index') . '"', false);

        foreach (['badges.catalog', 'games.index', 'surveys.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('account'));
        }
    }

    public function test_unverified_resident_and_official_see_disabled_links_and_cannot_open_restricted_pages(): void
    {
        foreach (['resident', 'official'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'official_group' => $role === 'official' ? 'barangay_council' : null,
                'official_position' => $role === 'official' ? 'kagawad' : null,
                'is_verified' => false,
            ]);

            $this->actingAs($user)
                ->get(route('home'))
                ->assertOk()
                ->assertSee('aria-disabled="true" title="Verify your account to access badges."', false)
                ->assertSee('aria-disabled="true" title="Verify your account to access games."', false)
                ->assertSee('aria-disabled="true" title="Verify your account to access surveys."', false)
                ->assertDontSee('href="' . route('badges.catalog') . '"', false)
                ->assertDontSee('href="' . route('games.index') . '"', false)
                ->assertDontSee('href="' . route('surveys.index') . '"', false);

            foreach (['badges.catalog', 'games.index', 'surveys.index'] as $route) {
                $this->get(route($route))->assertRedirect(route('account'));
            }
        }
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

    public function test_system_log_uses_bootstrap_pagination_and_preserves_filters_across_pages(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'barangay_captain',
            'is_verified' => true,
        ]);

        foreach (range(1, 26) as $index) {
            AuditLog::create([
                'user_id' => $official->id,
                'actor_unique_id' => $official->unique_id,
                'action' => 'login',
                'auditable_type' => User::class,
                'auditable_id' => $official->id,
                'new_values' => ['index' => $index],
            ]);
        }

        $this->actingAs($official)
            ->get(route('audit-logs.index', [
                'user_name' => $official->name,
                'type' => 'User',
                'action' => 'login',
            ]))
            ->assertOk()
            ->assertSee('aria-label="System log pagination"', false)
            ->assertSee('class="pagination"', false)
            ->assertSee('Showing')
            ->assertSee('type=User')
            ->assertSee('action=login')
            ->assertSee('page=2');

        $this->get(route('audit-logs.index', [
            'user_name' => $official->name,
            'type' => 'User',
            'action' => 'login',
            'page' => 2,
        ]))
            ->assertOk()
            ->assertSee('aria-label="System log pagination"', false)
            ->assertSee('rel="prev"', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_system_log_can_be_filtered_by_date_and_navigated_by_day(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'barangay_captain',
            'is_verified' => true,
        ]);

        foreach ([now()->subDays(2), now()->subDay(), now()] as $dayIndex => $day) {
            $entries = $dayIndex === 1 ? 26 : 1;
            for ($index = 0; $index < $entries; $index++) {
                $log = AuditLog::create([
                    'user_id' => $official->id,
                    'actor_unique_id' => $official->unique_id,
                    'action' => 'login',
                    'auditable_type' => User::class,
                    'auditable_id' => $official->id,
                    'new_values' => ['index' => $index],
                ]);
                $log->forceFill(['created_at' => $day->copy()->setTime(10, 0)->addSeconds($index)])->saveQuietly();
            }
        }

        $date = now()->subDay()->toDateString();
        $this->actingAs($official)
            ->get(route('audit-logs.index', ['date' => $date, 'action' => 'login']))
            ->assertOk()
            ->assertSee('name="date"', false)
            ->assertSee('value="' . $date . '"', false)
            ->assertDontSee('Yesterday')
            ->assertDontSee('Today')
            ->assertSee('action=login')
            ->assertSee('26 entries');

        $this->actingAs($official)
            ->get(route('audit-logs.index', ['date' => 'not-a-date']))
            ->assertRedirect();
    }
}
