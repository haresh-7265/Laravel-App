<?php

namespace App\Http\Controllers;

use App\Services\DiscountService;
use Illuminate\Http\Request;

class DependencyController extends Controller
{
    public function __construct(private DiscountService $discountService)// service dependency
    {
    }

    public function index(Request $request)// request dependency
    {
        $data = [
            'inputs' => $request->all(),
            'discount_price' => $this->discountService->calculateDiscount(200, 10)
        ];

        return $data;
    }
}
