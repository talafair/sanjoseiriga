<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVerified
{
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        $user = $request->user();

        if ($user && (
            $user->isSuperadmin()
            || ($user->isGuest() && $user->is_verified && in_array('verified_guest', $allowedRoles, true))
            || in_array($user->role, $allowedRoles, true)
            || ($user->is_verified && ! $user->isGuest())
        )) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'message' => $user?->isGuest()
                    ? ($user->is_verified
                        ? 'This feature is not available to guest accounts.'
                        : 'Your guest account must be verified before you can access this feature.')
                    : ($user?->isUnverifiedOfficial()
                        ? 'Your official account must be verified by a Barangay Administrator before you can use this feature.'
                        : 'Your resident account must be verified by a Barangay Official before you can use this feature.'),
            ], 403);
        }

        $message = $user?->isGuest()
            ? ($user->is_verified
                ? 'This feature is not available to guest accounts.'
                : 'Your guest account must be verified before you can access this feature.')
            : ($user?->isUnverifiedOfficial()
                ? 'Your official account is unverified. Please ask a Barangay Administrator to verify it before using this feature.'
                : 'Your resident account is unverified. Please ask a Barangay Official to verify it before using this feature.');

        return redirect()->route('account')->with('error', $message);
    }
}