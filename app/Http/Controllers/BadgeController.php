<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\UserNotification;
use Illuminate\Validation\Rule;
use RuntimeException;

class BadgeController extends Controller
{
    public function catalog(Request $request)
    {
        $earnedBadges = $request->user()->badges()->get()->keyBy('id');

        return view('pages.badge-catalog', [
            'badges' => Badge::with('announcement')->orderByRaw('CASE WHEN EXISTS (SELECT 1 FROM badge_user WHERE badge_user.badge_id = badges.id AND badge_user.user_id = ?) THEN 0 ELSE 1 END', [$request->user()->id])->latest()->get(),
            'earnedBadges' => $earnedBadges,
        ]);
    }

    public function index()
    {
        return view('pages.badges', [
            'badges' => Badge::with('announcement')->latest()->get(),
            'events' => Announcement::where('is_event', true)->latest('event_start_at')->get(),
            'residents' => User::whereIn('role', ['resident', 'guest'])->orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:255'],
            'points_required' => ['required', 'integer', 'min:0'],
            'category'        => ['required', 'in:attendance,participation,streaks,milestones,competition,games,special'],
            'rarity'          => ['required', 'in:common,uncommon,rare,epic,legendary'],
            'award_method'    => ['required', 'in:automatic,official'],
            'condition_key'   => ['required', Rule::in($this->conditionsForCategory($request->input('category')))],
            'limited_total'   => ['nullable', 'integer', 'min:1'],
            'announcement_id' => ['nullable', 'exists:announcements,id'],
            'recipients'      => ['nullable', 'array'],
            'recipients.*'    => ['integer', 'exists:users,id'],
            'image'           => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ]);

        $recipients = $data['recipients'] ?? [];
        unset($data['recipients']);
        $data['image_path'] = $request->hasFile('image') ? $request->file('image')->store('badges', config('filesystems.uploads_disk', 'public')) : null;
        if ($request->hasFile('image') && ! $data['image_path']) {
            throw new RuntimeException('The badge image could not be saved to the configured upload storage.');
        }
        unset($data['image']);

        $badge = Badge::create($data);
        if ($badge->award_method === 'official' && $recipients) {
            foreach ($recipients as $recipientId) {
                if ($badge->users()->whereKey($recipientId)->exists()) continue;
                $badge->users()->attach($recipientId, ['awarded_by' => $request->user()->id]);
                UserNotification::create(['user_id' => $recipientId, 'title' => 'Badge awarded', 'body' => "You received the {$badge->name} badge.", 'created_by' => $request->user()->id]);
            }
        }

        return back()->with('success', 'Badge added.');
    }

    public function update(Request $request, Badge $badge): RedirectResponse
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:255'],
            'points_required' => ['required', 'integer', 'min:0'],
            'category'        => ['required', 'in:attendance,participation,streaks,milestones,competition,games,special'],
            'rarity'          => ['required', 'in:common,uncommon,rare,epic,legendary'],
            'award_method'    => ['required', 'in:automatic,official'],
            'condition_key'   => ['required', Rule::in($this->conditionsForCategory($request->input('category')))],
            'limited_total'   => ['nullable', 'integer', 'min:1'],
            'announcement_id' => ['nullable', 'exists:announcements,id'],
            'image'           => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ]);

        $oldImagePath = $badge->image_path;
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('badges', config('filesystems.uploads_disk', 'public'));
            if (! $data['image_path']) {
                throw new RuntimeException('The badge image could not be saved to the configured upload storage.');
            }
        }

        unset($data['image']);
        $badge->update($data);
        if ($oldImagePath && isset($data['image_path'])) {
            Storage::disk(config('filesystems.uploads_disk', 'public'))->delete($oldImagePath);
        }

        return back()->with('success', 'Badge updated.');
    }

    public function destroy(Badge $badge): RedirectResponse
    {
        if ($badge->image_path) {
            Storage::disk(config('filesystems.uploads_disk', 'public'))->delete($badge->image_path);
        }

        $badge->delete();

        return back()->with('success', 'Badge removed.');
    }

    private function conditionsForCategory(?string $category): array
    {
        return match ($category) {
            'attendance' => ['first_event', 'event_attendance', 'early_arrival', 'early_bird', 'attendance_count'],
            'participation' => ['participation_count'],
            'streaks' => ['streak'],
            'milestones' => ['attendance_count', 'points'],
            'games' => ['best_player_week', 'best_player_month', 'best_player_all_time'],
            'competition', 'special' => ['manual'],
            default => [],
        };
    }
}