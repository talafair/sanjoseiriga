<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class IdCardController extends Controller
{
    /** The resident's own digital ID. */
    public function show()
    {
        $user = Auth::user();

        abort_if($user->isSuperadmin(), 404);
        abort_if(! $user->unique_id, 404, 'This account has no resident ID yet.');

        return view('pages.my-id', [
            'user'  => $user,
            'qrSvg' => $this->qrFor($user),
        ]);
    }

    /** Raw SVG endpoint — used by the "Save to phone" button. */
    public function qr()
    {
        $user = Auth::user();

        abort_if($user->isSuperadmin(), 404);
        abort_if(! $user->unique_id, 404, 'This account has no resident ID yet.');

        return response($this->qrFor($user), 200, ['Content-Type' => 'image/svg+xml']);
    }

    /** Print a resident's existing TalaFair ID card from the official user list. */
    public function printUser(User $user)
    {
        abort_unless($user->role === 'resident', 404);
        abort_if(! $user->unique_id, 404, 'This account has no resident ID yet.');

        return view('pages.my-id', [
            'user' => $user,
            'qrSvg' => $this->qrFor($user),
            'printOnly' => true,
        ]);
    }

    /** Print selected residents' existing cards on supported paper sizes. */
    public function printSelected(Request $request)
    {
        $data = $request->validate([
            'resident_ids' => ['required', 'array', 'min:1', 'max:200'],
            'resident_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'resident')),
            ],
            'paper_size' => ['required', Rule::in(['a4', 'letter', 'long'])],
        ]);

        $residents = User::whereIn('id', $data['resident_ids'])
            ->where('role', 'resident')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (User $user) => [
                'user' => $user,
                'qrSvg' => $this->qrFor($user),
            ]);

        return view('pages.id-cards-print', [
            'cardPages' => $residents->chunk(8),
            'paperSize' => $data['paper_size'],
        ]);
    }

    private function qrFor(User $user): string
    {
        // Signed payload so a screenshot of the QR can still be verified by an official
        $payload = json_encode([
            'id'     => $user->unique_id,
            'name'   => $user->full_name,
            'gender' => $user->gender_label,
            'sig'    => substr(hash_hmac('sha256', $user->unique_id, config('app.key')), 0, 16),
        ]);

        return (string) QrCode::format('svg')
            ->size(220)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($payload);
    }
}