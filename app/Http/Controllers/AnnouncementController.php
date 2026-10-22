<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\EventReminder;
use App\Models\UserNotification;
use App\Models\EventRaffleEntry;
use App\Models\EventSubstitution;
use App\Models\User;
use App\Services\PushNotificationDelivery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use RuntimeException;

class AnnouncementController extends Controller
{
    /** Officials only for everything except index/show — see routes/web.php. */

    public function index()
    {
        $showArchived = request()->boolean('archived') && (request()->user()?->canAccessOfficialFeatures() ?? false);
        $featured = $showArchived
            ? null
            : Announcement::query()
                ->where('is_featured', true)
                ->where('is_archived', false)
                ->when(! auth()->check(), fn ($query) => $query->where(function ($query) {
                    $query->whereNull('audiences')
                        ->orWhereJsonContains('audiences', 'public')
                        ->orWhereJsonLength('audiences', 0);
                }))
                ->latest()
                ->first();

        $announcements = Announcement::query()
            ->where('is_archived', $showArchived)
            ->when(! auth()->check(), fn ($query) => $query->where(function ($query) {
                $query->whereNull('audiences')
                    ->orWhereJsonContains('audiences', 'public')
                    ->orWhereJsonLength('audiences', 0);
            }))
            ->reorder()
            ->orderByRaw(
                'CASE
                    WHEN is_event = 1 AND event_start_at IS NOT NULL
                        AND ((event_end_at IS NOT NULL AND event_end_at < ?)
                            OR (event_end_at IS NULL AND event_start_at < ?)) THEN 2
                    WHEN is_event = 1 AND event_start_at IS NOT NULL THEN 0
                    ELSE 1
                END',
                [now(), now()->subHours(Announcement::FALLBACK_SCAN_CLOSE_HOURS)]
            )
            ->orderByRaw('CASE WHEN is_event = 1 AND event_start_at IS NOT NULL THEN event_start_at END ASC')
            ->orderByDesc('created_at')
            ->paginate(15);
        $calendarAnnouncements = Announcement::query()
            ->where('is_archived', $showArchived)
            ->when(! auth()->check(), fn ($query) => $query->where(function ($query) {
                $query->whereNull('audiences')
                    ->orWhereJsonContains('audiences', 'public')
                    ->orWhereJsonLength('audiences', 0);
            }))
            ->with('creator')
            ->latest()
            ->get();

        $facilitators = User::query()
            ->where('role', 'official')
            ->where(fn ($query) => $query
                ->where('official_group', '!=', 'personnel')
                ->orWhere('is_verified', true))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('pages.announcements', compact('announcements', 'featured', 'facilitators', 'calendarAnnouncements', 'showArchived'));
    }

    public function archive(Announcement $announcement): RedirectResponse
    {
        if (! $announcement->is_event || $announcement->eventStatus() !== 'closed') {
            return back()->with('error', 'Only events that have ended can be archived.');
        }

        $announcement->update(['is_archived' => true]);

        return redirect()->route('announcements', ['archived' => 1])
            ->with('success', 'Event archived.');
    }

    public function restore(Announcement $announcement): RedirectResponse
    {
        $announcement->update(['is_archived' => false]);

        return redirect()->route('announcements', ['archived' => 1])
            ->with('success', 'Announcement restored.');
    }

    public function create()
    {
        // The "New Announcement" form is a Bootstrap modal inside the index page,
        // so there is no separate create screen to render.
        return redirect()->route('announcements.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $announcement = new Announcement($data);

        if ($request->hasFile('banner')) {
            $announcement->banner_path = $request->file('banner')->store('banners', config('filesystems.uploads_disk', 'public'));
            if (! $announcement->banner_path) {
                throw new RuntimeException('The announcement banner could not be saved to the configured upload storage.');
            }
        }

        if ($announcement->is_event) {
            $announcement->refreshQrToken();
        }

        $announcement->save();   // Auditable stamps created_by / updated_by + audit log
        $this->syncFacilitators($announcement, $data['facilitator_ids'] ?? []);

        if ($announcement->is_event) {
            $this->notifyAudience($announcement, 'New event: ' . $announcement->title,
                'You are invited. Please confirm your attendance before ' .
                $announcement->rsvp_due_at?->format('M j, Y g:i A') . '.');
        } else {
            $this->notifyAudience(
                $announcement,
                'New announcement: ' . $announcement->title,
                'A new community announcement has been published.'
            );
        }

        return redirect()->route('announcements.show', $announcement)
            ->with('success', 'Announcement published.');
    }

    public function show(Announcement $announcement)
    {
        $user = Auth::user();

        $announcement->load('creator', 'editor');

        if (! $user && ! in_array('public', $announcement->audiences ?: ['public'], true)) {
            abort(404);
        }

        $myRsvp = $user ? $announcement->rsvps()->where('user_id', $user->id)->first() : null;
        $myAttendance = $user && $announcement->is_event
            ? $announcement->attendances()->where('user_id', $user->id)->first()
            : null;
        $assignedSubstitution = $user && $announcement->is_event
            ? EventSubstitution::where('announcement_id', $announcement->id)
                ->where('substitute_user_id', $user->id)
                ->first()
            : null;
        $iAmAudience = $user !== null && (
            $user->belongsToAudience($announcement->audiences ?: ['public'])
            || $assignedSubstitution !== null
        );
        $canScanAttendance = $user?->canScanAttendance()
            && ($user->official_group !== 'personnel'
                || $announcement->facilitators()->where('user_id', $user->id)->exists());
        $householdMembers = collect();
        $substitution = null;

        if ($announcement->is_event && $user?->is_head_of_family) {
            $householdMembers = User::query()
                ->whereKeyNot($user->id)
                ->where('is_head_of_family', false)
                ->where(function ($query) use ($user) {
                    $query->where('head_of_family_id', $user->id)
                        ->orWhereRaw('LOWER(TRIM(head_of_family_name)) = ?', [strtolower(trim($user->full_name))])
                        ->orWhere(function ($addressQuery) use ($user) {
                            $addressQuery->whereRaw('LOWER(TRIM(house_no)) = ?', [strtolower(trim($user->house_no))])
                                ->whereRaw('LOWER(TRIM(street)) = ?', [strtolower(trim($user->street))])
                                ->when($user->zone !== null, fn ($q) => $q->whereRaw('LOWER(TRIM(zone)) = ?', [strtolower(trim($user->zone))]));
                        });
                })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            $substitution = EventSubstitution::where('announcement_id', $announcement->id)
                ->where('family_head_id', $user->id)
                ->first();
        }

        return view('pages.announcement-show', [
            'announcement' => $announcement,
            'myRsvp'       => $myRsvp,
            'myAttendance' => $myAttendance,
            'iAmAudience'  => $iAmAudience,
            'canScanAttendance' => $canScanAttendance,
            'stats'        => $this->statistics($announcement),
            'rafflePrizes' => $announcement->raffle_enabled
                ? $announcement->rafflePrizes()->withCount('winners')->get()
                : collect(),
            'raffleWinners' => $announcement->raffle_enabled
                ? $announcement->raffleWinners()->with('prize')->get()
                : collect(),
            'householdMembers' => $householdMembers,
            'substitution' => $substitution,
            'assignedSubstitution' => $assignedSubstitution,
        ]);
    }

    public function edit(Announcement $announcement)
    {
        // Editing also happens in a modal on the index page.
        return redirect()->route('announcements.index');
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $data = $this->validated($request, $announcement);
        $notificationFields = [
            'title', 'category', 'body', 'is_featured', 'is_event',
            'allow_guest_scanning', 'raffle_enabled', 'event_start_at', 'event_end_at',
            'rsvp_due_at', 'audiences', 'base_points', 'confirmation_points',
            'weight_points', 'participation_points', 'venue_name', 'venue_lat',
            'venue_lng', 'geofence_radius', 'banner_path',
        ];

        $oldBannerPath = $announcement->banner_path;
        if ($request->hasFile('banner')) {
            $data['banner_path'] = $request->file('banner')->store('banners', config('filesystems.uploads_disk', 'public'));
            if (! $data['banner_path']) {
                throw new RuntimeException('The announcement banner could not be saved to the configured upload storage.');
            }
        }

        $announcement->fill($data);

        if ($announcement->is_event) {
            $announcement->refreshQrToken();   // keeps the token, moves the expiry
        }

        $announcement->save();
        if ($oldBannerPath && isset($data['banner_path'])) {
            Storage::disk(config('filesystems.uploads_disk', 'public'))->delete($oldBannerPath);
        }

        $this->syncFacilitators($announcement, $data['facilitator_ids'] ?? []);

        if ($announcement->wasChanged($notificationFields)) {
            $this->notifyAudience(
                $announcement,
                ($announcement->is_event ? 'Event updated: ' : 'Announcement updated: ') . $announcement->title,
                $announcement->is_event
                    ? 'The event details have changed. Please review the latest information.'
                    : 'The announcement details have changed. Please review the latest information.'
            );
        }

        return redirect()->route('announcements.show', $announcement)
            ->with('success', 'Announcement updated. The change is logged under your ID.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('announcements')->with('success', 'Announcement deleted.');
    }

    /* ------------------------------------------------------------------
     | Event QR
     * ------------------------------------------------------------------*/

    public function qr(Announcement $announcement)
    {
        abort_unless($announcement->is_event, 404);

        $svg = (string) QrCode::format('svg')->size(320)->margin(1)
            ->errorCorrection('H')
            ->generate($announcement->qrPayload());

        return view('pages.event-qr', compact('announcement', 'svg'));
    }

    /* ------------------------------------------------------------------
     | Extend the survey window / re-notify the audience
     * ------------------------------------------------------------------*/

    public function extendSurvey(Request $request, Announcement $announcement): RedirectResponse
    {
        $data = $request->validate([
            'rsvp_due_at' => ['required', 'date', 'after:now', 'before_or_equal:' . ($announcement->event_start_at ?? now()->addYear())],
        ]);

        $announcement->update($data);

        $this->notifyAudience($announcement, 'Attendance survey extended: ' . $announcement->title,
            'You now have until ' . $announcement->rsvp_due_at->format('M j, Y g:i A') . ' to confirm.');

        return back()->with('success', 'Survey deadline moved to ' . $announcement->rsvp_due_at->format('M j, Y g:i A') . '.');
    }

    public function remind(Request $request, Announcement $announcement): RedirectResponse
    {
        $data = $request->validate([
            'kind'    => ['required', Rule::in(['event_reminder'])],
            'target'  => ['required', Rule::in(['everyone', 'pending_rsvp'])],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        abort_unless($announcement->is_event, 404);

        $onlyPending = $data['target'] === 'pending_rsvp';
        if ($onlyPending && ! $announcement->surveyIsOpen()) {
            return back()->with('error', 'RSVP reminders can only be sent while the confirmation period is open.');
        }

        $title = $onlyPending
            ? 'Please RSVP: ' . $announcement->title
            : 'Event reminder: ' . $announcement->title;
        $body = filled($data['message'] ?? null)
            ? $data['message']
            : ($onlyPending
                ? 'Please confirm whether you can attend this event.'
                : 'Here is a reminder about this event. View the event details for more information.');

        $count = $this->notifyAudience($announcement, $title, $body, onlyPending: $onlyPending);

        EventReminder::create([
            'announcement_id'  => $announcement->id,
            'sent_by'          => Auth::id(),
            'kind'             => $data['kind'],
            'message'          => $data['message'] ?? null,
            'recipients_count' => $count,
        ]);

        return back()->with('success', "Reminder sent to {$count} resident(s).");
    }

    /* ------------------------------------------------------------------
     | Statistics — visible to officials and to the audience
     * ------------------------------------------------------------------*/

    public function statistics(Announcement $announcement, bool $includePrivateDetails = false): array
    {
        $audience = (clone $announcement->audienceQuery())->count();

        $attending    = $announcement->rsvps()->where('status', 'attending')->count();
        $notAttending = $announcement->rsvps()->where('status', 'not_attending')->count();
        
        // Separate resident and official attendance
        $residentAttendances = $announcement->attendances()->where('user_category', 'resident');
        $officialAttendances = $announcement->attendances()->where('user_category', 'official');
        
        $residentScanned = $residentAttendances->count();
        $officialScanned = $officialAttendances->count();
        $scanned = $residentScanned + $officialScanned;
        
        $early        = $announcement->attendances()->where('is_early', true)->count();

        return [
            'audience'          => $audience,
            'attending'         => $attending,
            'not_attending'     => $notAttending,
            'no_response'       => max(0, $audience - $attending - $notAttending),
            'scanned'           => $scanned,
            'resident_scanned'  => $residentScanned,
            'official_scanned'  => $officialScanned,
            'early'             => $early,
            'on_time'           => $scanned - $early,
            'turnout_rate'      => $attending > 0 ? round($residentScanned / $attending * 100, 1) : 0.0,
            'reasons'           => $includePrivateDetails
                ? $announcement->rsvps()
                    ->where('status', 'not_attending')
                    ->whereNotNull('reason')
                    ->latest('responded_at')
                    ->limit(50)
                    ->get(['user_id', 'reason', 'responded_at'])
                : collect(),
        ];
    }

    public function statisticsPage(Announcement $announcement)
    {
        $canViewDetails = request()->user()->canAccessOfficialFeatures();
        $userName = $canViewDetails ? trim(request()->string('user_name')->toString()) : '';

        return view('pages.announcement-statistics', [
            'announcement' => $announcement,
            'stats'        => $this->statistics($announcement, $canViewDetails),
            'canViewDetails' => $canViewDetails,
            'attendees'    => $canViewDetails ? $announcement->attendances()
                                ->with('user')
                                ->when($userName !== '', fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%' . $userName . '%')))
                                ->orderBy('scanned_at')
                                ->get() : collect(),
            'raffleAttendees' => $canViewDetails && $announcement->raffle_enabled
                                ? $announcement->attendances()->with('user')->orderBy('scanned_at')->get()
                                : collect(),
            'userName'     => $userName,
            'rafflePrizes' => $announcement->rafflePrizes()->withCount('winners')->with('winners')->get(),
            'raffleWinners' => $announcement->raffleWinners()->with('prize')->get(),
        ]);
    }

    /* ------------------------------------------------------------------
     | Helpers
     * ------------------------------------------------------------------*/

    private function validated(Request $request, ?Announcement $existing = null): array
    {
        if ($request->boolean('is_event') && $existing && ! $request->exists('audiences')) {
            $request->merge(['audiences' => $existing->audiences ?: ['public']]);
        }

        $rules = [
            'title'       => ['required', 'string', 'max:255'],
            'category'    => ['required', Rule::in(['events', 'updates', 'rewards', 'maintenance', 'ice_breaker', 'q_and_a', 'game', 'intermission'])],
            'body'        => ['required', 'string'],
            'is_featured' => ['nullable', 'boolean'],
            'is_event'    => ['nullable', 'boolean'],
            'allow_guest_scanning' => ['nullable', 'boolean'],
            'raffle_enabled' => ['nullable', 'boolean'],
            'banner'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'facilitator_ids' => ['nullable', 'array'],
            'facilitator_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', 'official')
                    ->where(fn ($authorized) => $authorized
                        ->where('official_group', '!=', 'personnel')
                        ->orWhere('is_verified', true))),
            ],
        ];

        if ($request->boolean('is_event')) {
            $rules += [
                'event_start_at'  => ['required', 'date'],
                'event_end_at'    => ['required', 'date', 'after:event_start_at'],
                'rsvp_due_at'     => ['required', 'date', 'before_or_equal:event_start_at'],
                'audiences'       => ['required', 'array', 'min:1'],
                'audiences.*'     => [Rule::in(array_keys(Announcement::AUDIENCES))],
                'base_points'     => [Rule::requiredIf(fn () => ! in_array($request->input('category'), ['ice_breaker', 'q_and_a', 'game', 'intermission'], true)), 'integer', 'min:0', 'max:100000'],
                'confirmation_points' => ['integer', 'min:0', 'max:100000'],
                'weight_points'   => ['nullable', 'numeric', 'min:0', 'max:1000'],
                'participation_points' => [Rule::requiredIf(fn () => in_array($request->input('category'), ['ice_breaker', 'q_and_a', 'game', 'intermission'], true)), 'integer', 'min:0', 'max:100000'],
                'venue_name'      => ['nullable', 'string', 'max:255'],
                'venue_lat'       => ['required', 'numeric', 'between:-90,90'],
                'venue_lng'       => ['required', 'numeric', 'between:-180,180'],
                'geofence_radius' => ['required', 'integer', 'min:20', 'max:5000'],
            ];
        }

        $data = $request->validate($rules);

        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_event']    = $request->boolean('is_event');
        $data['allow_guest_scanning'] = $data['is_event'] && $request->boolean('allow_guest_scanning');
        $data['raffle_enabled'] = $data['is_event'] && $request->boolean('raffle_enabled');

        $isActivity = in_array($data['category'], ['ice_breaker', 'q_and_a', 'game', 'intermission'], true);
        $data['base_points'] = $data['is_event'] && ! $isActivity ? (int) ($data['base_points'] ?? 0) : 0;
        $data['confirmation_points'] = $data['is_event'] ? (int) ($data['confirmation_points'] ?? 0) : 0;
        $data['weight_points'] = $data['is_event'] ? (float) ($data['weight_points'] ?? 1) : 0;
        $data['participation_points'] = $data['is_event'] && $isActivity ? (int) ($data['participation_points'] ?? 0) : 0;

        if (! $data['is_event']) {
            foreach ([
                'event_start_at', 'event_end_at', 'rsvp_due_at', 'audiences',
                'venue_name', 'venue_lat', 'venue_lng',
                'qr_token', 'qr_expires_at',
            ] as $field) {
                $data[$field] = null;
            }
            $data['geofence_radius'] = 0;
            $data['allow_guest_scanning'] = false;
            $data['raffle_enabled'] = false;
        }

        unset($data['banner']);

        return $data;
    }

    private function syncFacilitators(Announcement $announcement, array $facilitatorIds): void
    {
        $ids = collect($facilitatorIds)->map(fn ($id) => (int) $id)->unique()->values();
        $existingIds = $announcement->facilitators()->pluck('user_id');
        $announcement->facilitators()->whereNotIn('user_id', $ids)->delete();

        $newIds = $ids->diff($existingIds);
        $ids->each(fn (int $userId) => $announcement->facilitators()->updateOrCreate(
            ['user_id' => $userId],
            ['assigned_by' => Auth::id(), 'role' => 'facilitator']
        ));

        if ($newIds->isNotEmpty()) {
            $now = now();
            UserNotification::insert($newIds->map(fn (int $userId) => [
                'user_id' => $userId,
                'announcement_id' => $announcement->id,
                'title' => 'Event facilitator assignment',
                'body' => "You have been assigned as a facilitator for {$announcement->title}. You may record your attendance using the event QR code.",
                'created_by' => Auth::id(),
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
            PushNotificationDelivery::queueForRecipients(
                $newIds,
                'Event facilitator assignment',
                "You have been assigned as a facilitator for {$announcement->title}. You may record your attendance using the event QR code.",
                route('announcements.show', $announcement)
            );
        }
    }

    /** Drops a notification into every targeted resident's inbox. Returns the count. */
    private function notifyAudience(Announcement $announcement, string $title, ?string $body, bool $onlyPending = false): int
    {
        $query = $announcement->audienceQuery();

        if ($onlyPending) {
            $query->whereDoesntHave('rsvps', fn ($q) => $q->where('announcement_id', $announcement->id));
        }

        $count = 0;

        $query->select('id')->chunkById(500, function ($users) use ($announcement, $title, $body, &$count) {
            $rows = $users->map(fn ($u) => [
                'user_id'         => $u->id,
                'announcement_id' => $announcement->id,
                'title'           => $title,
                'body'            => $body,
                'created_by'      => Auth::id(),
                'created_at'      => now(),
                'updated_at'      => now(),
            ])->all();

            UserNotification::insert($rows);
            PushNotificationDelivery::queueForRecipients(
                $users->modelKeys(),
                $title,
                $body,
                route('announcements.show', $announcement)
            );
            $count += count($rows);
        });

        return $count;
    }
}