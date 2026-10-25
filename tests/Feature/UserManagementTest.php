<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Announcement;
use App\Models\Attendance;
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
            if ($category === 'guest') {
                $this->assertDatabaseHas('users', [
                    'username' => "managed-{$category}-{$index}",
                    'gender' => 'female',
                    'barangay' => 'West District',
                    'city' => 'Naga City',
                    'province' => 'Camarines Sur',
                    'country' => 'Philippines',
                    'postal_code' => '4400',
                    'sex_at_birth' => null,
                    'preferred_gender_identity' => null,
                    'is_head_of_family' => false,
                ]);
            }
        }
    }

    public function test_add_user_form_includes_guest_registration_fields(): void
    {
        $this->actingAs($this->managementOfficial())
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('data-user-roles="guest"', false)
            ->assertSee('name="gender"', false)
            ->assertSee('name="barangay"', false)
            ->assertSee('name="city"', false)
            ->assertSee('name="postal_code"', false);
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

    public function test_add_user_rejects_a_non_numeric_zone(): void
    {
        $official = $this->managementSuperadmin();
        $payload = $this->userPayload('resident', 24);
        $payload['zone'] = 'Zone 2';

        $this->actingAs($official)
            ->from(route('users.create'))
            ->post(route('users.store'), $payload)
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors('zone');

        $this->assertDatabaseMissing('users', ['username' => 'managed-resident-24']);
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
            ->assertSee('name="birthdate"', false)
            ->assertSee('type="date"', false)
            ->assertSee('name="is_sjtoda"', false)
            ->assertSee('name="is_bpc"', false)
            ->assertSee('Category', false);
        $this->assertSame(1, substr_count($response->getContent(), 'id="role"'));

        $payload = $this->userPayload('guest', 20);
        $payload['email'] = $user->email;
        $payload['username'] = $user->username;
        $payload['first_name'] = 'Updated';
        unset($payload['password'], $payload['password_confirmation']);

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
        $this->assertSame('1990-01-01', $user->fresh()->birthdate->toDateString());
    }

    public function test_editing_a_users_zone_regenerates_their_zone_based_id(): void
    {
        $official = $this->managementSuperadmin();
        $year = now()->format('y');
        $user = User::factory()->create([
            'role' => 'resident',
            'username' => 'zone-update-target',
            'zone' => '1',
            'unique_id' => "Z1-{$year}000000001",
        ]);
        User::factory()->create([
            'role' => 'resident',
            'zone' => '2',
            'unique_id' => "Z2-{$year}000000123",
        ]);

        $payload = $this->userPayload('resident', 23);
        $payload['username'] = $user->username;
        $payload['email'] = $user->email;
        $payload['zone'] = '2';
        unset($payload['password'], $payload['password_confirmation']);

        $this->actingAs($official)
            ->put(route('users.update', $user), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('2', $user->fresh()->zone);
        $this->assertSame("Z2-{$year}000000124", $user->fresh()->unique_id);
    }

    public function test_password_update_is_audited_separately_and_notifies_superadmins_without_exposing_password(): void
    {
        $superadmin = $this->managementSuperadmin();
        $target = User::factory()->create([
            'role' => 'resident',
            'username' => 'password-target',
            'email' => 'password-target@example.test',
        ]);
        $newPassword = 'NeverExposeThisPassword123!';
        $payload = $this->userPayload('resident', 30);
        $payload['username'] = $target->username;
        $payload['email'] = $target->email;
        $payload['password'] = $newPassword;
        $payload['password_confirmation'] = $newPassword;

        $this->actingAs($superadmin)
            ->put(route('users.update', $target), $payload)
            ->assertRedirect();

        $passwordAudit = AuditLog::query()
            ->where('auditable_type', User::class)
            ->where('auditable_id', $target->id)
            ->where('action', 'password_updated')
            ->sole();
        $profileAudit = AuditLog::query()
            ->where('auditable_type', User::class)
            ->where('auditable_id', $target->id)
            ->where('action', 'updated')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(['password_changed' => true], $passwordAudit->new_values);
        $this->assertStringNotContainsString($newPassword, json_encode($passwordAudit->getAttributes()));
        $this->assertArrayNotHasKey('password', $profileAudit->new_values ?? []);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $superadmin->id,
            'title' => 'User Password Updated',
            'body' => 'Updated the password for Account "Managed Test User" by ' . $superadmin->full_name . '.',
        ]);
    }

    public function test_registration_uses_the_same_category_options(): void
    {
        $this->get(route('register', ['role' => 'resident']))
            ->assertOk()
            ->assertSee('value="resident"', false)
            ->assertSee('name="role"', false)
            ->assertSee('name="is_sjtoda"', false)
            ->assertSee('name="is_bpc"', false);

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
            'is_pwd' => '1',
            'is_sjtoda' => '1',
            'is_bpc' => '1',
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
            'is_pwd' => true,
            'is_sjtoda' => true,
            'is_bpc' => true,
            'student_level' => 'senior_high_school',
        ]);
    }

    public function test_managed_user_creation_and_editing_save_new_classifications_and_preserve_existing_ones(): void
    {
        $official = $this->managementSuperadmin();
        $payload = $this->userPayload('resident', 21);
        $payload['is_sjtoda'] = '1';
        $payload['is_bpc'] = '1';
        $payload['is_pwd'] = '1';
        $payload['is_4ps_member'] = '1';

        $this->actingAs($official)
            ->post(route('users.store'), $payload)
            ->assertRedirect(route('users.index'));

        $user = User::where('username', 'managed-resident-21')->firstOrFail();
        $this->assertTrue($user->is_sjtoda);
        $this->assertTrue($user->is_bpc);
        $this->assertTrue($user->is_pwd);
        $this->assertTrue($user->is_4ps_member);

        $update = $this->userPayload('resident', 21);
        $update['email'] = $user->email;
        $update['username'] = $user->username;
        $update['is_sjtoda'] = '0';
        $update['is_bpc'] = '1';
        unset($update['password'], $update['password_confirmation']);

        $this->actingAs($official)
            ->put(route('users.update', $user), $update)
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_sjtoda' => false,
            'is_bpc' => true,
            'is_pwd' => true,
            'is_4ps_member' => true,
        ]);
    }

    public function test_user_management_can_create_and_edit_accounts_without_email(): void
    {
        $official = $this->managementOfficial();
        $payload = $this->userPayload('resident', 22);
        $payload['email'] = '';

        $this->actingAs($official)
            ->post(route('users.store'), $payload)
            ->assertRedirect(route('users.index'));

        $created = User::where('username', 'managed-resident-22')->firstOrFail();
        $this->assertNull($created->email);

        $target = User::factory()->create([
            'role' => 'resident',
            'username' => 'account-no-email',
            'email' => 'remove-email@example.test',
        ]);
        $update = $this->userPayload('resident', 23);
        $update['username'] = $target->username;
        $update['email'] = '';
        unset($update['password'], $update['password_confirmation']);

        $this->actingAs($official)
            ->put(route('users.update', $target), $update)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'email' => null,
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
        $householdHead = User::factory()->create([
            'role' => 'resident',
            'is_head_of_family' => true,
        ]);
        $official = $this->managementOfficial();
        $official->forceFill([
            'is_verified' => false,
            'points' => 375,
            'head_of_family_id' => $householdHead->id,
            'unique_id' => 'OFFICIAL-PRESERVE-1',
        ])->save();
        $event = Announcement::create([
            'title' => 'Verification preservation event',
            'category' => 'events',
            'body' => 'Attendance remains linked after verification.',
            'is_event' => true,
            'event_start_at' => now()->subHour(),
            'event_end_at' => now()->addHour(),
            'qr_token' => 'verification-preservation-event',
            'audiences' => ['public'],
        ]);
        Attendance::create([
            'announcement_id' => $event->id,
            'user_id' => $official->id,
            'user_category' => 'official',
            'attendance_method' => 'event_qr_scan',
            'scanned_at' => now(),
            'points_awarded' => 25,
        ]);

        $this->actingAs($superadmin)
            ->patch(route('users.verification.toggle', $official))
            ->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $official->id,
            'is_verified' => true,
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'points' => 375,
            'head_of_family_id' => $householdHead->id,
            'unique_id' => 'OFFICIAL-PRESERVE-1',
        ]);
        $this->assertDatabaseHas('attendances', [
            'announcement_id' => $event->id,
            'user_id' => $official->id,
            'points_awarded' => 25,
        ]);

        $this->actingAs($superadmin)
            ->patch(route('users.verification.toggle', $official))
            ->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $official->id,
            'is_verified' => false,
            'role' => 'official',
            'official_group' => 'barangay_council',
            'official_position' => 'kagawad',
            'points' => 375,
            'head_of_family_id' => $householdHead->id,
            'unique_id' => 'OFFICIAL-PRESERVE-1',
        ]);
        $this->assertDatabaseHas('attendances', [
            'announcement_id' => $event->id,
            'user_id' => $official->id,
            'points_awarded' => 25,
        ]);
    }

    public function test_official_status_column_uses_the_shared_verification_field(): void
    {
        $superadmin = $this->managementSuperadmin();
        $verified = $this->managementOfficial();
        $verified->forceFill(['is_verified' => true])->save();
        $unverified = $this->managementOfficial();

        $this->actingAs($superadmin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Verified')
            ->assertSee('Unverified');
    }

    public function test_official_cannot_change_own_verification_status(): void
    {
        $official = $this->managementOfficial();

        $this->actingAs($official)
            ->patch(route('users.verification.toggle', $official))
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $official->id,
            'is_verified' => true,
        ]);
    }

    public function test_superadmin_cannot_be_deleted_or_demoted(): void
    {
        $superadmin = $this->managementSuperadmin();

        $this->actingAs($superadmin)->delete(route('users.destroy', $superadmin))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'official', 'is_verified' => true]))
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
            'is_verified' => true,
        ]);
    }

    private function userPayload(string $category, int $index = 0): array
    {
        $payload = [
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

        if ($category === 'guest') {
            $payload += [
                'barangay' => 'West District',
                'city' => 'Naga City',
                'province' => 'Camarines Sur',
                'country' => 'Philippines',
                'postal_code' => '4400',
            ];
            unset($payload['is_head_of_family'], $payload['head_of_family_id'], $payload['head_of_family_name']);
        }

        return $payload;
    }
}
