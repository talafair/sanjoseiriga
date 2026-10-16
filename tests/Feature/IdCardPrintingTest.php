<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdCardPrintingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_official_can_print_the_selected_residents_existing_id(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => true,
            'points' => 0,
        ]);
        $resident = User::factory()->create([
            'role' => 'resident',
            'first_name' => 'Print',
            'last_name' => 'Resident',
            'username' => 'selected-resident',
            'unique_id' => 'Z2-26123456789',
            'zone' => '2',
            'avatar_path' => null,
            'points' => 75,
        ]);

        $printResponse = $this->actingAs($official)
            ->get(route('users.id-card.print', $resident))
            ->assertOk()
            ->assertSee('Print TalaFair ID')
            ->assertSee('Print Resident')
            ->assertSee($resident->unique_id)
            ->assertSee('id="id-card"', false)
            ->assertSee('tala-id-qr', false)
            ->assertSee('<svg', false)
            ->assertSee('Download as PNG')
            ->assertSee('scale: 300 / 96', false)
            ->assertSee('85.60mm 53.98mm', false)
            ->assertSee('images/default-avatar.svg');

        $residentQr = $this->actingAs($resident)
            ->get(route('id-card.qr'))
            ->assertOk()
            ->assertHeader('content-type', 'image/svg+xml');

        $this->assertSame($residentQr->getContent(), $this->extractQrSvg($printResponse->getContent()));
        $this->assertDatabaseHas('users', [
            'id' => $resident->id,
            'unique_id' => 'Z2-26123456789',
            'points' => 75,
            'role' => 'resident',
        ]);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_superadmin_retains_authorized_resident_id_print_access(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::SUPERADMIN_ROLE,
            'is_verified' => false,
            'points' => 0,
        ]);
        $resident = User::factory()->create([
            'role' => 'resident',
            'unique_id' => 'Z4-26000000007',
            'points' => 0,
        ]);

        $this->actingAs($superadmin)
            ->get(route('users.id-card.print', $resident))
            ->assertOk()
            ->assertSee($resident->unique_id);
    }

    public function test_resident_cannot_open_another_residents_print_route(): void
    {
        $resident = User::factory()->create([
            'role' => 'resident',
            'is_verified' => true,
            'points' => 0,
        ]);
        $target = User::factory()->create([
            'role' => 'resident',
            'unique_id' => 'Z1-26987654321',
            'points' => 0,
        ]);

        $this->actingAs($resident)
            ->get(route('users.id-card.print', $target))
            ->assertRedirect(route('announcements'));
    }

    public function test_personnel_and_nonresident_targets_cannot_be_printed_by_this_route(): void
    {
        $personnel = User::factory()->create([
            'role' => 'official',
            'official_group' => 'personnel',
            'is_verified' => true,
            'points' => 0,
        ]);
        $resident = User::factory()->create([
            'role' => 'resident',
            'unique_id' => 'Z1-26000000001',
            'points' => 0,
        ]);
        $officialTarget = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'is_verified' => true,
            'unique_id' => 'Z1-26000000002',
            'points' => 0,
        ]);

        $this->actingAs($personnel)
            ->get(route('users.id-card.print', $resident))
            ->assertRedirect(route('announcements'));

        $barangayOfficial = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'barangay_captain',
            'is_verified' => true,
            'points' => 0,
        ]);

        $this->actingAs($barangayOfficial)
            ->get(route('users.id-card.print', $officialTarget))
            ->assertNotFound();
    }

    public function test_manage_users_links_print_action_only_for_residents(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => true,
            'points' => 0,
        ]);
        $resident = User::factory()->create([
            'role' => 'resident',
            'username' => 'print-action-resident',
            'unique_id' => 'Z1-26000000003',
            'points' => 0,
        ]);
        $otherOfficial = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'is_verified' => true,
            'points' => 0,
        ]);

        $this->actingAs($official)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee(route('users.id-card.print', $resident))
            ->assertDontSee(route('users.id-card.print', $otherOfficial));
    }

    private function extractQrSvg(string $html): string
    {
        preg_match('/<div class="tala-id-qr"[^>]*>(.*?)<\\/div>/s', $html, $matches);

        return $matches[1] ?? '';
    }

    public function test_authorized_official_can_print_selected_residents_on_a4_or_letter_sheets(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => true,
            'points' => 0,
        ]);
        $residents = collect([
            User::factory()->create([
                'role' => 'resident',
                'unique_id' => 'Z2-26000000011',
                'first_name' => 'Bulk One',
                'points' => 15,
            ]),
            User::factory()->create([
                'role' => 'resident',
                'unique_id' => 'Z2-26000000012',
                'first_name' => 'Bulk Two',
                'points' => 25,
            ]),
        ]);

        $response = $this->actingAs($official)
            ->post(route('users.id-cards.print-selected'), [
                'resident_ids' => $residents->pluck('id')->all(),
                'paper_size' => 'a4',
            ])
            ->assertOk()
            ->assertSee('A4 paper')
            ->assertSee('size: A4 portrait', false)
            ->assertSee('grid-template-columns: repeat(2, 85.6mm)', false)
            ->assertSee('page-break-inside: avoid', false)
            ->assertSee('Z2-26000000011')
            ->assertSee('Z2-26000000012');

        $this->assertSame(2, substr_count($response->getContent(), '<article id="id-card-'));
        $this->assertSame(2, substr_count($response->getContent(), 'class="tala-id-qr"'));
        foreach ($residents as $resident) {
            $this->assertDatabaseHas('users', [
                'id' => $resident->id,
                'unique_id' => $resident->unique_id,
                'points' => $resident->points,
            ]);
        }

        $this->actingAs($official)
            ->post(route('users.id-cards.print-selected'), [
                'resident_ids' => $residents->pluck('id')->all(),
                'paper_size' => 'letter',
            ])
            ->assertOk()
            ->assertSee('US Letter paper')
            ->assertSee('size: letter portrait', false);
    }

    public function test_bulk_print_rejects_nonresident_ids_and_unauthorized_users(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => true,
            'points' => 0,
        ]);
        $resident = User::factory()->create(['role' => 'resident', 'points' => 0]);
        $targetOfficial = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'is_verified' => true,
            'points' => 0,
        ]);

        $this->actingAs($official)
            ->from(route('users.index'))
            ->post(route('users.id-cards.print-selected'), [
                'resident_ids' => [$resident->id, $targetOfficial->id],
                'paper_size' => 'a4',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHasErrors('resident_ids.1');

        $otherResident = User::factory()->create(['role' => 'resident', 'points' => 0]);
        $this->actingAs($otherResident)
            ->post(route('users.id-cards.print-selected'), [
                'resident_ids' => [$resident->id],
                'paper_size' => 'a4',
            ])
            ->assertRedirect(route('announcements'));
    }

    public function test_bulk_print_splits_cards_into_nonbreaking_print_pages(): void
    {
        $official = User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'is_verified' => true,
            'points' => 0,
        ]);
        $residents = collect(range(1, 9))->map(fn (int $index) => User::factory()->create([
            'role' => 'resident',
            'unique_id' => 'Z3-' . str_pad((string) $index, 11, '0', STR_PAD_LEFT),
            'points' => 0,
        ]));

        $response = $this->actingAs($official)
            ->post(route('users.id-cards.print-selected'), [
                'resident_ids' => $residents->pluck('id')->all(),
                'paper_size' => 'a4',
            ])
            ->assertOk();

        $this->assertSame(2, substr_count($response->getContent(), 'class="bulk-id-sheet"'));
        $this->assertSame(9, substr_count($response->getContent(), '<article id="id-card-'));
        $this->assertSame(9, substr_count($response->getContent(), 'class="tala-id-qr"'));
    }
}
