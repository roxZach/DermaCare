<?php

namespace App\Support;

use App\Models\Patient;
use App\Models\User;

/**
 * Small helpers for record-level access rules.
 */
class Access
{
    /** Staff roles that may open any patient's clinical record. */
    public const CLINICAL_STAFF = ['dermatologist', 'admin'];

    public static function canViewPatient(?User $user, Patient $patient): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->hasRole(...self::CLINICAL_STAFF)) {
            return true;
        }
        if ($user->hasRole('receptionist', 'lab')) {
            return true; // basic details only; views decide what to show
        }
        return $user->hasRole('patient') && $user->patient && $user->patient->id === $patient->id;
    }

    public static function authorizePatient(Patient $patient): void
    {
        abort_unless(self::canViewPatient(auth()->user(), $patient), 403);
    }

    /** Notify every active user with the given role(s). */
    public static function notifyRole(array|string $roles, string $title, ?string $message = null, ?string $link = null): void
    {
        User::whereIn('role', (array) $roles)->where('is_active', true)->pluck('id')
            ->each(fn ($id) => \App\Models\Alert::send($id, $title, $message, $link));
    }
}
