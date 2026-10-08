<?php

namespace App\Http\Controllers;

use App\Models\SpinHistory;
use App\Models\Badge;
use App\Models\User;
use App\Models\Announcement;
use App\Models\Setting;

class HomeController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $now = now();
        $visibleAnnouncements = Announcement::latest('created_at')->get()
            ->filter(fn (Announcement $announcement) => $user->isOfficial()
                || $user->belongsToAudience($announcement->audiences ?: ['public']));
        $activeAnnouncements = $visibleAnnouncements
            ->filter(fn (Announcement $announcement) => ! $announcement->is_event
                || ($announcement->scanClosesAt()?->greaterThanOrEqualTo($now) ?? false))
            ->values();
        $featured = $activeAnnouncements->first(fn (Announcement $announcement) => $announcement->is_featured);
        $banners = $activeAnnouncements
            ->reject(fn (Announcement $announcement) => $featured && $announcement->is($featured))
            ->sortByDesc('is_event')
            ->values();
        $displayBanners = $featured ? collect([$featured])->concat($banners) : $banners;
        $scanNow = $activeAnnouncements
            ->filter(fn (Announcement $announcement) => $announcement->scanningIsOpen($now))
            ->count();

        return view('pages.home', [
            'topPlayers' => User::orderByDesc('points')->orderBy('created_at')->take(5)->get(),
            'recentWinners' => SpinHistory::with('user')
                ->where('prize_type', '!=', 'none')
                ->latest()
                ->take(5)
                ->get(),
            'newMembers' => User::latest()->take(5)->get(),
            'badges' => Badge::latest()->take(4)->get(),
            'announcements' => $activeAnnouncements->take(4),
            'background' => Setting::url('home_background'),
            'featured' => $featured,
            'banners' => $banners,
            'displayBanners' => $displayBanners,
            'scanNow' => $scanNow,
            'earnedBadgeIds' => $user->badges()->pluck('badges.id'),
        ]);
    }
}
