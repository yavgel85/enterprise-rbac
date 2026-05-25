<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Permission cache TTL (seconds)
    |--------------------------------------------------------------------------
    | How long resolved permissions are cached per user/tenant pair.
    | The cache is also explicitly invalidated whenever a role/permission
    | assignment changes, so the TTL is mostly a safety net.
    */
    'cache_ttl' => env('RBAC_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Forbidden role pairs (separation of duties)
    |--------------------------------------------------------------------------
    | A user must not hold both roles in any given pair simultaneously.
    | Each entry is a tuple [slugA, slugB]. Order does not matter.
    */
    'forbidden_role_pairs' => [
        ['auditor', 'tenant-admin'],
        ['auditor', 'manager'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Business hours (used by time-based policy rules)
    |--------------------------------------------------------------------------
    */
    'business_hours' => [
        'start' => env('RBAC_BUSINESS_HOURS_START', 9),
        'end' => env('RBAC_BUSINESS_HOURS_END', 18),
        'weekdays_only' => env('RBAC_BUSINESS_HOURS_WEEKDAYS_ONLY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default invitation expiry (days)
    |--------------------------------------------------------------------------
    */
    'invitation_ttl_days' => env('RBAC_INVITATION_TTL_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Account lockout
    |--------------------------------------------------------------------------
    | After max_attempts consecutive failed logins for the same email the
    | account is locked for duration_minutes. Admins with the users.unlock
    | permission can release the lock from the UI before the timeout.
    */
    'lockout' => [
        'max_attempts' => (int) env('RBAC_LOCKOUT_MAX_ATTEMPTS', 5),
        'duration_minutes' => (int) env('RBAC_LOCKOUT_DURATION_MINUTES', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password history
    |--------------------------------------------------------------------------
    | When set to N > 0, every password-change action (self, reset, admin
    | override, invitation acceptance) appends a row to password_histories
    | and rejects new passwords that match any of the last N hashes.
    | Set to 0 to disable history checks entirely.
    */
    'password_history' => [
        'size' => (int) env('RBAC_PASSWORD_HISTORY_SIZE', 5),
    ],
];
