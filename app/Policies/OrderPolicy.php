<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Admin|User|null $user): bool
    {
        if (is_null($user)) {
            return false;
        }

        return $user->can('manage_orders');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Admin|User|null $user, Order $order)
    {
        if (is_null($user)) {
            return Response::denyAsNotFound();
        }

        if ($user->can('manage_orders')) {
            return Response::allow();
        }

        return ($user instanceof User && $user->hasRole('customer') && $order->user_id === $user->id)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Admin|User|null $user): bool
    {
        return $user instanceof User && $user->hasRole('customer');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Admin|User|null $user, Order $order)
    {
        if (is_null($user)) {
            return Response::denyAsNotFound();
        }

        if ($user->can('manage_orders')) {
            return Response::allow();
        }

        return ($user instanceof User && $user->hasRole('customer') && $order->user_id === $user->id)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, Order $order)
    {
        if (is_null($user)) {
            return Response::denyAsNotFound();
        }

        if ($user->can('manage_orders')) {
            return Response::allow();
        }

        return ($user instanceof User && $user->hasRole('customer') && $order->user_id === $user->id)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can cancel the model.
     */
    public function cancel(Admin|User|null $user, Order $order)
    {
        if (is_null($user)) {
            return false;
        }

        if ($user->can('manage_orders')) {
            return true;
        }

        return ($user instanceof User && $user->hasRole('customer') && $order->user_id === $user->id)
            ? true
            : false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Admin|User|null $user, Order $order)
    {
        if (is_null($user)) {
            return Response::denyAsNotFound();
        }

        if ($user->can('manage_orders')) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Admin|User|null $user, Order $order)
    {
        if (is_null($user)) {
            return Response::denyAsNotFound();
        }

        if ($user->can('manage_orders')) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }
}
