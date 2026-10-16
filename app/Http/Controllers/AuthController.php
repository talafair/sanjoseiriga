<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->boolean('another')) {
            $request->session()->forget('pending_saved_login');
        }

        return view('pages.login', [
            'targetAccountSelected' => $request->session()->has('pending_saved_login'),
            'pendingSavedLogin' => $request->session()->get('pending_saved_login'),
            'saveAccountRequested' => $request->session()->pull('save_account_after_login', false),
        ]);
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

        $targetLogin = $request->session()->get('pending_saved_login');
        $matchesSelectedAccount = $targetLogin === null || (
            $user
            && is_string($targetLogin)
            && hash_equals($targetLogin, $login)
            && hash_equals($targetLogin, $user->username)
        );

        if ($user && $matchesSelectedAccount && Auth::attempt(
            ['id' => $user->id, 'password' => $credentials['password']],
            $request->boolean('remember')
        )) {
            $request->session()->regenerate();
            $request->session()->forget(['pending_saved_login', 'save_account_after_login']);

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

            if ($request->boolean('save_account')) {
                $request->session()->flash('savedAccountProfile', $this->savedAccountProfile($user));
            }

            return redirect()->intended(route('home'));
        }

        return back()
            ->withErrors(['login' => 'The provided credentials do not match our records.'])
            ->onlyInput('login', 'save_account');
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

    public function switchAccount(Request $request)
    {
        $validated = $request->validate([
            'saved_login' => ['required', 'string', 'max:30', 'alpha_dash'],
        ]);

        $account = User::where('username', $validated['saved_login'])->first();
        if ($account
            && in_array($account->role, array_keys(User::ROLE_LABELS), true)
            && filled($account->password)) {
            $request->attributes->set('pending_saved_login', $account->username);
        } else {
            $request->attributes->set('pending_saved_login', '');
        }

        return $this->logout($request);
    }

    public function addAccount(Request $request)
    {
        $request->attributes->set('save_account_after_login', true);

        return $this->logout($request);
    }

    public function logout(Request $request)
    {
        $targetLogin = $request->attributes->get('pending_saved_login');
        $saveAccount = $request->attributes->get('save_account_after_login', false);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($targetLogin !== null) {
            $request->session()->put('pending_saved_login', $targetLogin);
        }

        if ($saveAccount) {
            $request->session()->put('save_account_after_login', true);
        }

        return redirect()->route('login')
            ->withHeaders(['Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0']);
    }

    private function savedAccountProfile(User $user): array
    {
        return [
            'username' => $user->username,
            'name' => $user->full_name,
            'avatar' => $user->avatar_url,
            'role' => User::ROLE_LABELS[$user->role] ?? 'Account',
        ];
    }
}