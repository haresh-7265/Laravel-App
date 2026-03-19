<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController1 extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::all();
        return view('products.index')->with('products', $products);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required'],
            'price' => ['required', 'numeric'],
            'category' => ['required'],
            'description' => ['max:500']
        ]);

        Product::create($validated);
        return redirect()->route('products.index')->with('success', 'Product created!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return view('products.show')->with('product', $product);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        return view('products.edit')->with('product', $product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $data = [
            'name' => $request->string('name', null),
            'price' => $request->float('price', 0)
        ];

        $product->update($data);

        return redirect()->route('products.index')
            ->with('success', 'Product updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted!');
    }

    public function search(Request $request)
    {
        $category = $request->query('category', null);
        $price = $request->query('price', null);

        $products = Product::query()
            ->when($category, fn($q) => $q->where('category', $category))
            ->when($price, fn($q) => $q->where('price', $price))
            ->get()
            ->toArray();

        return $products;
    }
}
