<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;

class DeviceController extends Controller
{
    public function index()
    {
        $tokens = current_user()->tokens()
            ->latest()
            ->get();

        return view('customer.devices', compact('tokens'));
    }

    public function revoke($id)
    {
        current_user()->tokens()->where('id', $id)->delete();

        return back()->with('success', 'Device removed');
    }
}
