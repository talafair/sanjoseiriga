<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;
use App\Models\EventSubstitution;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $rank = User::where('points', '>', $user->points)->count() + 1;

        $recentSpins = $user->spins()->latest()->take(6)->get();
        $householdHead = $user->is_head_of_family ? $user : $user->headOfFamily;
        $householdMembers = collect();

        if (! $householdHead) {
            $householdHead = User::where('is_head_of_family', true)
                ->where(function ($query) use ($user) {
                    $query->whereRaw('LOWER(TRIM(head_of_family_name)) = ?', [strtolower(trim($user->full_name))])
                        ->orWhere(function ($addressQuery) use ($user) {
                            $addressQuery->whereRaw('LOWER(TRIM(house_no)) = ?', [strtolower(trim($user->house_no))])
                                ->whereRaw('LOWER(TRIM(street)) = ?', [strtolower(trim($user->street))])
                                ->when($user->zone !== null, fn ($q) => $q->whereRaw('LOWER(TRIM(zone)) = ?', [strtolower(trim($user->zone))]));
                        });
                })
                ->first();
        }

        if ($householdHead) {
            $householdMembers = User::query()
                ->whereKeyNot($householdHead->id)
                ->where('is_head_of_family', false)
                ->where(function ($query) use ($householdHead) {
                    $query->where('head_of_family_id', $householdHead->id)
                        ->orWhereRaw('LOWER(TRIM(head_of_family_name)) = ?', [strtolower(trim($householdHead->full_name))])
                        ->orWhere(function ($addressQuery) use ($householdHead) {
                            $addressQuery->whereRaw('LOWER(TRIM(house_no)) = ?', [strtolower(trim($householdHead->house_no))])
                                ->whereRaw('LOWER(TRIM(street)) = ?', [strtolower(trim($householdHead->street))]);

                            if ($householdHead->zone !== null) {
                                $addressQuery->whereRaw('LOWER(TRIM(zone)) = ?', [strtolower(trim($householdHead->zone))]);
                            }
                        });
                })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        return view('pages.account', [
            'user' => $user,
            'rank' => $rank,
            'recentSpins' => $recentSpins,
            'totalSpins' => $user->spins()->count(),
            'recentPointTransactions' => $user->pointTransactions()->with('announcement')->latest()->take(10)->get(),
            'householdMembers' => $householdMembers,
            'householdHead' => $householdHead,
        ]);
    }

    public function assignSubstitute(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->is_head_of_family, 403);

        $data = $request->validate([
            'announcement_id' => ['required', 'exists:announcements,id'],
            'substitute_user_id' => ['required', 'exists:users,id'],
        ]);

        $event = Announcement::upcoming()->whereKey($data['announcement_id'])->firstOrFail();
        $member = User::whereKey($data['substitute_user_id'])
            ->where('is_head_of_family', false)
            ->where(function ($query) use ($user) {
                $query->where('head_of_family_id', $user->id)
                    ->orWhereRaw('LOWER(TRIM(head_of_family_name)) = ?', [strtolower(trim($user->full_name))])
                    ->orWhere(function ($addressQuery) use ($user) {
                        $addressQuery->whereRaw('LOWER(TRIM(house_no)) = ?', [strtolower(trim($user->house_no))])
                            ->whereRaw('LOWER(TRIM(street)) = ?', [strtolower(trim($user->street))])
                            ->when($user->zone !== null, fn ($q) => $q->whereRaw('LOWER(TRIM(zone)) = ?', [strtolower(trim($user->zone))]));
                    });
            })->firstOrFail();

        EventSubstitution::updateOrCreate(
            ['announcement_id' => $event->id, 'family_head_id' => $user->id],
            ['substitute_user_id' => $member->id]
        );

        UserNotification::create([
            'user_id' => $member->id,
            'announcement_id' => $event->id,
            'title' => 'You were assigned as a substitute',
            'body' => "You were assigned to attend {$event->title} on behalf of {$user->full_name}.",
            'created_by' => $user->id,
        ]);

        return back()->with('success', "{$member->full_name} is assigned to attend {$event->title} on your behalf.");
    }
}
