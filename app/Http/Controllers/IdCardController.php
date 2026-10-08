<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
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