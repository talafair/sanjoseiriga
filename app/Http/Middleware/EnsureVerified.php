<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->isSuperadmin() || $user->is_verified)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'message' => $user?->isUnverifiedOfficial()
                    ? 'Your official account must be verified by a Barangay Administrator before you can use this feature.'
                    : 'Your resident account must be verified by a Barangay Official before you can use this feature.',
            ], 403);
        }

        $message = $user?->isUnverifiedOfficial()
            ? 'Your official account is unverified. Please ask a Barangay Administrator to verify it before using this feature.'
            : 'Your resident account is unverified. Please ask a Barangay Official to verify it before using this feature.';

        return redirect()->route('account')->with('error', $message);
    }
}