<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Hash;

final readonly class AssertPasswordNotReused
{
    /**
     * Throws DomainException if the candidate password matches any of the
     * last N hashes recorded in password_histories OR the user's current
     * password. N is config('rbac.password_history.size'); when N <= 0
     * the check is skipped entirely.
     */
    public function handle(User $user, string $candidatePassword): void
    {
        $size = (int) config('rbac.password_history.size', 0);

        if ($size <= 0) {
            return;
        }

        if ($user->password !== null && Hash::check($candidatePassword, $user->password)) {
            throw new DomainException(
                'The new password must differ from the current one.',
            );
        }

        $recentHashes = $user->passwordHistories()
            ->orderByDesc('created_at')
            ->limit($size)
            ->pluck('password_hash');

        foreach ($recentHashes as $hash) {
            if (Hash::check($candidatePassword, $hash)) {
                throw new DomainException(
                    "The new password must differ from your last {$size} passwords.",
                );
            }
        }
    }
}
