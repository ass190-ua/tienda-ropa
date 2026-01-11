<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Cart;
use App\Models\Product;
use App\Models\CartItem;

class CartSeeder extends Seeder
{
    public function run(): void
    {
        User::all()->each(function ($user) {
            Cart::create([
                'user_id' => $user->id,
            ]);
        });
    }
}

class CartItemSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();

        Cart::all()->each(function ($cart) use ($products) {

            // Número realista de productos en un carrito
            $itemsCount = rand(2, 5);

            $products
                ->random($itemsCount)
                ->each(function ($product) use ($cart) {

                    $quantity = rand(1, 3);
                    $unitPrice = $product->price;

                    CartItem::create([
                        'cart_id'    => $cart->id,
                        'product_id' => $product->id,
                        'quantity'   => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $unitPrice * $quantity,
                    ]);
                });
        });
    }
}