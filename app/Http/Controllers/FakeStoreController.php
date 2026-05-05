<?php

namespace App\Http\Controllers;

use App\Services\FakeStoreService;

class FakeStoreController extends Controller
{
    public function __construct(
        protected FakeStoreService $fakeStoreService
    ) {
    }

    public function index()
    {
        $products = $this->fakeStoreService->fetchProducts();
        $this->fakeStoreService->postProduct();  // post request with body

        if(request()->expectsJson()){
            return $products;
        }

        return view('fakestore.index', compact('products'));
    }
}