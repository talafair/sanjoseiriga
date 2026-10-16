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

        $this->actingAs($resident)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('id="mainNav"', false)
            ->assertSee('id="navToggle"', false)
            ->assertSee('aria-label="Close navigation"', false)
            ->assertSee('Home')
            ->assertSee('Announcements')
            ->assertSee('Spin the Wheel')
            ->assertSeeInOrder(['Appearance', 'Light Mode', 'Dark Mode', 'Logout'])
            ->assertSee('role="switch"', false)
            ->assertDontSee('Manage surveys');
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
            ->assertSeeInOrder(['Appearance', 'Light Mode', 'Dark Mode', 'Logout']);
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
            ->assertSeeInOrder(['Appearance', 'Light Mode', 'Dark Mode', 'Logout']);
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
            ->assertSeeInOrder(['Appearance', 'Light Mode', 'Dark Mode', 'Logout']);
    }
}
