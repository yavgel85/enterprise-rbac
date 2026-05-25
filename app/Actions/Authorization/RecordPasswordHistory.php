<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

final readonly class RecordPasswordHistory
{
    /**
     * Append the new password hash to password_histories and prune the
     * tail so that only the last N rows remain. N is
     * config('rbac.password_history.size'); when N <= 0 the call is a
     * no-op (history disabled).
     */
    public function handle(User $user, string $rawPassword): void
    {
        $size = (int) config('rbac.password_history.size', 0);

        if ($size <= 0) {
            return;
        }

        PasswordHistory::create([
            'user_id' => $user->id,
            'password_hash' => Hash::make($rawPassword),
            'created_at' => now(),
        ]);

        $keepIds = $user->passwordHistories()
            ->orderByDesc('created_at')
            ->limit($size)
            ->pluck('id');

        $user->passwordHistories()
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}
