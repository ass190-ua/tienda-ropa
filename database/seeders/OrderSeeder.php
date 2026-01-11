<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderLine;
use App\Models\Coupon;


class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            Order::STATUS_PENDING,
            Order::STATUS_PAID,
            Order::STATUS_SHIPPED,
            Order::STATUS_CANCELLED,
        ];

        
        User::all()->each(function ($user) use ($statuses) {
            $coupons = Coupon::where('is_active', true)->get();

            $addresses = $user->addresses;
            if ($addresses->isEmpty()) {
                return; // seguridad extra
            }
            $ordersCount = rand(0, 3);

            for ($i = 0; $i < $ordersCount; $i++) {
                Order::create([
                    'user_id' => $user->id,
                    'address_id' => $addresses->random()->id,
                    'coupon_id' => rand(0,1) && $coupons->isNotEmpty()
                        ? $coupons->random()->id
                        : null,
                    'subtotal' => 0,
                    'discount_total' => 0,
                    'coupon_discount_total' => 0,
                    'status' => $statuses[array_rand($statuses)],
                ]);
            }
        });
    }
}

class OrderLineSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();

        Order::all()->each(function ($order) use ($products) {

            $linesCount = rand(1, 5);
            $subtotal = 0;

            $products
                ->random($linesCount)
                ->each(function ($product) use ($order, &$subtotal) {

                    $quantity = rand(1, 3);
                    $unitPrice = $product->price;
                    $lineTotal = $unitPrice * $quantity;

                    OrderLine::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ]);

                    $subtotal += $lineTotal;
                });

            // Actualizar el pedido con el subtotal real
            $order->update([
                'subtotal' => $subtotal,
                'discount_total' => rand(0, 1) ? rand(0, intval($subtotal * 0.1)) : 0,
                'coupon_discount_total' => 0,
            ]);
        });
    }
}