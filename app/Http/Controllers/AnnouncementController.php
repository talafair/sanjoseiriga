<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\EventReminder;
use App\Models\UserNotification;
use App\Models\EventRaffleEntry;
use App\Models\EventSubstitution;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AnnouncementController extends Controller
{
    /** Officials only for everything except index/show — see routes/web.php. */

    public function index()
    {
        // The featured announcement fills the banner, so keep it out of the grid below it.
        $featured = Announcement::where('is_featured', true)->latest()->first();

        $announcements = Announcement::latest()
            ->when($featured, fn ($q) => $q->whereKeyNot($featured->getKey()))
            ->paginate(15);
        $calendarAnnouncements = Announcement::with('creator')->latest()->get();

        $facilitators = User::query()
            ->where('role', 'official')
            ->where(fn ($query) => $query
                ->where('official_group', '!=', 'personnel')
                ->orWhere('is_verified', true))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('pages.announcements', compact('announcements', 'featured', 'facilitators', 'calendarAnnouncements'));
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
        }

        return redirect()->route('announcements.show', $announcement)
            ->with('success', 'Announcement published.');
    }

    public function show(Announcement $announcement)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $announcement->load('creator', 'editor');

        $myRsvp = $announcement->rsvps()->where('user_id', $user->id)->first();
        $assignedSubstitution = $announcement->is_event
            ? EventSubstitution::where('announcement_id', $announcement->id)
                ->where('substitute_user_id', $user->id)
                ->first()
            : null;
        $iAmAudience = $user->belongsToAudience($announcement->audiences ?: ['public'])
            || $assignedSubstitution !== null;
        $householdMembers = collect();
        $substitution = null;

        if ($announcement->is_event && $user->is_head_of_family) {
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
            'iAmAudience'  => $iAmAudience,
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

        if ($request->hasFile('banner')) {
            if ($announcement->banner_path) {
                Storage::disk(config('filesystems.uploads_disk', 'public'))->delete($announcement->banner_path);
            }
            $data['banner_path'] = $request->file('banner')->store('banners', config('filesystems.uploads_disk', 'public'));
        }

        $announcement->fill($data);

        if ($announcement->is_event) {
            $announcement->refreshQrToken();   // keeps the token, moves the expiry
        }

        $announcement->save();
        $this->syncFacilitators($announcement, $data['facilitator_ids'] ?? []);

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
            'kind'    => ['required', Rule::in(['event_reminder', 'survey_closing'])],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        // Guardrail from the spec: reminders are for the day before the event,
        // or the final hours of the survey window.
        if ($data['kind'] === 'event_reminder') {
            abort_unless(
                $announcement->event_start_at && now()->greaterThanOrEqualTo($announcement->event_start_at->copy()->subDay()),
                422,
                'Event reminders can only be sent within 24 hours of the event.'
            );
        } else {
            abort_unless(
                $announcement->rsvp_due_at && now()->greaterThanOrEqualTo($announcement->rsvp_due_at->copy()->subHours(24))
                    && now()->lessThanOrEqualTo($announcement->rsvp_due_at),
                422,
                'Survey reminders can only be sent in the last 24 hours before the survey closes.'
            );
        }

        $title = $data['kind'] === 'event_reminder'
            ? 'Reminder: ' . $announcement->title . ' is tomorrow'
            : 'Last call: confirm your attendance for ' . $announcement->title;

        $count = $this->notifyAudience($announcement, $title, $data['message'] ?? null, onlyPending: $data['kind'] === 'survey_closing');

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

    public function statistics(Announcement $announcement): array
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
            'reasons'           => $announcement->rsvps()
                                    ->where('status', 'not_attending')
                                    ->whereNotNull('reason')
                                    ->latest('responded_at')
                                    ->limit(50)
                                    ->get(['user_id', 'reason', 'responded_at']),
        ];
    }

    public function statisticsPage(Announcement $announcement)
    {
        $userName = trim(request()->string('user_name')->toString());

        return view('pages.announcement-statistics', [
            'announcement' => $announcement,
            'stats'        => $this->statistics($announcement),
            'attendees'    => $announcement->attendances()
                                ->with('user')
                                ->when($userName !== '', fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%' . $userName . '%')))
                                ->orderBy('scanned_at')
                                ->get(),
            'raffleAttendees' => $announcement->raffle_enabled
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
            $count += count($rows);
        });

        return $count;
    }
}