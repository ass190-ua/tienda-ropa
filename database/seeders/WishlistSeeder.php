<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\Product;
use App\Models\WishlistItem;
use Carbon\Carbon;

class WishlistSeeder extends Seeder
{
    public function run(): void
    {
        $wishlistNames = [
            'Favoritos',
            'Ideas de regalo',
            'Para más adelante',
            'Lista de deseos',
        ];

        User::all()->each(function ($user) use ($wishlistNames) {

            $numberOfLists = rand(1, 2);

            for ($i = 0; $i < $numberOfLists; $i++) {
                Wishlist::create([
                    'user_id' => $user->id,
                    'name' => $wishlistNames[array_rand($wishlistNames)],
                ]);
            }
        });
    }
}

class WishlistItemSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();

        Wishlist::all()->each(function ($wishlist) use ($products) {

            $itemsCount = rand(3, 8);

            $products
                ->random($itemsCount)
                ->each(function ($product) use ($wishlist) {

                    WishlistItem::create([
                        'wishlist_id' => $wishlist->id,
                        'product_id'  => $product->id,
                        'added_at'    => Carbon::now()->subDays(rand(1, 30)),
                    ]);
                });
        });
    }
}