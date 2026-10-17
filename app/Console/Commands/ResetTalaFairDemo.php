<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetTalaFairDemo extends Command
{
    protected $signature = 'app:reset-tala-fair-demo';

    protected $description = 'Reset local TalaFair demo data';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Blocked: local environment only.');
            return self::FAILURE;
        }

        if (
            config('database.default') !== 'mysql' ||
            config('database.connections.mysql.database') !== 'talafair'
        ) {
            $this->error('Blocked: expected local database talafair.');
            return self::FAILURE;
        }

        $this->warn('This will delete existing local users and related demo data.');

        if (! $this->confirm('Have you verified your backup and want to continue?', false)) {
            $this->info('Cancelled. No records were deleted.');
            return self::SUCCESS;
        }

        $tables = [
            'announcement_participations',
            'attendances',
            'event_facilitators',
            'event_raffle_winners',
            'event_raffle_entries',
            'event_raffle_prizes',
            'event_reminders',
            'event_rsvps',
            'event_substitutions',
            'badge_user',
            'point_transactions',
            'raffle_entries',
            'game_runs',
            'spin_history',
            'trivia_answers',
            'survey_responses',
            'user_notifications',
            'push_subscriptions',
            'password_reset_tokens',
            'audit_logs',
            'trivia_themes',
            'surveys',
        ];

        DB::transaction(function () use ($tables): void {
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            if (
                Schema::hasTable('badges') &&
                Schema::hasColumn('badges', 'announcement_id')
            ) {
                DB::table('badges')
                    ->whereNotNull('announcement_id')
                    ->delete();
            }

            if (Schema::hasTable('announcements')) {
                DB::table('announcements')->delete();
            }

           // Preserve settings, but detach them from old users.
if (
    Schema::hasTable('settings') &&
    Schema::hasColumn('settings', 'updated_by')
) {
    DB::table('settings')->update(['updated_by' => null]);
}

// Preserve settings but detach them from old users.
if (
    Schema::hasTable('settings') &&
    Schema::hasColumn('settings', 'updated_by')
) {
    DB::table('settings')->update(['updated_by' => null]);
}

// Clear family references before deleting users.
if (
    Schema::hasTable('users') &&
    Schema::hasColumn('users', 'head_of_family_id')
) {
    DB::table('users')->update(['head_of_family_id' => null]);
}

if (Schema::hasTable('users')) {
    DB::table('users')->delete();
}
        });

        $this->info('Local demo records cleared successfully.');

        return self::SUCCESS;
    }
}
