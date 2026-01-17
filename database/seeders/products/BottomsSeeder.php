<?php

namespace Database\Seeders\Products;

use Illuminate\Database\Seeder;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use Illuminate\Support\Str;
use Database\Seeders\products\SeedsProductImages;
use App\Models\ProductImage;

class BottomsSeeder extends Seeder
{
    use SeedsProductImages;
    
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

                // Estos 3 son iguales para todos los "productos base" de este type+category
                $group = 'ropa';
                $typeSlug = $this->slug($type);            // pantalon / falda
                $catSlug  = $this->catSlug($categoryName); // hombre/mujer/nino/nina

                foreach ($names as $name) {

                    $price = rand($priceRanges[$type][0], $priceRanges[$type][1]);

                    // Se decide UNA SOLA VEZ por "producto base" (name+type+category)
                    $baseImageRows = null;

                    foreach ($colorsCommon as $colorName) {
                        foreach ($sizesToUse as $sizeName) {

                            // IMPORTANTE: guardamos el producto creado
                            $product = Product::create([
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

                            // Primera variante: creamos imágenes y guardamos sus paths
                            if ($baseImageRows === null) {

                                $this->seedImagesForProduct($product->id, $group, $typeSlug, $catSlug, 3);

                                $baseImageRows = ProductImage::where('product_id', $product->id)
                                    ->orderBy('sort_order')
                                    ->get(['path','sort_order'])
                                    ->map(fn($r) => ['path' => $r->path, 'sort_order' => $r->sort_order])
                                    ->toArray();

                            } else {
                                // Resto de variantes: copiamos las mismas imágenes
                                foreach ($baseImageRows as $row) {
                                    ProductImage::create([
                                        'product_id' => $product->id,
                                        'path'       => $row['path'],
                                        'sort_order' => $row['sort_order'],
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }

    }
}
