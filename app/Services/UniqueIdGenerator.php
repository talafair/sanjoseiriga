<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Builds resident IDs as Z{zone}-{yy}{9-digit sequence} and guest IDs as
 * GZ{zone}-{yy}{9-digit sequence}, keeping guest IDs separate from resident IDs.
 */
class UniqueIdGenerator
{
    public static function for(User $user): string
    {
        $zone = preg_replace('/[^0-9A-Za-z]/', '', (string) $user->zone) ?: '0';
        $yy   = $user->created_at?->format('y') ?? now()->format('y');

        $idPrefix = $user->isGuest() ? 'GZ' : 'Z';

        return DB::transaction(function () use ($zone, $yy, $idPrefix) {
            $prefix = "{$idPrefix}{$zone}-{$yy}";

            $last = User::where('unique_id', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('unique_id')
                ->value('unique_id');

            $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

            return $prefix . str_pad((string) $next, 9, '0', STR_PAD_LEFT);
        });
    }
}

//new file 08/17/2026