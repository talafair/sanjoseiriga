<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOfficial
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isOfficial()) {
            return redirect()
                ->route('announcements')
                ->with('error', 'Only officials can manage that page.');
        }

        if ($user->isUnverifiedOfficial()) {
            return redirect()
                ->route('account')
                ->with('error', 'Your official account is unverified. A Barangay Administrator must verify it before you can access official features.');
        }

        return $next($request);
    }
}
