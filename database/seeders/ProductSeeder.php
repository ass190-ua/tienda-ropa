<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            \Database\Seeders\products\TopsSeeder::class,
            \Database\Seeders\products\BottomsSeeder::class,
            \Database\Seeders\products\ShoesSeeder::class,

        ]);
    }
}
