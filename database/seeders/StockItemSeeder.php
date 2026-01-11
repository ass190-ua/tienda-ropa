<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\StockItem;

class StockItemSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Product::all() as $product) {
            StockItem::create([
                'product_id' => $product->id,
                'code' => 'SKU-' . str_pad($product->id, 6, '0', STR_PAD_LEFT),
                'quantity' => rand(5, 20),
            ]);
        }
    }
}
