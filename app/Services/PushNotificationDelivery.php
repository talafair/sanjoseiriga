<?php

namespace App\Services;

use App\Jobs\SendWebPushNotification;
use App\Models\User;
use App\Models\UserNotification;

class PushNotificationDelivery
{
    public static function queueForNotification(UserNotification $notification): void
    {
        self::queueForRecipient(
            $notification->user_id,
            $notification->title,
            $notification->body,
            self::urlForNotification($notification)
        );
    }

    /**
     * @param  iterable<int|string>  $userIds
     */
    public static function queueForRecipients(iterable $userIds, string $title, ?string $body, string $url): void
    {
        $userIds = collect($userIds)->map(fn ($id) => (int) $id)->unique()->values();

        if ($userIds->isEmpty()) {
            return;
        }

        $subscribedUserIds = User::query()
            ->whereKey($userIds)
            ->whereHas('pushSubscriptions')
            ->pluck('id');

        foreach ($subscribedUserIds as $userId) {
            self::queueForRecipient((int) $userId, $title, $body, $url);
        }
    }

    private static function queueForRecipient(int $userId, string $title, ?string $body, string $url): void
    {
        SendWebPushNotification::dispatch($userId, $title, $body, $url);
    }

    private static function urlForNotification(UserNotification $notification): string
    {
        if ($notification->announcement_id) {
            return route('announcements.show', $notification->announcement_id);
        }

        if ($notification->survey_id) {
            return route('surveys.show', $notification->survey_id);
        }

        if (str_starts_with($notification->title, 'New trivia')) {
            return route('games.index');
        }

        if (str_contains($notification->title, 'Badge')) {
            return route('badges.catalog');
        }

        if (in_array($notification->title, [
            'Account created by an official',
            'Account information updated',
            'Account verified',
            'Account verification changed',
            'Profile information updated',
            'Profile picture updated',
            'Profile picture removed',
            'Password changed',
        ], true)) {
            return route('account');
        }

        return route('notifications.index');
    }
}
