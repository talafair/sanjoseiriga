<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Officials only: the home page background image. */
class AppearanceController extends Controller
{
    public function edit()
    {
        return view('pages.appearance', [
            'backgroundUrl' => Setting::url('home_background'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'background' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $disk = config('filesystems.uploads_disk', 'public');
        $newPath = $request->file('background')->store('backgrounds', $disk);
        if (! $newPath) {
            throw new RuntimeException('The home background could not be saved to the configured upload storage.');
        }

        $old = Setting::get('home_background');
        Setting::put('home_background', $newPath);
        if ($old) {
            Storage::disk($disk)->delete($old);
        }

        return back()->with('success', 'Background updated.');
    }

    public function destroy(): RedirectResponse
    {
        if ($old = Setting::get('home_background')) {
            Storage::disk(config('filesystems.uploads_disk', 'public'))->delete($old);
        }

        Setting::put('home_background', null);

        return back()->with('success', 'Background removed.');
    }
}