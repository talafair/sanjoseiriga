<?php

namespace App\Providers;

use App\Models\Announcement;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
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
        View::composer('layouts.app', function ($view): void {
            $user = Auth::user();
            if (! $user) {
                return;
            }

            $events = Announcement::scanningOpen()
                ->when(
                    $user->role === 'official' && $user->official_group === 'personnel',
                    fn ($query) => $query->whereHas(
                        'facilitators',
                        fn ($facilitators) => $facilitators->where('user_id', $user->id)
                    )
                )
                ->when(
                    $user->isGuest(),
                    fn ($query) => $query->where('allow_guest_scanning', true)
                )
                ->orderBy('event_start_at')
                ->get(['id', 'title', 'qr_token', 'event_start_at', 'audiences'])
                ->filter(fn (Announcement $event) => $user->role !== 'resident'
                    || $user->belongsToAudience($event->audiences ?: ['public'])
                )
                ->values();

            $view->with('offlineAttendanceEvents', $events);
        });

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
