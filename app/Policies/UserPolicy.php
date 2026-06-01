<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(Admin|User|null $currentUser, User $model): bool
    {
        if(is_null($currentUser)){
            return false;
        }
        if($currentUser->hasRole('admin')){
            return true;
        }
        
        return $currentUser->can('manage_users') || $currentUser->id === $model->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Admin|User|null $currentUser): bool
    {
        if(is_null($currentUser)){
            return false;
        }
        
        return $currentUser->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Admin|User|null $currentUser, User $model): bool
    {
        if(is_null($currentUser)){
            return false;
        }
        if($currentUser->hasRole('admin')){
            return true;
        }
        
        return $currentUser->can('manage_users') || $currentUser->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Admin|User|null $currentUser, User $model): bool
    {
        if(is_null($currentUser)){
            return false;
        }
        if($currentUser->hasRole('admin')){
            return true;
        }
        
        return $currentUser->can('manage_users') || $currentUser->id === $model->id;
    }

    public function forceDelete(Admin|User|null $currentUser, User $model): bool
    {
        if(is_null($currentUser)){
            return false;
        }
        
        return $currentUser->hasRole('admin') || $currentUser->id === $model->id;
    }

    public function restore(Admin|User|null $currentUser, User $model): bool
    {
        if(is_null($currentUser)){
            return false;
        }
        
        return $currentUser->hasRole('admin');
    }
}
