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

    public function open(Request $request, UserNotification $notification): RedirectResponse|View
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['read_at' => $notification->read_at ?? now()]);

        if ($notification->announcement) {
            return redirect()->route('announcements.show', $notification->announcement);
        }

        if ($notification->survey) {
            if ($notification->survey->audienceQuery()->whereKey($request->user()->id)->exists()
                || $request->user()->isOfficial()) {
                return redirect()->route('surveys.show', $notification->survey);
            }

            return $this->details($notification, available: false);
        }

        if ($this->expectsDeletedAnnouncement($notification) || $this->expectsDeletedSurvey($notification)) {
            return $this->details($notification, available: false);
        }

        if (str_starts_with($notification->title, 'New trivia')) {
            return $this->details($notification, destination: route('games.index'), destinationLabel: 'Open Trivia');
        }

        if (str_contains($notification->title, 'Badge')) {
            return $this->details($notification, destination: route('badges.catalog'), destinationLabel: 'View badges');
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

        return $this->details($notification);
    }

    private function details(
        UserNotification $notification,
        bool $available = true,
        ?string $destination = null,
        ?string $destinationLabel = null
    ): View {
        return view('pages.notification-detail', compact(
            'notification',
            'available',
            'destination',
            'destinationLabel'
        ));
    }

    private function expectsDeletedAnnouncement(UserNotification $notification): bool
    {
        return in_array($notification->title, [
            'Deleted Announcement',
            'Event facilitator assignment',
            'You were assigned as a substitute',
        ], true)
            || str_starts_with($notification->title, 'New event:')
            || str_starts_with($notification->title, 'Attendance survey extended:');
    }

    private function expectsDeletedSurvey(UserNotification $notification): bool
    {
        return in_array($notification->title, ['Deleted Survey'], true)
            || str_starts_with($notification->title, 'New survey:');
    }
}
