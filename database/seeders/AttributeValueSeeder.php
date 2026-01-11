<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attribute;
use App\Models\AttributeValue;

class AttributeValueSeeder extends Seeder
{
    public function run()
    {
        $color    = Attribute::where('code', 'color')->firstOrFail();
        $size     = Attribute::where('code', 'size')->firstOrFail();
        $brand    = Attribute::where('code', 'brand')->firstOrFail();
        $category = Attribute::where('code', 'category')->firstOrFail();
        $type     = Attribute::where('code', 'type')->firstOrFail();

        AttributeValue::insert([
            /*COLORES*/
            ['attribute_id' => $color->id, 'value' => 'Negro'],
            ['attribute_id' => $color->id, 'value' => 'Blanco'],
            ['attribute_id' => $color->id, 'value' => 'Rojo'],
            ['attribute_id' => $color->id, 'value' => 'Azul'],
            ['attribute_id' => $color->id, 'value' => 'Verde'],
            ['attribute_id' => $color->id, 'value' => 'Marrón'],
            ['attribute_id' => $color->id, 'value' => 'Beige'],
            ['attribute_id' => $color->id, 'value' => 'Gris'],
            ['attribute_id' => $color->id, 'value' => 'Rosa'],


            /*TALLAS ROPA ADULTO*/
            ['attribute_id' => $size->id, 'value' => 'S'],
            ['attribute_id' => $size->id, 'value' => 'M'],
            ['attribute_id' => $size->id, 'value' => 'L'],

            /*TALLAS ROPA INFANTIL*/
            ['attribute_id' => $size->id, 'value' => '10'],
            ['attribute_id' => $size->id, 'value' => '12'],
            ['attribute_id' => $size->id, 'value' => '14'],

            /*TALLAS CALZADO y PANTALONES*/
            ['attribute_id' => $size->id, 'value' => '30'],
            ['attribute_id' => $size->id, 'value' => '31'],
            ['attribute_id' => $size->id, 'value' => '32'],
            ['attribute_id' => $size->id, 'value' => '33'],
            ['attribute_id' => $size->id, 'value' => '34'],
            ['attribute_id' => $size->id, 'value' => '35'],
            ['attribute_id' => $size->id, 'value' => '36'],            ['attribute_id' => $size->id, 'value' => '36'],
            ['attribute_id' => $size->id, 'value' => '37'],
            ['attribute_id' => $size->id, 'value' => '38'],
            ['attribute_id' => $size->id, 'value' => '39'],
            ['attribute_id' => $size->id, 'value' => '40'],
            ['attribute_id' => $size->id, 'value' => '41'],
            ['attribute_id' => $size->id, 'value' => '42'],

            /*MARCAS*/
            ['attribute_id' => $brand->id, 'value' => 'Nike'],
            ['attribute_id' => $brand->id, 'value' => 'Adidas'],
            ['attribute_id' => $brand->id, 'value' => 'Puma'],
            ['attribute_id' => $brand->id, 'value' => 'Zara'],
            ['attribute_id' => $brand->id, 'value' => 'H&M'],
            ['attribute_id' => $brand->id, 'value' => 'Clarks'],
            ['attribute_id' => $brand->id, 'value' => 'Mustang'],
            ['attribute_id' => $brand->id, 'value' => 'Louis Vuitton'],
            ['attribute_id' => $brand->id, 'value' => 'Levis'],


            /*CATEGORÍAS*/
            ['attribute_id' => $category->id, 'value' => 'Mujer'],
            ['attribute_id' => $category->id, 'value' => 'Hombre'],
            ['attribute_id' => $category->id, 'value' => 'Niño'],
            ['attribute_id' => $category->id, 'value' => 'Niña'],
            ['attribute_id' => $category->id, 'value' => 'Zapatos Mujer'],
            ['attribute_id' => $category->id, 'value' => 'Zapatos Hombre'],
            ['attribute_id' => $category->id, 'value' => 'Zapatos Niño'],
            ['attribute_id' => $category->id, 'value' => 'Zapatos Niña'],

            /*TIPOS DE ROPA*/
            ['attribute_id' => $type->id, 'value' => 'Camiseta'],
            ['attribute_id' => $type->id, 'value' => 'Camisa'],
            ['attribute_id' => $type->id, 'value' => 'Top'],
            ['attribute_id' => $type->id, 'value' => 'Chaqueta'],
            ['attribute_id' => $type->id, 'value' => 'Vestido'],
            ['attribute_id' => $type->id, 'value' => 'Pantalón'],
            ['attribute_id' => $type->id, 'value' => 'Falda'],

            /*TIPOS DE CALZADO*/
            ['attribute_id' => $type->id, 'value' => 'Zapatilla deportiva'],
            ['attribute_id' => $type->id, 'value' => 'Zapato casual'],
            ['attribute_id' => $type->id, 'value' => 'Zapato náutico'],
            ['attribute_id' => $type->id, 'value' => 'Bota'],
            ['attribute_id' => $type->id, 'value' => 'Bota de tacón'],
        ]);
    }
}
