<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;

class WaitlistController extends Controller
{
    public function store(Product $product): JsonResponse
    {
        if ($product->stock > 0) {
            return response()->json([
                'status' => 'warning',
                'message' => 'Product is already in stock'], 400);
        }

            $exists = auth()->user()
            ->waitlistProducts()
            ->where('product_id', $product->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'success',
                'message' => 'Already on waitlist'], 409);
        }

        auth()->user()->waitlistProducts()->attach($product);

        return response()->json([
            'status' => 'success',
            'message' => 'Added to waitlist']);
    }

    public function destroy(Product $product): JsonResponse
    {
        $detached = auth()->user()->waitlistProducts()->detach($product->id);

        if ($detached) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Removed from waitlist']);
        }

        return response()->json([
            'status'  => 'info',
            'message' => 'Not on waitlist'], 404);
    }
}
