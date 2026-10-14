<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use NotificationChannels\WebPush\Events\NotificationFailed;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(NotificationFailed::class, function (NotificationFailed $event): void {
            Log::warning('TalaFair Web Push delivery failed.', [
                'subscription_id' => $event->subscription->getKey(),
                'expired' => $event->report->isSubscriptionExpired(),
                'status_code' => $event->report->getResponse()?->getStatusCode(),
                'reason' => $event->report->getReason(),
            ]);
        });
    }
}
