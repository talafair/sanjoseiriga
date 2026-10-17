<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\EventRaffleEntry;
use App\Models\EventRafflePrize;
use App\Models\EventRsvp;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\UniqueIdGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TalaFairDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.connections.mysql.database') !== 'talafair') {
            throw new \RuntimeException(
                'Stopped: this demo seeder is intended only for the local talafair database.'
            );
        }

        $this->command?->warn(
            'This seeder creates demo records. It does not delete existing records.'
        );

        DB::transaction(function (): void {
            $admin = $this->makeUser(
                'Admin', 'admin', 'admin12345', 'superadmin',
                null, null, false
            );

            $captain = $this->makeUser(
                'Captain San Jose', 'official', 'official12345', 'official',
                'barangay_council', 'barangay_captain', false
            );

            $kagawad = $this->makeUser(
                'Kagawad San Jose', 'kagawad', 'official12345', 'official',
                'barangay_council', 'kagawad', false
            );

            $secretary = $this->makeUser(
                'Secretary San Jose', 'secretary', 'official12345', 'official',
                'barangay_council', 'secretary', false
            );

            $residents = [];
            foreach ([
                ['Resident Talafair', 'resident'],
                ['Ynah Calibara', 'ynah'],
                ['Marcuz Briñas', 'marcuz'],
                ['Juan Dela Cruz', 'juan'],
                ['John Doe', 'johndoe'],
                ['Resident 6', 'resident6'],
                ['Resident 7', 'resident7'],
            ] as [$name, $username]) {
                $residents[] = $this->makeUser(
                    $name, $username, 'resident12345', 'resident',
                    null, null, true
                );
            }

            $now = now();

            $cleanup = $this->makeEvent($captain, [
                'title' => 'Barangay Clean-Up Drive',
                'body' => 'Demo event for testing attendance history and points.',
                'category' => 'events',
                'is_featured' => true,
                'is_event' => true,
                'raffle_enabled' => false,
                'event_start_at' => $now->copy()->subDays(7)->setTime(8, 0),
                'event_end_at' => $now->copy()->subDays(7)->setTime(11, 0),
                'rsvp_due_at' => $now->copy()->subDays(8),
                'audiences' => ['public'],
                'base_points' => 50,
                'confirmation_points' => 10,
                'weight_points' => 1,
                'participation_points' => 0,
                'venue_name' => 'Barangay San Jose Hall',
                'geofence_radius' => 300,
            ]);

            $assembly = $this->makeEvent($captain, [
                'title' => 'Barangay General Assembly',
                'body' => 'Demo upcoming event for testing event listings and RSVP.',
                'category' => 'events',
                'is_featured' => false,
                'is_event' => true,
                'raffle_enabled' => false,
                'event_start_at' => $now->copy()->addDays(3)->setTime(9, 0),
                'event_end_at' => $now->copy()->addDays(3)->setTime(12, 0),
                'rsvp_due_at' => $now->copy()->addDays(2),
                'audiences' => ['public'],
                'base_points' => 50,
                'confirmation_points' => 10,
                'weight_points' => 1,
                'participation_points' => 0,
                'venue_name' => 'Barangay San Jose Hall',
                'geofence_radius' => 300,
            ]);

            $raffle = $this->makeEvent($captain, [
                'title' => 'TalaFair Community Raffle',
                'body' => 'Demo raffle event with sample prizes and entries.',
                'category' => 'events',
                'is_featured' => false,
                'is_event' => true,
                'raffle_enabled' => true,
                'event_start_at' => $now->copy()->addDays(7)->setTime(9, 0),
                'event_end_at' => $now->copy()->addDays(7)->setTime(12, 0),
                'rsvp_due_at' => $now->copy()->addDays(6),
                'audiences' => ['public'],
                'base_points' => 50,
                'confirmation_points' => 10,
                'weight_points' => 1,
                'participation_points' => 0,
                'venue_name' => 'Barangay San Jose Hall',
                'geofence_radius' => 300,
            ]);

            foreach ([
                ['name' => 'Grocery Pack', 'type' => 'essentials', 'prize_type' => 'essentials', 'points_amount' => 0, 'quantity' => 2],
                ['name' => 'Cash Prize', 'type' => 'cash', 'prize_type' => 'cash', 'points_amount' => 0, 'quantity' => 1],
                ['name' => '500 TalaFair Points', 'type' => 'points', 'prize_type' => 'points', 'points_amount' => 500, 'quantity' => 1],
            ] as $index => $prize) {
                EventRafflePrize::create([
                    'announcement_id' => $raffle->id,
                    ...$prize,
                    'description' => 'Sample demo raffle prize.',
                    'sort_order' => $index + 1,
                    'created_by' => $captain->id,
                ]);
            }

            // Seed sample historical attendance directly.
            // These are demo records, not actual QR scans.
            foreach (array_slice($residents, 0, 5) as $index => $resident) {
                $scannedAt = $cleanup->event_start_at->copy()->addMinutes(10 + ($index * 5));
                $points = 50;

                Attendance::create([
                    'announcement_id' => $cleanup->id,
                    'user_id' => $resident->id,
                    'user_category' => 'resident',
                    'attendance_method' => 'event_qr_scan',
                    'scanned_at' => $scannedAt,
                    'is_early' => false,
                    'pre_registered' => false,
                    'latitude' => null,
                    'longitude' => null,
                    'distance_m' => null,
                    'points_awarded' => $points,
                ]);

                $resident->increment('points', $points);

                PointTransaction::create([
                    'user_id' => $resident->id,
                    'announcement_id' => $cleanup->id,
                    'type' => 'event_attendance',
                    'description' => 'Demo Attendance - ' . $cleanup->title,
                    'base_points' => $points,
                    'multiplier' => 1.00,
                    'points_awarded' => $points,
                ]);
            }

            $this->command?->info('Created 11 demo accounts and 3 demo events.');
            $this->command?->info('Demo usernames: admin, official, kagawad, secretary, resident, ynah, marcuz, juan, johndoe, resident6, resident7.');
        });
    }

    private function makeUser(
        string $name,
        string $username,
        string $password,
        string $role,
        ?string $officialGroup,
        ?string $officialPosition,
        bool $generateUniqueId
    ): User {
        $parts = preg_split('/\s+/', trim($name)) ?: [$name];
        $firstName = array_shift($parts) ?: $name;
        $lastName = count($parts) ? array_pop($parts) : null;
        $middleName = count($parts) ? implode(' ', $parts) : null;

        $user = User::updateOrCreate(
            ['username' => $username],
            [
                'name' => $name,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'email' => $username . '@talafair.test',
                'password' => $password,
                'role' => $role,
                'is_verified' => true,
                'official_group' => $officialGroup,
                'official_position' => $officialPosition,
                'points' => 0,
                'zone' => '2',
                'barangay' => 'San Jose',
                'city' => 'Iriga City',
                'province' => 'Camarines Sur',
                'country' => 'Philippines',
            ]
        );

        if ($generateUniqueId && ! $user->unique_id) {
            $user->forceFill([
                'unique_id' => UniqueIdGenerator::for($user),
            ])->save();
        }

        return $user;
    }

    private function makeEvent(User $creator, array $data): Announcement
    {
        $event = new Announcement();
        $event->fill($data);
        $event->created_by = $creator->id;
        $event->updated_by = $creator->id;
        $event->refreshQrToken();
        $event->save();

        return $event;
    }
}
