<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProfilePolicy
{
    // ✅ accept any authenticatable — not type hinted to one model
    public function view(Model $authUser, Model $targetUser): bool
    {
        // admin can view any profile
        if ($authUser instanceof Admin) {
            return true;
        }

        // customer can only view own profile
        return $authUser->id === $targetUser->id
            && $authUser::class === $targetUser::class;
    }

    public function update(Model $authUser, Model $targetUser): bool
    {
        // admin can update any
        if ($authUser instanceof Admin) {
            return true;
        }

        // customer own profile only
        return $authUser->id === $targetUser->id
            && $authUser::class === $targetUser::class;
    }

    public function delete(Model $authUser, Model $targetUser): bool
    {
        // admin can delete any customer
        if ($authUser instanceof Admin) {
            return $targetUser instanceof User;
        }

        // customer can only delete own
        return $authUser->id === $targetUser->id;
    }
}