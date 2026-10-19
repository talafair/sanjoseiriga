<?php

namespace Tests\Feature;

use App\Models\Prize;
use App\Models\SpinHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestSpinAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_guest_can_access_and_spin_the_wheel(): void
    {
        $guest = User::factory()->create([
            'role' => 'guest',
            'is_verified' => true,
            'points' => 0,
        ]);
        $this->createWheelPrizes();

        $this->actingAs($guest)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('href="' . route('spin') . '"', false);

        $this->get(route('spin'))->assertOk();

        $this->postJson(route('spin.play'))
            ->assertOk()
            ->assertJsonStructure(['index', 'id', 'label', 'type', 'points', 'source', 'extraChances']);

        $this->assertDatabaseHas('spin_history', ['user_id' => $guest->id]);
    }

    public function test_unverified_guest_cannot_access_or_spin_the_wheel(): void
    {
        $guest = User::factory()->create([
            'role' => 'guest',
            'is_verified' => false,
        ]);
        $this->createWheelPrizes();

        $this->actingAs($guest)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Verify your guest account to access Spin the Wheel.');

        $this->get(route('spin'))
            ->assertRedirect(route('account'))
            ->assertSessionHas('error', 'Your guest account must be verified before you can access this feature.');

        $this->postJson(route('spin.play'))
            ->assertForbidden()
            ->assertJsonPath('message', 'Your guest account must be verified before you can access this feature.');

        $this->assertSame(0, SpinHistory::where('user_id', $guest->id)->count());
    }

    private function createWheelPrizes(): void
    {
        Prize::create([
            'label' => 'Points',
            'prize_type' => 'points',
            'amount' => 10,
            'color' => '#9ACD32',
        ]);
        Prize::create([
            'label' => 'Try again',
            'prize_type' => 'none',
            'amount' => 0,
            'color' => '#7CB342',
        ]);
    }
}
