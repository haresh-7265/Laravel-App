<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerIds = User::where('role', 'customer')->pluck('id')->toArray();
        $adminIds = User::where('role', 'admin')->pluck('id')->toArray();
        $productIds = Product::pluck('id')->toArray();

        if (empty($customerIds) || empty($adminIds) || empty($productIds)) {
            $this->command->error('Users and products must be seeded before orders.');
            return;
        }

        DB::transaction(function () use ($customerIds, $adminIds, $productIds) {
            for ($i = 0; $i < 500; $i++) {
                // 1. Create order with placeholder values for total/subtotal
                $order = Order::factory()->create([
                    'user_id' => fake()->randomElement($customerIds),
                    'created_by' => fake()->randomElement($adminIds),
                    'updated_by' => fake()->randomElement($adminIds),
                    'subtotal' => 0,
                    'discount' => 0,
                    'total' => 0,
                ]);

                // 2. Add between 1 and 5 items
                $numItems = fake()->numberBetween(1, 5);
                $orderSubtotal = 0;
                $orderTotal = 0;

                // Pick random unique products for this order
                $selectedProducts = fake()->randomElements($productIds, min($numItems, count($productIds)));

                foreach ($selectedProducts as $productId) {
                    $item = OrderItem::factory()->create([
                        'order_id' => $order->id,
                        'product_id' => $productId,
                    ]);
                    $orderTotal += $item->subtotal;
                    $orderSubtotal += $item->price * $item->quantity;
                }

                // 3. Apply optional discount and update the order totals
                $discount = $orderSubtotal - $orderTotal;

                $order->update([
                    'subtotal' => $orderSubtotal,
                    'discount' => $discount,
                    'total' => $orderTotal,
                ]);
            }
        });
    }
}
