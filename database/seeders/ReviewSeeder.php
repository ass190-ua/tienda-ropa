<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Review;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();

        User::all()->each(function ($user) use ($products) {

            // No todos los usuarios escriben reviews
            if (rand(0,1) === 0) {
                return;
            }

            $reviewsCount = rand(1, 5);

            $products
                ->random($reviewsCount)
                ->each(function ($product) use ($user) {

                    Review::create([
                        'user_id' => $user->id,
                        'product_id' => $product->id,
                        'rating' => rand(3, 5),
                        'comment' => fake()->sentence(rand(8, 15)),
                        'status' => rand(0,1)
                            ? Review::STATUS_APPROVED
                            : Review::STATUS_PENDING,
                    ]);
                });
        });
    }
}
