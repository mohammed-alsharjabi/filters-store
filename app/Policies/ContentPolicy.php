<?php

namespace App\Policies;

use App\Models\User;

class ContentPolicy
{
    public function manage(User $user): bool
    {
        return $user->is_admin;
    }

    public function viewLeads(User $user): bool
    {
        return $user->is_admin;
    }
}
