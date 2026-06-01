<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\User;

class CartPolicy
{
    public function view(User|Admin|null $user): bool
    {
        if ($user instanceof Admin) {
            return false;
        } 

        return is_null($user) || $user->hasRole('customer');                               
    }

    public function checkout(User|Admin|null $user): bool
    {
        if (is_null($user)) {
            return false;
        }  
        if ($user instanceof Admin) {
            return false;
        }  

        return $user->hasRole('customer');
    }
}
