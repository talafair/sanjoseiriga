<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TalaFairPushNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly ?string $body,
        private readonly string $url
    ) {
    }

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->body ?? '')
            ->icon(asset('images/icon-192.png'))
            ->tag('talafair-update-' . $notification->id)
            ->data(['url' => $this->url])
            ->options(['TTL' => 86400]);
    }
}
