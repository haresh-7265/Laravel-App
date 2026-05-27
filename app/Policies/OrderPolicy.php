<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user): bool
    {
        return $user instanceof Admin || $user instanceof User;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, Order $order): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $user instanceof User && $order->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        return $user instanceof User;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, Order $order): bool
    {
        return $user instanceof Admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, Order $order): bool
    {
        return $user instanceof Admin;
    }

    /**
     * Determine whether the user can cancel the model.
     */
    public function cancel($user, Order $order): bool
    {
        if ($user instanceof Admin) {
            return true;
        }
        return $user instanceof User && $order->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore($user, Order $order): bool
    {
        return $user instanceof Admin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete($user, Order $order): bool
    {
        return $user instanceof Admin;
    }
}
