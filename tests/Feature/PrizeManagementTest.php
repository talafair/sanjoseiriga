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
}
