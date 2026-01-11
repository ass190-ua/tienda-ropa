<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attribute;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        Attribute::insert([
            ['code' => 'color',    'name' => 'Color'],
            ['code' => 'size',     'name' => 'Talla'],
            ['code' => 'brand',    'name' => 'Marca'],
            ['code' => 'type',     'name' => 'Tipo de producto'],
            ['code' => 'category', 'name' => 'Categoría'],
        ]);
    }
}
