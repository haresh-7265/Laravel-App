<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Product;

class ProductPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before($user, string $ability)
    {
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
    public function view($user, Product $product): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        return $user instanceof Admin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, Product $product): bool
    {
        return $user instanceof Admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, Product $product): bool
    {
        return $user instanceof Admin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore($user, Product $product): bool
    {
        return $user instanceof Admin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete($user, Product $product): bool
    {
        return $user instanceof Admin;
    }
}
