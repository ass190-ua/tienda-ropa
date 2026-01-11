<?php

namespace Database\Seeders\Products;

use Illuminate\Database\Seeder;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use Illuminate\Support\Str;

class ShoesSeeder extends Seeder
{
    public function run(): void
    {
        // ===== ATRIBUTOS =====
        $attrType     = Attribute::where('code','type')->firstOrFail();
        $attrCategory = Attribute::where('code','category')->firstOrFail();
        $attrColor    = Attribute::where('code','color')->firstOrFail();
        $attrSize     = Attribute::where('code','size')->firstOrFail();

        $types      = AttributeValue::where('attribute_id',$attrType->id)->get()->keyBy('value');
        $categories = AttributeValue::where('attribute_id',$attrCategory->id)->get()->keyBy('value');
        $colors     = AttributeValue::where('attribute_id',$attrColor->id)->get()->keyBy('value');
        $sizes      = AttributeValue::where('attribute_id',$attrSize->id)->get()->keyBy('value');

        // ===== REGLAS =====
        $sizesKids  = ['30','31','32','33','34'];
        $sizesAdult = ['35','36','37','38','39','40','41','42'];

        $shoeColors = ['Negro','Blanco','Marrón','Gris','Azul'];

        // Rangos de precio por tipo
        $priceRanges = [
            'Zapatilla deportiva' => [39, 99],
            'Zapato casual'       => [49, 109],
            'Bota'                => [69, 149],
            'Bota de tacón'       => [79, 159],
            'Zapato náutico'      => [59, 129],
        ];

        /*
        |--------------------------------------------------------------------------
        | CATÁLOGO AGRUPADO – SHOES
        |--------------------------------------------------------------------------
        */
        $catalog = [

        'Zapatilla deportiva' => [
            'Zapatos Hombre' => [
                'Zapatilla running ligera',
                'Zapatilla urbana casual',
                'Zapatilla training versátil',
                'Zapatilla trail outdoor',
                'Zapatilla minimalista street',
            ],
            'Zapatos Mujer' => [
                'Zapatilla running ligera',
                'Zapatilla urbana casual',
                'Zapatilla training flexible',
                'Zapatilla lifestyle moderna',
                'Zapatilla deportiva cómoda',
            ],
            'Zapatos Niño' => [
                'Zapatilla infantil deportiva',
                'Zapatilla infantil running',
                'Zapatilla infantil velcro',
            ],
            'Zapatos Niña' => [
                'Zapatilla infantil deportiva',
                'Zapatilla infantil ligera',
                'Zapatilla infantil velcro',
            ],
        ],

        'Zapato casual' => [
            'Zapatos Hombre' => [
                'Zapato casual piel',
                'Zapato urbano cómodo',
                'Zapato slip-on moderno',
                'Zapato casual flexible',
            ],
            'Zapatos Mujer' => [
                'Zapato casual elegante',
                'Zapato urbano plano',
                'Zapato slip-on femenino',
            ],
            'Zapatos Niño' => [
                'Zapato casual infantil',
                'Zapato infantil cómodo',
            ],
            'Zapatos Niña' => [
                'Zapato casual infantil',
                'Zapato infantil elegante',
            ],
        ],

        'Bota' => [
            'Zapatos Hombre' => [
                'Bota outdoor resistente',
                'Bota urbana piel',
                'Bota trekking impermeable',
            ],
            'Zapatos Mujer' => [
                'Bota urbana moderna',
                'Bota piel cómoda',
                'Bota caña media',
            ],
            'Zapatos Niño' => [
                'Bota infantil invierno',
                'Bota infantil impermeable',
            ],
            'Zapatos Niña' => [
                'Bota infantil invierno',
                'Bota infantil impermeable',
            ],
        ],

        'Bota de tacón' => [
            'Zapatos Mujer' => [
                'Bota tacón elegante',
                'Bota tacón medio',
                'Botín tacón moderno',
                'Bota tacón ancho',
            ],
        ],

        'Zapato náutico' => [
            'Zapatos Hombre' => [
                'Zapato náutico clásico',
                'Zapato náutico piel',
                'Zapato náutico casual',
            ],
            'Zapatos Niño' => [
                'Zapato náutico infantil',
                'Zapato náutico infantil cómodo',
            ],
        ],

        ];

        foreach ($catalog as $type => $byCategory) {
            foreach ($byCategory as $categoryName => $names) {

                $sizesToUse = str_contains($categoryName,'Niñ')
                    ? $sizesKids
                    : $sizesAdult;

                foreach ($names as $name) {

                    $price = rand($priceRanges[$type][0], $priceRanges[$type][1]);

                    foreach ($shoeColors as $colorName) {
                        foreach ($sizesToUse as $sizeName) {
                            Product::create([
                                'name' => $name,
                                'url'  => Str::slug($name.'-'.uniqid()),
                                'description_short' => $name,
                                'description_long'  => "Calzado tipo {$type} pensado para {$categoryName}.",
                                'price' => $price,
                                'type_id' => $types[$type]->id,
                                'category_id' => $categories[$categoryName]->id,
                                'color_id' => $colors[$colorName]->id,
                                'size_id'  => $sizes[$sizeName]->id,
                            ]);
                        }
                    }
                }
            }
        }
    }
}
