<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view($currentUser, User $model): bool
    {
        return $currentUser instanceof Admin || ($currentUser instanceof User && $currentUser->id === $model->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($currentUser): bool
    {
        return $currentUser instanceof Admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($currentUser, User $model): bool
    {
        return $currentUser instanceof Admin || ($currentUser instanceof User && $currentUser->id === $model->id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($currentUser, User $model): bool
    {
        return $currentUser instanceof Admin || ($currentUser instanceof User && $currentUser->id === $model->id);
    }
}
