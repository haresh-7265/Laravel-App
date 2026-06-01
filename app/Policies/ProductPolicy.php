<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User|Admin|null $user, string $ability)
    {
        $skip = ['waitlist'];

        if (in_array($ability, $skip)) {
            return null;
        }

        if ($user instanceof Admin) {
            return true;
        }
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User|Admin|null $user, Product $product): bool
    {
        if (is_null($user)) {
            return true;
        }

        return $user->can('view_products');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User|Admin|null $user): bool
    {
        if (is_null($user)) {
            return false;
        }

        return $user->can('manage_products');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User|Admin|null $user, Product $product): bool
    {
        if (is_null($user)) {
            return false;
        }

        return $user->can('manage_products');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User|Admin|null $user, Product $product): bool
    {
        if (is_null($user)) {
            return false;
        }

        return $user->can('manage_products');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User|Admin|null $user, Product $product): bool
    {
        if (is_null($user)) {
            return false;
        }

        return $user->can('manage_products');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User|Admin|null $user, Product $product): bool
    {
        if (is_null($user)) {
            return false;
        }

        return $user->can('manage_products');
    }

    public function waitlist(User|Admin|null $user, Product $product): bool
    {
        if (is_null($user)) {
            return false;
        }
        if ($user instanceof Admin) {
            return false;
        }

        return $user->hasRole('customer')
            && $product->stock <= 0;
    }
}
