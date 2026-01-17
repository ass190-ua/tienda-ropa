<?php
//se encarga de asignar las imagenes a los productos
namespace Database\Seeders\Products;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\ProductImage;

trait SeedsProductImages
{
    protected function seedImagesForProduct(
        int $productId,
        string $group,      // 'ropa' | 'calzado'
        string $typeSlug,   // 'pantalon', 'bota-de-tacon'...
        string $catSlug,    // 'hombre', 'mujer', 'nino', 'nina'
        int $maxImages = 3
    ): void {
        $root = public_path('images');
        $baseDir = $root . DIRECTORY_SEPARATOR . $group . DIRECTORY_SEPARATOR . $typeSlug . DIRECTORY_SEPARATOR . $catSlug;

        if (!File::isDirectory($baseDir)) return;

        $variants = collect(File::directories($baseDir))
            ->map(fn($d) => basename($d))
            ->filter(fn($v) => ctype_digit($v))
            ->values();

        if ($variants->isEmpty()) return;

        $chosenVariant = $variants->random();
        $variantDir = $baseDir . DIRECTORY_SEPARATOR . $chosenVariant;

        $files = collect(File::files($variantDir))
            ->filter(fn($f) => in_array(strtolower($f->getExtension()), ['png','jpg','jpeg','webp']))
            ->values();

        if ($files->isEmpty()) return;

        $count = min($maxImages, $files->count());
        $picked = $files->shuffle()->take($count)->values();

        foreach ($picked as $i => $file) {
            ProductImage::create([
                'product_id' => $productId,
                'path'       => "images/{$group}/{$typeSlug}/{$catSlug}/{$chosenVariant}/" . $file->getFilename(),
                'sort_order' => $i,
            ]);
        }
    }

    protected function slug(string $text): string
    {
        return Str::of($text)->lower()->ascii()->slug('-')->toString();
    }

    protected function catSlug(string $categoryName): string
    {
        $c = Str::of($categoryName)->lower()->ascii()->toString();
        if (str_starts_with($c, 'zapatos ')) $c = substr($c, strlen('zapatos '));
        return Str::of($c)->slug('-')->toString();
    }
}
