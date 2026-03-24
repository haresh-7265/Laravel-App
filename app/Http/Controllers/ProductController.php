<?php

namespace App\Http\Controllers;

use App\Facades\Products;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;

class ProductController extends Controller
{
    // role middleware on specific methods
    public static function middleware(): array
    {
        return [
            new Middleware('role:admin', except: [
                'index',
                'show',
                'search'
            ]),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $products = Products::getAll();
        $total_products = count($products);
        $page_title = 'Product-list';
        if ($request->acceptsHtml()) {
            return view('products.index', compact('products', 'total_products', 'page_title'));
        }
        return response()->success($products, 'All products');
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
    public function store(StoreProductRequest $request)
    {
        $data = $request->except(['image','_token']);
        $image = $request->file('image');

        Products::create($data, $image);
        session()->flash('success', 'Product created successfully');
        return redirect()->route('products.index');
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
    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->except(['image','_token','_method']);
        $image = $request->file('image');

        Products::update($product, $data, $image);

        return redirect()->route('products.index')
            ->with('success', 'Product updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        Products::delete($product);

        return redirect()->route('products.index')
            ->with('success', 'Product deleted!');
    }

    public function search(Request $request)
    {
        $filters = $request->only(['category', 'price']);

        $products = Products::search($filters);

        return $products;
    }
}
