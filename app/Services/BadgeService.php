<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Badge;
use App\Models\GameRun;
use App\Models\User;
use App\Models\UserNotification;

class BadgeService
{
    public static function awardEligible(User $user, ?Announcement $event = null, ?int $attendancePosition = null, bool $includeGameBadges = false): void
    {
        $attendanceCount = $user->attendances()->count();
        $attendanceIds = $user->attendances()->pluck('announcement_id')->flip();
        $streak = 0;
        foreach (Announcement::where('is_event', true)->whereNotNull('event_start_at')->where('event_start_at', '<=', now())->orderByDesc('event_start_at')->pluck('id') as $eventId) {
            if (! $attendanceIds->has($eventId)) break;
            $streak++;
        }
        $badges = Badge::where('award_method', 'automatic')->get();

        foreach ($badges as $badge) {
            if ($badge->category === 'games' && ! $includeGameBadges) continue;
            self::awardBadgeIfEligible($user, $badge, $attendanceCount, $streak, $attendancePosition, $event);
        }
    }

    public static function awardExistingUsersForBadge(Badge $badge): void
    {
        if ($badge->award_method !== 'automatic') {
            return;
        }

        $event = $badge->announcement;
        $completedEventIds = $badge->condition_key === 'streak'
            ? Announcement::where('is_event', true)
                ->whereNotNull('event_start_at')
                ->where('event_start_at', '<=', now())
                ->orderByDesc('event_start_at')
                ->pluck('id')
            : collect();

        User::whereIn('role', ['resident', 'guest'])->orderBy('id')->chunkById(100, function ($users) use ($badge, $event, $completedEventIds): void {
            foreach ($users as $user) {
                $attendanceCount = $user->attendances()->count();
                $attendanceIds = $user->attendances()->pluck('announcement_id')->flip();
                $streak = 0;
                foreach ($completedEventIds as $eventId) {
                    if (! $attendanceIds->has($eventId)) {
                        break;
                    }
                    $streak++;
                }

                $attendancePosition = $badge->condition_key === 'early_bird'
                    ? self::existingEarlyBirdPosition($user, $badge)
                    : null;

                self::awardBadgeIfEligible($user, $badge, $attendanceCount, $streak, $attendancePosition, $event);
            }
        });
    }

    public static function awardGameBadges(User $user): void
    {
        self::awardEligible($user, null, null, true);
    }

    private static function awardBadgeIfEligible(
        User $user,
        Badge $badge,
        int $attendanceCount,
        int $streak,
        ?int $attendancePosition,
        ?Announcement $event
    ): void {
        if ($badge->announcement_id && (! $event || $badge->announcement_id !== $event->id)) {
            return;
        }

        $eligible = match ($badge->condition_key) {
            'first_event', 'event_attendance' => $event
                ? $user->attendances()->where('announcement_id', $event->id)->exists()
                : $attendanceCount >= 1,
            'early_arrival' => $user->attendances()
                ->where('is_early', true)
                ->when($event, fn ($query) => $query->where('announcement_id', $event->id))
                ->exists(),
            'early_bird' => $attendancePosition !== null && $attendancePosition <= ($badge->limited_total ?: 20),
            'participation_count' => $user->participations()->count() >= max(1, (int) $badge->points_required),
            'attendance_count' => $attendanceCount >= max(1, (int) $badge->points_required),
            'streak' => $streak >= max(1, (int) $badge->points_required),
            'points' => $user->points >= $badge->points_required,
            'best_player_week', 'best_player_month', 'best_player_all_time' => self::isBestGamePlayer($user, $badge->condition_key),
            default => false,
        };

        if (! $eligible || ($badge->limited_total && $badge->condition_key !== 'early_bird' && $badge->users()->count() >= $badge->limited_total)) {
            return;
        }

        if ($user->badges()->whereKey($badge->id)->exists()) {
            return;
        }

        $awardRank = $badge->condition_key === 'early_bird' ? $attendancePosition : null;
        $user->badges()->attach($badge->id, ['awarded_by' => null, 'award_rank' => $awardRank]);
        UserNotification::create([
            'user_id' => $user->id,
            'title' => 'Badge unlocked',
            'body' => $awardRank ? "You earned {$badge->name} #{$awardRank}/" . ($badge->limited_total ?: 20) . '.' : "You earned the {$badge->name} badge.",
            'created_by' => null,
        ]);
    }

    private static function existingEarlyBirdPosition(User $user, Badge $badge): ?int
    {
        $attendances = $user->attendances()
            ->when($badge->announcement_id, fn ($query) => $query->where('announcement_id', $badge->announcement_id))
            ->orderBy('id')
            ->get(['id', 'announcement_id']);
        $maximumRank = $badge->limited_total ?: 20;

        foreach ($attendances as $attendance) {
            $position = Attendance::where('announcement_id', $attendance->announcement_id)
                ->where('id', '<=', $attendance->id)
                ->count();

            if ($position <= $maximumRank) {
                return $position;
            }
        }

        return null;
    }

    private static function isBestGamePlayer(User $user, string $condition): bool
    {
        $start = match ($condition) {
            'best_player_week' => now()->startOfWeek(),
            'best_player_month' => now()->startOfMonth(),
            default => null,
        };

        $scores = GameRun::query()
            ->when($start, fn ($query) => $query->whereDate('played_on', '>=', $start->toDateString()))
            ->selectRaw('user_id, SUM(total_score) as total_score')
            ->groupBy('user_id')
            ->orderByDesc('total_score')
            ->get();

        return $scores->first()?->user_id === $user->id;
    }
}