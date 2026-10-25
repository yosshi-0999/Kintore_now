<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * 記録・継続日数などを見られるか（アカウント名は常に公開だが一覧には載せない）。
     * 本人、または view_permissions に行がある場合のみ。
     */
    public function viewRecords(User $viewer, User $owner): bool
    {
        return $viewer->is($owner)
            || $owner->viewers()->whereKey($viewer->id)->exists();
    }
}
