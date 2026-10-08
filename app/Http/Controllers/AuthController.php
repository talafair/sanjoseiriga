<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('pages.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($credentials['login']);
        $user = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $login)->first()
            : $this->findByPhoneOrUsername($login);

        if ($user && Auth::attempt(
            ['id' => $user->id, 'password' => $credentials['password']],
            $request->boolean('remember')
        )) {
            $request->session()->regenerate();

            $user = Auth::user();
            AuditLog::create([
                'user_id' => $user->id,
                'actor_unique_id' => $user->unique_id,
                'action' => 'login',
                'auditable_type' => get_class($user),
                'auditable_id' => $user->id,
                'ip_address' => $request->ip(),
                'duration_ms' => $request->attributes->get('started_at')
                    ? (int) round((microtime(true) - $request->attributes->get('started_at')) * 1000)
                    : null,
            ]);

            return redirect()->intended(route('home'));
        }

        return back()
            ->withErrors(['login' => 'The provided credentials do not match our records.'])
            ->onlyInput('login');
    }

    private function phoneCandidates(string $phone): array
    {
        $digits = preg_replace('/\D+/', '', $phone);
        $candidates = [$phone, $digits];

        if (str_starts_with($digits, '0')) {
            $international = '63' . substr($digits, 1);
            $candidates[] = $international;
            $candidates[] = '+' . $international;
        } elseif (str_starts_with($digits, '63')) {
            $local = '0' . substr($digits, 2);
            $candidates[] = $local;
            $candidates[] = '+' . $digits;
        }

        return array_values(array_unique(array_map(
            fn (string $candidate): string => preg_replace('/\D+/', '', $candidate),
            $candidates
        )));
    }

    private function findByPhoneOrUsername(string $login): ?User
    {
        $phoneCandidates = $this->phoneCandidates($login);
        $phoneUser = User::whereRaw(
            "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(contact_number, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') IN (" . implode(',', array_fill(0, count($phoneCandidates), '?')) . ')',
            $phoneCandidates
        )->first();

        return $phoneUser ?: User::where('username', $login)->first();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}