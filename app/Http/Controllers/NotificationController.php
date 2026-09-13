<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->appNotifications()->paginate(15);

        return view('pages.notifications', [
            'notifications' => $notifications,
            'unreadCount' => $request->user()->unreadAppNotifications()->count(),
        ]);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadAppNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function open(Request $request, UserNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['read_at' => $notification->read_at ?? now()]);

        if ($notification->announcement) {
            return redirect()->route('announcements.show', $notification->announcement);
        }

        if ($notification->survey) {
            return redirect()->route('surveys.show', $notification->survey);
        }

        if (str_starts_with($notification->title, 'New trivia')) {
            return redirect()->route('games.index');
        }

        if (str_contains($notification->title, 'Badge')) {
            return redirect()->route('badges.catalog');
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
            return redirect()->route('account');
        }

        return redirect()->route('notifications.index');
    }
}
