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

Broadcast::channel('store.browsing', function ($user) {
    // return user data to populate presence channel
    return [
        'id'   => $user->id,
        'name' => $user->name,
        'role' => $user->role,
        'page' => parse_url(request()->headers->get('referer', '/'), PHP_URL_PATH),
    ];
});