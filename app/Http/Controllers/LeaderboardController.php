<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $ranking = $request->string('ranking')->toString() === 'family' ? 'family' : 'individual';
        $players = User::with('badges')
            ->orderByDesc('points')
            ->orderBy('created_at')
            ->get();

        $households = $this->households();
        $rankings = $ranking === 'family' ? $households : $players;
        $topScore = max(1, (int) ($ranking === 'family'
            ? ($households->first()['points'] ?? 0)
            : ($players->first()->points ?? 0)));

        return view('pages.leaderboard', [
            'ranking' => $ranking,
            'podium' => $players->take(3),
            'rest' => $players->slice(3),
            'players' => $players,
            'households' => $households,
            'rankings' => $rankings,
            'topScore' => $topScore,
        ]);
    }

    private function households(): Collection
    {
        return User::query()
            ->familyHeads()
            ->with('badges')
            ->get()
            ->map(function (User $head) {
                $members = User::query()
                    ->whereKeyNot($head->id)
                    ->where('is_head_of_family', false)
                    ->where(function ($query) use ($head) {
                        $query->where('head_of_family_id', $head->id)
                            ->orWhereRaw('LOWER(TRIM(head_of_family_name)) = ?', [strtolower(trim($head->full_name))])
                            ->orWhere(function ($addressQuery) use ($head) {
                                $addressQuery->whereRaw('LOWER(TRIM(house_no)) = ?', [strtolower(trim($head->house_no))])
                                    ->whereRaw('LOWER(TRIM(street)) = ?', [strtolower(trim($head->street))]);

                                if ($head->zone !== null) {
                                    $addressQuery->whereRaw('LOWER(TRIM(zone)) = ?', [strtolower(trim($head->zone))]);
                                }
                            });
                    })
                    ->orderBy('last_name')
                    ->orderBy('first_name')
                    ->get();

                $members = collect([$head])->merge($members);

                return [
                    'head' => $head,
                    'members' => $members,
                    'points' => (int) $members->sum('points'),
                ];
            })
            ->sortByDesc('points')
            ->values();
    }
}
