<?php

namespace App\Http\Controllers;

use App\Events\Product\ProductViewed;
use App\Exports\ProductsExport;
use App\Facades\Products;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\RecentlyViewedService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function index(Request $request, RecentlyViewedService $recentlyViewedService)
    {
        $recentlyViewed = $recentlyViewedService->get(auth()?->id(), session()->getId());
        $page_title = 'Product-list';
        $filters = $request->query();
        $hasFilters = collect($filters)->hasAny(['min_price', 'max_price', 'categories', 'in_stock', 'on_sale', 'sort']);

        extract(Products::getHomepageProducts($request->input('page', 1), $filters));

        if ($request->acceptsHtml()) {
            return view('products.index', compact(
                'products',
                'page_title',
                'recentlyViewed',
                'hasFilters',
                'featured',
                'newArrivals',
                'onSale'
            ));
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
        $data = $request->except(['image', '_token']);
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
        ProductViewed::dispatch($product, auth()->user(), session()->id());
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
        $data = $request->except(['image', '_token', '_method']);
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

    // export csv file
    public function exportCsv(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0|gte:min_price',
            'min_stock' => 'nullable|integer|min:0',
            'max_stock' => 'nullable|integer|min:0',
        ]);

        $filename = 'products_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return Excel::download(
            new ProductsExport(
                categoryId: $request->category_id,
                minPrice: $request->min_price,
                maxPrice: $request->max_price,
                minStock: $request->min_stock,
                maxStock: $request->max_stock,
            ),
            $filename,
            \Maatwebsite\Excel\Excel::CSV
        );
    }
}
