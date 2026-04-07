<?php

use App\Models\Order;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin.orders', function ($user) {
    return (bool) $user->isAdmin();
});

Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    return Order::where('id', $orderId)
        ->where('user_id', $user->id)
        ->exists();
});