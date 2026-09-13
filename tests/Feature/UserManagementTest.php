<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_user_accepts_each_registration_category_and_saves_it(): void
    {
        $official = $this->managementSuperadmin();

        foreach (array_keys(User::CATEGORIES) as $index => $category) {
            $user = $this->actingAs($official)->post(route('users.store'), $this->userPayload($category, $index));

            $user->assertRedirect(route('users.index'));
            $this->assertDatabaseHas('users', [
                'username' => "managed-{$category}-{$index}",
                'role' => $category,
            ]);
        }
    }

    public function test_add_user_rejects_an_invalid_category(): void
    {
        $official = $this->managementOfficial();

        $this->actingAs($official)
            ->from(route('users.create'))
            ->post(route('users.store'), $this->userPayload('not-a-category'))
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['username' => 'managed-not-a-category']);
    }

    public function test_edit_user_preserves_existing_data_and_updates_category(): void
    {
        $official = $this->managementSuperadmin();
        $user = User::factory()->create([
            'role' => 'resident',
            'email' => 'keep-this@example.test',
            'username' => 'keep-this-user',
        ]);

        $response = $this->actingAs($official)
            ->get(route('users.edit', $user))
            ->assertOk();

        $response->assertSee('value="guest"', false)
            ->assertSee('Category', false);
        $this->assertSame(1, substr_count($response->getContent(), 'id="role"'));

        $payload = $this->userPayload('guest', 20);
        $payload['email'] = $user->email;
        $payload['username'] = $user->username;
        $payload['first_name'] = 'Updated';

        $this->actingAs($official)
            ->put(route('users.update', $user), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'guest',
            'email' => 'keep-this@example.test',
            'username' => 'keep-this-user',
            'first_name' => 'Updated',
        ]);
    }

    public function test_registration_uses_the_same_category_options(): void
    {
        $this->get(route('register', ['role' => 'resident']))
            ->assertOk()
            ->assertSee('value="resident"', false)
            ->assertSee('name="role"', false);

        $this->assertSame(['resident', 'guest', 'official'], array_keys(User::CATEGORIES));
    }

    public function test_registration_accepts_a_student_with_out_of_school_youth_unchecked(): void
    {
        $this->post(route('register.store'), [
            'role' => 'resident',
            'first_name' => 'Student',
            'last_name' => 'Account',
            'gender' => 'female',
            'birthdate' => '2008-01-01',
            'is_student' => '1',
            'is_out_of_school_youth' => '0',
            'student_level' => 'senior_high_school',
            'school' => 'Test High School',
            'is_head_of_family' => '1',
            'house_no' => '10',
            'street' => 'Main Street',
            'zone' => '1',
            'username' => 'student-account',
            'email' => 'student-account@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('id-card.show'));

        $this->assertDatabaseHas('users', [
            'username' => 'student-account',
            'is_student' => true,
            'is_out_of_school_youth' => false,
            'student_level' => 'senior_high_school',
        ]);
    }

    public function test_official_cannot_manage_an_official_or_change_roles(): void
    {
        $official = $this->managementOfficial();
        $otherOfficial = $this->managementOfficial();
        $resident = User::factory()->create(['role' => 'resident']);

        $this->actingAs($official)->delete(route('users.destroy', $otherOfficial))->assertForbidden();
        $this->actingAs($official)->patch(route('users.verification.toggle', $otherOfficial))->assertForbidden();
        $this->actingAs($official)->put(route('users.update', $resident), $this->userPayload('official'))
            ->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $resident->id, 'role' => 'resident']);
        $this->assertDatabaseHas('users', ['id' => $otherOfficial->id, 'role' => 'official']);
    }

    public function test_official_can_manage_residents_but_cannot_delete_superadmin(): void
    {
        $official = $this->managementOfficial();
        $resident = User::factory()->create(['role' => 'resident', 'is_verified' => false]);
        $superadmin = $this->managementSuperadmin();

        $this->actingAs($official)->patch(route('users.verification.toggle', $resident))->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $resident->id, 'is_verified' => true]);
        $this->actingAs($official)->delete(route('users.destroy', $superadmin))->assertForbidden();
    }

    public function test_superadmin_can_verify_and_unverify_an_official(): void
    {
        $superadmin = $this->managementSuperadmin();
        $official = $this->managementOfficial();

        $this->actingAs($superadmin)
            ->patch(route('users.verification.toggle', $official))
            ->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $official->id, 'is_verified' => true]);

        $this->actingAs($superadmin)
            ->patch(route('users.verification.toggle', $official))
            ->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $official->id, 'is_verified' => false]);
    }

    public function test_superadmin_cannot_be_deleted_or_demoted(): void
    {
        $superadmin = $this->managementSuperadmin();

        $this->actingAs($superadmin)->delete(route('users.destroy', $superadmin))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'official']))
            ->patch(route('users.verification.toggle', $superadmin))
            ->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $superadmin->id, 'role' => User::SUPERADMIN_ROLE]);
    }

    public function test_resident_cannot_access_user_management_actions(): void
    {
        $resident = User::factory()->create(['role' => 'resident']);
        $target = User::factory()->create(['role' => 'resident']);

        $this->actingAs($resident)->get(route('users.index'))->assertRedirect(route('announcements'));
        $this->actingAs($resident)->put(route('users.update', $target), $this->userPayload('official'))
            ->assertRedirect(route('announcements'));
    }

    private function managementSuperadmin(): User
    {
        return User::factory()->create(['role' => User::SUPERADMIN_ROLE]);
    }

    private function managementOfficial(): User
    {
        return User::factory()->create([
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
        ]);
    }

    private function userPayload(string $category, int $index = 0): array
    {
        return [
            'role' => $category,
            'official_group' => $category === 'official' ? 'barangay_council' : '',
            'official_position' => $category === 'official' ? 'kagawad' : '',
            'first_name' => 'Managed',
            'middle_name' => 'Test',
            'last_name' => 'User',
            'gender' => 'female',
            'birthdate' => '1990-01-01',
            'contact_number' => '09170000000',
            'is_student' => '0',
            'occupation' => 'Tester',
            'house_no' => '10',
            'street' => 'Main Street',
            'zone' => '1',
            'is_head_of_family' => '1',
            'head_of_family_id' => '',
            'head_of_family_name' => '',
            'username' => "managed-{$category}-{$index}",
            'email' => "managed-{$category}-{$index}@example.test",
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];
    }
}
