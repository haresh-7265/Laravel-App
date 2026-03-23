<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DiscountService;
class ProductController1 extends Controller
{
    protected $discountService;

    public function __construct(DiscountService $discountService){
        $this->discountService = $discountService;
    }

    public function discountPrice(Request $request){
        $price = $request->query('price');
        $discountPercentage = 20;

        if(!$price){
            return "<h1>Please give price to get discounted price</h1>";
        }
        $discount = $this->discountService->calculateDiscount($price,$discountPercentage);
        return "<h1>Price after discount= $discount</h1>";
    }
}
