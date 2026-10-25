<?php

namespace Tests\Feature;

use App\Models\Prize;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrizeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_can_update_a_prize(): void
    {
        /** @var User $official */
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => true,
        ]);
        $prize = Prize::create([
            'label' => 'Old prize',
            'prize_type' => 'foods',
            'amount' => 0,
            'color' => '#9ACD32',
        ]);

        $this->actingAs($official)
            ->put(route('prizes.update', $prize), [
                'label' => 'Updated prize',
                'prize_type' => 'points',
                'amount' => 250,
                'color' => '#112233',
            ])
            ->assertRedirect(route('prizes.index'));

        $indexResponse = $this->get(route('prizes.index'));
        $this->assertStringContainsString('no-store', $indexResponse->headers->get('Cache-Control'));
        $indexResponse->assertSee('Updated prize')->assertSee('#112233');

        $this->assertDatabaseHas('prizes', [
            'id' => $prize->id,
            'label' => 'Updated prize',
            'prize_type' => 'points',
            'amount' => 250,
            'color' => '#112233',
        ]);
    }

    public function test_official_can_update_a_non_points_prize_without_an_amount(): void
    {
        /** @var User $official */
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => true,
        ]);
        $prize = Prize::create([
            'label' => 'Old prize',
            'prize_type' => 'points',
            'amount' => 250,
            'color' => '#9ACD32',
        ]);

        $this->actingAs($official)
            ->put(route('prizes.update', $prize), [
                'label' => 'Updated food prize',
                'prize_type' => 'foods',
                'color' => '#112233',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('prizes', [
            'id' => $prize->id,
            'label' => 'Updated food prize',
            'prize_type' => 'foods',
            'amount' => 0,
            'color' => '#112233',
        ]);
    }

    public function test_official_can_add_a_non_points_prize_without_an_amount(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => true,
        ]);

        $this->actingAs($official)
            ->post(route('prizes.store'), [
                'label' => 'Community meal',
                'prize_type' => 'foods',
                'color' => '#112233',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Prize added to the wheel.');

        $this->assertDatabaseHas('prizes', [
            'label' => 'Community meal',
            'prize_type' => 'foods',
            'amount' => 0,
            'color' => '#112233',
        ]);
    }

    public function test_official_cannot_delete_prizes_below_the_wheel_minimum(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => true,
        ]);
        $first = Prize::create([
            'label' => 'First',
            'prize_type' => 'none',
            'amount' => 0,
            'color' => '#112233',
        ]);
        Prize::create([
            'label' => 'Second',
            'prize_type' => 'none',
            'amount' => 0,
            'color' => '#223344',
        ]);

        $this->actingAs($official)
            ->delete(route('prizes.destroy', $first))
            ->assertRedirect()
            ->assertSessionHas('error', 'The wheel needs at least 2 prizes.');

        $this->assertSame(2, Prize::count());
    }

    public function test_official_cannot_add_more_than_the_wheel_maximum(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => true,
        ]);
        foreach (range(1, 12) as $number) {
            Prize::create([
                'label' => "Prize {$number}",
                'prize_type' => 'none',
                'amount' => 0,
                'color' => '#112233',
            ]);
        }

        $this->actingAs($official)
            ->from(route('prizes.index'))
            ->post(route('prizes.store'), [
                'label' => 'Thirteenth prize',
                'prize_type' => 'none',
                'color' => '#223344',
            ])
            ->assertRedirect(route('prizes.index'))
            ->assertSessionHas('error', 'The wheel can hold at most 12 prizes.');

        $this->assertSame(12, Prize::count());
    }
}
