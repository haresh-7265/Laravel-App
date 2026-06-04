<?php

use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Redis;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('App.Models.Admin.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
}, ['guards' => ['admin']]);

Broadcast::channel('admin.orders', function ($user) {
    return (bool) $user?->isAdmin();
}, ['guards' => ['admin']]);

Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    return Order::where('id', $orderId)
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('store.browsing', function ($user) {
    // return user data to populate presence channel
    if ($user instanceof User) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'joinedToday' => (int) Cache::remember('browse.today:'.now()->toDateString(), 60, fn () => Redis::scard('browse.today:'.now()->toDateString())),
            'page' => parse_url(request()->headers->get('referer', '/'), PHP_URL_PATH),
        ];
    }
    if ($user instanceof Admin) {
        return [
            'id' => 'admin-'.$user->id,
            'name' => $user->name,
            'is_admin' => true,
        ];
    }

    return false;
}, ['guards' => ['admin', 'web']]);
