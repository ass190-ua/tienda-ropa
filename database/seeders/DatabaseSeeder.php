<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            AdminUserSeeder::class,

            AttributeSeeder::class,
            AttributeValueSeeder::class,

            ProductSeeder::class,
            StockItemSeeder::class,

            UserSeeder::class,
            AddressSeeder::class,

            WishlistSeeder::class,
            WishlistItemSeeder::class,

            CartSeeder::class,
            CartItemSeeder::class,

            CouponSeeder::class,

            OrderSeeder::class,
            OrderLineSeeder::class,

            ReviewSeeder::class,
        ]);

    }
}
