<?php

namespace Database\Seeders\Products;

use Illuminate\Database\Seeder;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use Illuminate\Support\Str;

class BottomsSeeder extends Seeder
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
        $sizesAdult = ['S','M','L','36','38','40','42'];
        $sizesKids  = ['10','12','14'];

        $colorsCommon = ['Azul','Negro','Blanco','Marrón','Gris'];

        // Rangos de precio por tipo
        $priceRanges = [
            'Pantalón' => [29, 79],
            'Falda'    => [24, 69],
        ];

        /*
        |--------------------------------------------------------------------------
        | CATÁLOGO AGRUPADO – BOTTOMS
        |--------------------------------------------------------------------------
        */
        $catalog = [

        'Pantalón' => [
            'Hombre' => [
                'Pantalón vaquero recto',
                'Pantalón vaquero slim',
                'Pantalón chino clásico',
                'Pantalón sastre moderno',
                'Pantalón cargo urbano',
                'Pantalón jogger casual',
                'Pantalón deportivo training',
                'Pantalón tapered fit',
                'Pantalón denim loose',
                'Pantalón técnico outdoor',
            ],
            'Mujer' => [
                'Pantalón vaquero recto',
                'Pantalón vaquero slim',
                'Pantalón ancho fluido',
                'Pantalón plisado elegante',
                'Pantalón chino femenino',
                'Pantalón jogger cómodo',
                'Pantalón deportivo soft',
                'Pantalón paperbag',
                'Pantalón culotte',
                'Pantalón denim cropped',
            ],
            'Niño' => [
                'Pantalón infantil cómodo',
                'Pantalón vaquero infantil',
                'Pantalón deportivo infantil',
                'Pantalón cargo infantil',
            ],
            'Niña' => [
                'Pantalón infantil cómodo',
                'Pantalón vaquero infantil',
                'Pantalón deportivo infantil',
                'Pantalón cargo infantil',
            ],
        ],

        'Falda' => [
            'Mujer' => [
                'Falda vaquera clásica',
                'Falda plisada midi',
                'Falda corta casual',
                'Falda midi fluida',
                'Falda satén elegante',
                'Falda lápiz oficina',
                'Falda cruzada moderna',
                'Falda boho larga',
            ],
            'Niña' => [
                'Falda infantil estampada',
                'Falda infantil tul',
                'Falda infantil volantes',
                'Falda infantil vaquera',
            ],
        ],

        ];

        foreach ($catalog as $type => $byCategory) {
            foreach ($byCategory as $categoryName => $names) {

                $isKid = in_array($categoryName, ['Niño','Niña']);
                $sizesToUse = $isKid ? $sizesKids : $sizesAdult;

                foreach ($names as $name) {

                    $price = rand($priceRanges[$type][0], $priceRanges[$type][1]);

                    foreach ($colorsCommon as $colorName) {
                        foreach ($sizesToUse as $sizeName) {
                            Product::create([
                                'name' => $name,
                                'url'  => Str::slug($name.'-'.uniqid()),
                                'description_short' => $name,
                                'description_long'  => "Prenda tipo {$type} pensada para {$categoryName}.",
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
