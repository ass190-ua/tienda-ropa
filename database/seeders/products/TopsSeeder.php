<?php

namespace Database\Seeders\Products;

use Illuminate\Database\Seeder;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use Illuminate\Support\Str;

class TopsSeeder extends Seeder
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

        // ===== REGLAS GENERALES =====
        $sizesAdult  = ['S','M','L'];
        $sizesKids   = ['10','12','14'];
        $colorsAdult = ['Negro','Blanco','Gris', 'Azul', 'Rojo'];
        $colorsKids  = ['Negro','Blanco','Gris','Azul','Marrón', 'Rojo', 'Rosa'];

        // Rangos de precio por tipo
        $priceRanges = [
            'Camiseta' => [14, 29],
            'Camisa'   => [29, 59],
            'Top'      => [15, 34],
            'Chaqueta' => [49, 109],
            'Vestido'  => [39, 89],
        ];

        $catalog = [

        'Camiseta' => [
            'Hombre' => [
                'Camiseta básica algodón',
                'Camiseta oversize urbana',
                'Camiseta deportiva transpirable',
                'Camiseta slim fit',
                'Camiseta gráfica urbana',
            ],
            'Mujer' => [
                'Camiseta básica algodón',
                'Camiseta cuello barco',
                'Camiseta yoga fluida',
                'Camiseta efecto lavado',
                'Camiseta manga larga suave',
            ],
            'Niño' => [
                'Camiseta infantil ilustrada',
                'Camiseta infantil cómoda',
                'Camiseta infantil deportiva',
            ],
            'Niña' => [
                'Camiseta infantil ilustrada',
                'Camiseta infantil estampada',
                'Camiseta infantil suave',
            ],
        ],

        'Camisa' => [
            'Hombre' => [
                'Camisa clásica lisa',
                'Camisa oxford regular',
                'Camisa slim business',
                'Camisa lino verano',
                'Camisa denim ligera',
            ],
            'Mujer' => [
                'Camisa fluida satén',
                'Camisa romántica volantes',
                'Blusa satinada elegante',
                'Camisa camisera',
                'Camisa lino suave',
            ],
            'Niño' => [
                'Camisa infantil cuadros',
                'Camisa infantil lisa',
            ],
            'Niña' => [
                'Camisa infantil estampada',
                'Camisa infantil ligera',
            ],
        ],

        'Top' => [
            'Mujer' => [
                'Top básico ajustado',
                'Top canalé slim',
                'Top crop deportivo',
                'Top asimétrico moderno',
                'Top cuello halter',
                'Top satinado fluido',
                'Top punto fino',
                'Top deportivo seamless',
                'Top lencero elegante',
                'Top algodón orgánico',
            ],
        ],

        'Chaqueta' => [
            'Hombre' => [
                'Chaqueta cortavientos urbana',
                'Chaqueta técnica deportiva',
                'Sobrecamisa estructurada',
                'Chaqueta softshell outdoor',
                'Chaqueta denim regular',
            ],
            'Mujer' => [
                'Chaqueta vaquera clásica',
                'Blazer estructurado',
                'Chaqueta tweed corta',
                'Chaqueta corta elegante',
                'Chaqueta impermeable ligera',
            ],
            'Niño' => [
                'Chaqueta acolchada ligera',
                'Chaqueta infantil impermeable',
            ],
            'Niña' => [
                'Chaqueta acolchada ligera',
                'Chaqueta infantil impermeable',
            ],
        ],

        'Vestido' => [
            'Mujer' => [
                'Vestido casual diario',
                'Vestido midi fluido',
                'Vestido camisero',
                'Vestido lencero satén',
                'Vestido punto canalé',
                'Vestido corto verano',
                'Vestido largo boho',
                'Vestido elegante noche',
            ],
            'Niña' => [
                'Vestido infantil estampado',
                'Vestido infantil tul',
            ],
        ],

        ];

        foreach ($catalog as $type => $byCategory) {
            foreach ($byCategory as $categoryName => $names) {

                $isKid = in_array($categoryName,['Niño','Niña']);
                $sizesToUse  = $isKid ? $sizesKids : $sizesAdult;
                $colorsToUse = $isKid ? $colorsKids : $colorsAdult;

                if ($type === 'Top') {
                    $colorsToUse = ['Negro','Blanco','Gris'];
                }

                foreach ($names as $name) {
                    $price = rand($priceRanges[$type][0], $priceRanges[$type][1]);

                    foreach ($colorsToUse as $colorName) {
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
