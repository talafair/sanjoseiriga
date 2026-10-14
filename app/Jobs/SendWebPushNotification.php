<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\TalaFairPushNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class SendWebPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly int $userId,
        public readonly string $title,
        public readonly ?string $body,
        public readonly string $url
    ) {
        $this->afterCommit = true;
    }

    public function handle(): void
    {
        $user = User::with('pushSubscriptions')->find($this->userId);

        if (! $user || $user->pushSubscriptions->isEmpty()) {
            return;
        }

        if (blank(config('webpush.vapid.subject'))
            || blank(config('webpush.vapid.public_key'))
            || blank(config('webpush.vapid.private_key'))) {
            throw new RuntimeException('Web Push subscriptions exist, but VAPID keys are not configured.');
        }

        $user->notify(new TalaFairPushNotification($this->title, $this->body, $this->url));
    }
}
