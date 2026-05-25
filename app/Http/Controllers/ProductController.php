<?php

namespace App\Http\Controllers;

use App\Events\Product\ProductViewed;
use App\Facades\Products;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\RecentlyViewedService;
use Arr;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request, RecentlyViewedService $recentlyViewedService)
    {
        $recentlyViewed = $recentlyViewedService->get(auth()?->id(), session()->getId());
        $page_title = 'Product-list';
        $filters = Arr::only($request->query(), [
            'min_price',
            'max_price',
            'categories',
            'in_stock',
            'on_sale',
            'sort',
            'q'
        ]);
        $user = $request->user();
        $cursor = $request->input('cursor');
        $perPage = min((int) $request->input('perPage', 20), 100);
        $hasFilters = collect($filters)->hasAny(['min_price', 'max_price', 'categories', 'in_stock', 'on_sale', 'sort']);

        extract(Products::getHomepageProducts(
            filters: $filters,
            role: $user?->role ?? 'customer',
            cursor: $cursor,
            perPage: $perPage
            ));

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
        $data = Arr::only($request->all(), [
            'name',
            'slug',
            'price',
            'discount_price',
            'description',
            'stock',
            'category_id',
            'is_active',
            'tags',
        ]);
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
        $data = Arr::except($request->all(), [
            '_token',
            '_method',
            'image',
        ]);
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
    public function exportCsv()
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Product Id', 'Name', 'Price', 'Stock', 'Category', 'Avg Rating']);

            try {
                Product::select(
                    'products.id',
                    'products.name',
                    'products.price',
                    'products.stock',
                    'products.avg_rating',
                    'categories.name as category_name'
                )
                    ->join('categories', 'categories.id', '=', 'products.category_id')
                    ->orderBy('categories.name')
                    ->lazy(500)
                    ->each(function ($product) use ($handle) {
                        fputcsv($handle, [
                            $product->id,
                            $product->name,
                            $product->price,
                            $product->stock,
                            $product->category_name,
                            $product->avg_rating,
                        ]);
                    });
            } catch (\Exception $e) {
                \Log::channel('product')->error('CSV Export failed: '.$e->getMessage());
            } finally {
                fclose($handle);
            }

        }, 'products-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Display paginated list of trashed (soft-deleted) products.
     */
    public function trashed(Request $request)
    {
        $page = request()->input('page');
        $perPage = min((int) $request->input('perPage', 20), 100);
        $products = Products::getTrashedProducts($page, $perPage);

        return view('products.trashed', compact('products'));
    }

    /**
     * Restore a soft-deleted product.
     */
    public function restore(int $id)
    {
        $product = Products::restoreProduct($id);

        return redirect()->route('products.trashed')
            ->with('success', "Product \"{$product->name}\" restored successfully!");
    }

    /**
     * Permanently delete a trashed product.
     */
    public function forceDelete(int $id)
    {
        $product = Products::forceDeleteProduct($id);

        return redirect()->route('products.trashed')
            ->with('success', "Product \"{$product->name}\" permanently deleted!");
    }
}
