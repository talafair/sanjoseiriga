<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementParticipation;
use App\Models\Attendance;
use App\Models\EventSubstitution;
use App\Models\User;
use App\Models\EventRaffleEntry;
use App\Models\PointTransaction;
use App\Services\Geofence;
use App\Services\PointsCalculator;
use App\Services\RaffleService;
use App\Services\BadgeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /** The scanner screen (camera opens here). */
    public function scanner(Request $request, ?string $token = null, ?Announcement $announcement = null)
    {
        $announcement = $announcement ?: ($request->filled('announcement')
            ? Announcement::findOrFail($request->integer('announcement'))
            : null);

        abort_if($announcement && ! $announcement->is_event, 404);

        /** @var User $viewer */
        $viewer = Auth::user();

        if (! $viewer->isOfficial() && ! $viewer->isGuest() && ! $viewer->is_verified) {
            return $this->fail('Your account must be verified by an official before you can scan attendance.');
        }
        abort_if($announcement && $viewer->isGuest() && ! $announcement->allow_guest_scanning, 403, 'Guest scanning is not enabled for this event.');
        abort_if($announcement?->isParticipationActivity() && ! $viewer->isOfficial(), 403);

        return view('pages.attendance', [
            'prefilledToken' => $token,
            'announcement'   => $announcement,
            'officialMode'   => $viewer->isOfficial(),
            'activityMode'   => $announcement?->isParticipationActivity() ?? false,
            'openEvents'     => Announcement::scanningOpen()
                ->orderBy('event_start_at')
                ->get(),
        ]);
    }

    /**
     * Called by the camera page once a QR is decoded.
     * Expects: token, latitude, longitude, accuracy
     *
     * Flow:
     * 1. If token is an Event QR:
     *    - Official: Record official's own attendance
     *    - Resident/Guest: Record their own attendance
     * 2. If token is a Resident QR (only for officials):
     *    - Record the scanned resident's attendance
     */
    public function check(Request $request): JsonResponse
    {
        /** @var User $viewer */
        $viewer = Auth::user();

        $data = $request->validate([
            'token'     => ['required', 'string', 'max:1024'],
            'announcement_id' => ['nullable', 'integer', 'exists:announcements,id'],
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy'  => ['nullable', 'numeric'],
        ]);

        if (! $viewer->isOfficial() && ! $viewer->isGuest() && ! $viewer->is_verified) {
            return $this->fail('Your account must be verified by an official before attendance can be recorded.');
        }

        // The QR may hold the full URL — pull the token out of it.
        $token = trim(basename(parse_url($data['token'], PHP_URL_PATH) ?: $data['token']));

        // Try to look up as an Event QR
        $event = Announcement::events()->where('qr_token', $token)->first();

        if ($data['announcement_id'] ?? null) {
            $requestedEvent = Announcement::events()->find($data['announcement_id']);

            if ($event && $event->id !== $requestedEvent?->id) {
                return $this->fail('That QR code does not belong to this announcement.');
            }

            if (! $event && ! $viewer->isOfficial()) {
                return $this->fail('Invalid event QR code. Please scan this event\'s QR code.');
            }

            $event = $event ?: $requestedEvent;
        }

        if (! $event) {
            return $this->fail('Invalid event QR code. Please scan the QR code for the correct event.');
        }

        if ($viewer->isOfficial()) {
            $scanKey = "attendance_scans.{$event->id}";
            session([$scanKey => min(3, session($scanKey, 0) + 1)]);
        }

        if ($viewer->isGuest() && ! $event->allow_guest_scanning) {
            return $this->fail('Guest QR scanning is not enabled for this event.');
        }

        $canFacilitate = $viewer->isOfficial() || $this->isAssignedFacilitator($viewer, $event);
        if ($viewer->role === 'official' && ! $canFacilitate) {
            return $this->fail('You are not assigned or authorized to facilitate this event.');
        }

        if ($windowError = $this->attendanceWindowError($event)) {
            return $windowError;
        }

        // Geofence: use the venue pin, falling back to the barangay hall coordinates.
        $lat    = (float) ($event->venue_lat ?? config('talafair.barangay_lat'));
        $lng    = (float) ($event->venue_lng ?? config('talafair.barangay_lng'));
        $radius = (int) ($event->geofence_radius ?: config('talafair.default_radius', 300));

        $distance = Geofence::distance((float) $data['latitude'], (float) $data['longitude'], $lat, $lng);

        if ($distance > $radius) {
            return $this->fail(sprintf(
                'Attendance was not recorded. You are about %s m from %s. Move within %d m of the venue and scan again.',
                number_format($distance), $event->venue_name ?: 'the venue', $radius
            ));
        }

        /** @var User $user */
        $user = Auth::user();

        // Determine who is attending
        $attendee = $user;
        $attendanceMethod = 'event_qr_scan';
        $userCategory = $canFacilitate ? 'official' : 'resident';

        // If official, check if they're trying to record a resident's attendance
        // by attempting to parse the token as a resident QR
        if ($user->isOfficial()) {
            $residentResult = $this->tryParseResidentQr($data['token']);
            if ($residentResult instanceof User) {
                // Token is a resident QR - record the resident's attendance
                $attendee = $residentResult;
                $attendanceMethod = 'official_qr_scan';
                $userCategory = 'resident';
            }
            // else: Token is an event QR, so record the official's own attendance
        }

        if ($userCategory !== 'official' && ! $this->canAttendEvent($attendee, $event)) {
            return $this->fail('You are not eligible to attend this event.');
        }

        return $this->recordAttendance($event, $attendee, $data, $distance, $userCategory, $attendanceMethod);
    }

    /** Officials can use the resident's printed unique ID through the same attendance flow. */
    public function checkById(Request $request): JsonResponse
    {
        /** @var User $viewer */
        $viewer = Auth::user();
        abort_unless($viewer->isOfficial(), 403);

        $data = $request->validate([
            'announcement_id' => ['required', 'integer', 'exists:announcements,id'],
            'unique_id'       => ['required', 'string', 'max:32'],
            'latitude'        => ['required', 'numeric', 'between:-90,90'],
            'longitude'       => ['required', 'numeric', 'between:-180,180'],
        ], [
            'unique_id.required' => 'Enter the resident Unique QR ID.',
        ]);

        $data['unique_id'] = trim($data['unique_id']);
        if ($data['unique_id'] === '') {
            return $this->fail('Unique QR ID not found. Please check the ID and try again.');
        }

        $event = Announcement::events()->findOrFail($data['announcement_id']);
        $attendee = User::where('unique_id', trim($data['unique_id']))
            ->where('role', 'resident')
            ->first();

        if (! $attendee) {
            return $this->fail('Unique QR ID not found. Please check the ID and try again.');
        }

        $lat = (float) ($event->venue_lat ?? config('talafair.barangay_lat'));
        $lng = (float) ($event->venue_lng ?? config('talafair.barangay_lng'));
        $distance = Geofence::distance((float) $data['latitude'], (float) $data['longitude'], $lat, $lng);
        $radius = (int) ($event->geofence_radius ?: config('talafair.default_radius', 300));

        if ($windowError = $this->attendanceWindowError($event)) {
            return $windowError;
        }

        if (! $this->canAttendEvent($attendee, $event)) {
            return $this->fail('This resident is not eligible to attend this event.');
        }

        if ($distance > $radius) {
            return $this->fail(sprintf(
                'Attendance was not recorded. You are about %s m from %s. Move within %d m of the venue and try again.',
                number_format($distance), $event->venue_name ?: 'the venue', $radius
            ));
        }

        return $this->recordAttendance($event, $attendee, $data, $distance, 'resident', 'manual_unique_id');
    }

    public function recordParticipation(Request $request, Announcement $announcement): JsonResponse
    {
        /** @var User $official */
        $official = Auth::user();
        abort_unless($official->isOfficial() && $announcement->isParticipationActivity(), 403);

        $data = $request->validate([
            'token' => ['required', 'string', 'max:1024'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $payload = json_decode($data['token'], true);
        $id = is_array($payload) ? ($payload['id'] ?? null) : null;
        $signature = is_array($payload) ? ($payload['sig'] ?? null) : null;

        if (! $id || ! $signature || ! hash_equals(substr(hash_hmac('sha256', $id, config('app.key')), 0, 16), $signature)) {
            return $this->fail('That resident ID QR code is invalid or has been altered.');
        }

        $resident = User::where('unique_id', $id)->where('role', 'resident')->first();
        if (! $resident) {
            return $this->fail('That resident ID could not be found.');
        }

        $lat = (float) ($announcement->venue_lat ?? config('talafair.barangay_lat'));
        $lng = (float) ($announcement->venue_lng ?? config('talafair.barangay_lng'));
        $distance = Geofence::distance((float) $data['latitude'], (float) $data['longitude'], $lat, $lng);
        $radius = (int) ($announcement->geofence_radius ?: config('talafair.default_radius', 300));

        if (! $announcement->scanningIsOpen() || $announcement->qrIsExpired() || $distance > $radius) {
            return $this->fail('This activity is not currently accepting participation at this location.');
        }

        if ($announcement->participations()->where('user_id', $resident->id)->exists()) {
            return $this->fail('This resident already received participation points for this activity.');
        }

        $points = (int) $announcement->participation_points;
        DB::transaction(function () use ($announcement, $resident, $official, $points) {
            AnnouncementParticipation::create([
                'announcement_id' => $announcement->id,
                'user_id' => $resident->id,
                'scanned_by' => $official->id,
                'points_awarded' => $points,
                'scanned_at' => now(),
            ]);
            $resident->increment('points', $points);
            RaffleService::ensureEntry($resident);
            $resident->refresh();
            BadgeService::awardEligible($resident);
        });

        return response()->json([
            'ok' => true,
            'resident' => $resident->full_name,
            'points' => $points,
            'message' => "Participation recorded. +{$points} points.",
        ]);
    }

    private function recordAttendance(Announcement $event, User $attendee, array $data, float $distance, string $userCategory = 'resident', string $attendanceMethod = 'event_qr_scan'): JsonResponse
    {
        $early = $event->isEarlyScan();
        $preRegistered = $event->rsvps()->where('user_id', $attendee->id)->where('status', 'attending')->exists();
        
        $breakdown = PointsCalculator::breakdown($event, $preRegistered, $early);
        $attendancePosition = null;

        try {
            DB::transaction(function () use ($event, $attendee, $data, $distance, $early, $preRegistered, $breakdown, $userCategory, $attendanceMethod, &$attendancePosition) {
            $lockedEvent = Announcement::query()->lockForUpdate()->findOrFail($event->id);
            if ($lockedEvent->attendances()->where('user_id', $attendee->id)->exists()) {
                $categoryLabel = $userCategory === 'official' ? 'official' : 'resident';
                throw new \DomainException("This resident has already been recorded for this event.");
            }

            $attendancePosition = $lockedEvent->attendances()->count() + 1;
            Attendance::create([
                'announcement_id' => $lockedEvent->id,
                'user_id' => $attendee->id,
                'user_category' => $userCategory,
                'attendance_method' => $attendanceMethod,
                'scanned_at' => now(),
                'is_early' => $early,
                'pre_registered' => $preRegistered,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'distance_m' => (int) round($distance),
                'points_awarded' => $breakdown['total'],
            ]);
            
            // Residents receive account points; every successful attendee may enter the event raffle.
            if ($breakdown['total'] > 0) {
                $attendee->increment('points', $breakdown['total']);
                PointTransaction::create([
                    'user_id' => $attendee->id,
                    'announcement_id' => $lockedEvent->id,
                    'type' => 'event_attendance',
                    'description' => 'Event Attendance - ' . $lockedEvent->title,
                    'base_points' => (int) $lockedEvent->base_points,
                    'multiplier' => $early ? 1.10 : 1.00,
                    'points_awarded' => $breakdown['total'],
                ]);
            }
            if ($userCategory === 'resident') {
                RaffleService::ensureEntry($attendee);
                $attendee->refresh();
                BadgeService::awardEligible($attendee, $lockedEvent, $attendancePosition);
            }
            if ($lockedEvent->raffle_enabled) {
                EventRaffleEntry::firstOrCreate(
                    ['announcement_id' => $lockedEvent->id, 'user_id' => $attendee->id],
                    [
                        'is_early' => $early,
                        'points_snapshot' => (int) $lockedEvent->base_points,
                        'weight' => RaffleService::weight((int) $lockedEvent->base_points, $early),
                    ]
                );
            }
            });
        } catch (\DomainException $exception) {
            return $this->fail($exception->getMessage());
        }

        // Build response message
        $message = $early
            ? "Attendance recorded successfully. You earned +{$breakdown['total']} points, including the 10% early bonus."
            : "Attendance recorded successfully. You earned +{$breakdown['total']} points.";

        return response()->json([
            'ok' => true,
            'scan_count' => session("attendance_scans.{$event->id}", 0),
            'event' => $event->title,
            'resident' => $attendee->full_name,
            'points' => $breakdown['total'],
            'early' => $early,
            'breakdown' => $breakdown,
            'message' => $message,
        ]);
    }

    private function tryParseResidentQr(string $value): User|null
    {
        $payload = json_decode($value, true);
        $id = is_array($payload) ? ($payload['id'] ?? null) : null;
        $signature = is_array($payload) ? ($payload['sig'] ?? null) : null;

        if (! is_string($id) || ! is_string($signature) || ! hash_equals(
            substr(hash_hmac('sha256', $id, config('app.key')), 0, 16),
            $signature
        )) {
            return null;
        }

        return User::where('unique_id', $id)->where('role', 'resident')->first();
    }

    private function residentFromQr(string $value): User|JsonResponse
    {
        $payload = json_decode($value, true);
        $id = is_array($payload) ? ($payload['id'] ?? null) : null;
        $signature = is_array($payload) ? ($payload['sig'] ?? null) : null;

        if (! is_string($id) || ! is_string($signature) || ! hash_equals(
            substr(hash_hmac('sha256', $id, config('app.key')), 0, 16),
            $signature
        )) {
            return $this->fail('Invalid QR code. Resident not found.');
        }

        $resident = User::where('unique_id', $id)->where('role', 'resident')->first();

        return $resident ?: $this->fail('Invalid QR code. Resident not found.');
    }

    private function attendanceWindowError(Announcement $event): ?JsonResponse
    {
        if ($event->qrIsExpired()) {
            return $this->fail('This event QR code expired on ' . $event->qr_expires_at->format('M j, Y g:i A') . '.');
        }

        if ($event->scanningIsOpen()) {
            return null;
        }

        if ($event->scanOpensAt() && now()->lessThan($event->scanOpensAt())) {
            return $this->fail(sprintf(
                'Attendance scanning is not available yet. Scanning opens 2 hours before the event starts at %s.',
                $event->scanOpensAt()->format('M j, Y g:i A')
            ));
        }

        return $this->fail('Attendance scanning has ended for this event.');
    }

    private function canAttendEvent(User $user, Announcement $event): bool
    {
        return $user->belongsToAudience($event->audiences ?: ['public'])
            || EventSubstitution::where('announcement_id', $event->id)
                ->where('substitute_user_id', $user->id)
                ->exists();
    }

    private function isAssignedFacilitator(User $user, Announcement $event): bool
    {
        return $event->facilitators()
            ->where('user_id', $user->id)
            ->whereHas('user', fn ($query) => $query->where('role', 'official')->where('is_verified', true))
            ->exists();
    }

    /** A resident's own attendance record. */
    public function history()
    {
        /** @var User $user */
        $user = Auth::user();

        return view('pages.attendance-history', [
            'attendances' => $user->attendances()->with('announcement')->latest('scanned_at')->paginate(20),
        ]);
    }

    public function summaryPdf(Announcement $announcement, bool $download = true)
    {
        abort_unless($announcement->is_event, 404);

        $stats = app(\App\Http\Controllers\AnnouncementController::class)->statistics($announcement);
        $attendees = $announcement->attendances()->with('user')->orderBy('scanned_at')->get();

        return $this->pdfResponse(
            Pdf::loadView('pdf.announcement-summary', compact('announcement', 'stats', 'attendees')),
            'announcement-summary-' . str($announcement->title)->slug() . '.pdf',
            $download
        );
    }

    public function printSummary(Announcement $announcement)
    {
        return $this->summaryPdf($announcement, false);
    }

    public function attendanceSheetPdf(Announcement $announcement, bool $download = true)
    {
        abort_unless($announcement->is_event, 404);

        $attendees = $announcement->attendances()->with('user')->orderBy('scanned_at')->get();

        return $this->pdfResponse(
            Pdf::loadView('pdf.attendance-sheet', compact('announcement', 'attendees')),
            'attendance-sheet-' . str($announcement->title)->slug() . '.pdf',
            $download
        );
    }

    public function printAttendanceSheet(Announcement $announcement)
    {
        return $this->attendanceSheetPdf($announcement, false);
    }

    private function pdfResponse($pdf, string $filename, bool $download)
    {
        $pdf->setPaper('a4', 'portrait');

        if ($download) {
            return $pdf->download($filename);
        }

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function fail(string $message): JsonResponse
    {
        return response()->json(['ok' => false, 'message' => $message], 422);
    }
}