<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminProductController extends Controller
{
    public function index()
    {
        // Cargamos productos con sus relaciones necesarias para la tabla
        $products = Product::with(['category', 'images', 'stockItems'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($product) {
                // Procesamos los datos para facilitar la vida al Frontend
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    // Categoría (si existe)
                    'category' => $product->category ? $product->category->value : 'Sin categoría',
                    // Referencia: Usamos el código de la primera variante o el ID formateado
                    'sku' => $product->stockItems->first()->code ?? 'REF-' . str_pad($product->id, 4, '0', STR_PAD_LEFT),
                    // Stock Total: Suma de todas las variantes
                    'stock_total' => $product->stockItems->sum('quantity'),
                    // Imagen: La primera que encuentre o null
                    'image' => $product->images->first() ? $product->images->first()->path : null,];
            });

        return response()->json($products);
    }

    public function destroy($id)
    {
        // Cargamos el producto CON sus imágenes para poder leer las rutas
        $product = Product::with('images')->findOrFail($id);

        // 1. Borrar imágenes del disco
        foreach($product->images as $image) {
            // Limpiamos la ruta igual que hicimos en el update
            $pathRelative = str_replace('/storage/', '', $image->path);

            if (Storage::disk('public')->exists($pathRelative)) {
                Storage::disk('public')->delete($pathRelative);
            }
        }

        // 2. Borrar el producto de la BD (esto borra las filas de imagenes por cascada si está configurado, o manual)
        $product->delete();

        return response()->json(['message' => 'Producto eliminado y fotos borradas del disco.']);
    }

    // Obtener datos para los desplegables del formulario (Categorías, Tipos, etc.)
    public function formData()
    {
        $attributes = \App\Models\Attribute::with('values')->get();

        return response()->json($attributes);
    }

    public function store(Request $request)
    {
        // 1. Validación
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:attribute_values,id',
            'description_short' => 'nullable|string',
            'description_long' => 'nullable|string',
            // Stock inicial (crearemos un item de stock por defecto)
            'stock' => 'nullable|integer|min:0',
            // Imagen (fichero)
            'image_file' => 'nullable|image|max:10240', // Max 10MB
        ]);

        // 2. Crear Producto
        $product = new Product();
        $product->name = $validated['name'];
        // Generamos URL amigable (slug) a partir del nombre + random para evitar duplicados
        $product->url = \Illuminate\Support\Str::slug($validated['name']) . '-' . uniqid();
        $product->price = $validated['price'];
        $product->category_id = $validated['category_id'] ?? null;
        $product->description_short = $validated['description_short'] ?? '';
        $product->description_long = $validated['description_long'] ?? '';
        // Valores por defecto para lo que no viene del form básico
        $product->save();

        // 3. Gestión de Stock Inicial (Crea un StockItem por defecto)
        if (isset($validated['stock'])) {
            $product->stockItems()->create([
                'quantity' => $validated['stock'],
                'code' => 'SKU-' . str_pad($product->id, 5, '0', STR_PAD_LEFT) // Generar SKU auto
            ]);
        }

        // 4. Subida de Imagen
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('products', 'public');

            $product->images()->create([
                'path' => '/storage/' . $path,     // Cambiado de file_path a path
                'sort_order' => 1                  // Cambiado de order a sort_order
            ]);
        }

        return response()->json($product, 201);
    }

    public function show($id)
    {
        // Necesitamos cargar el producto con sus datos para editarlo
        $product = Product::with(['images', 'stockItems', 'category'])->findOrFail($id);

        // Formateamos para el frontend
        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'category_id' => $product->category_id,
            'description_short' => $product->description_short,
            'description_long' => $product->description_long,
            // Sumamos stock total
            'stock' => $product->stockItems->sum('quantity'),
            // Imagen actual (si tiene)
            'image_url' => $product->images->first() ? $product->images->first()->path : null,
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:attribute_values,id',
            'description_short' => 'nullable|string',
            'description_long' => 'nullable|string',
            'stock' => 'nullable|integer|min:0',
            // Recuerda: 10240KB = 10MB (ajustado a tu php.ini)
            'image_file' => 'nullable|image|max:10240',
        ]);

        $product->update([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'category_id' => $validated['category_id'],
            'description_short' => $validated['description_short'],
            'description_long' => $validated['description_long'],
        ]);

        // Gestión de Stock
        $stockItem = $product->stockItems()->first();
        if ($stockItem) {
            $stockItem->update(['quantity' => $validated['stock']]);
        } else {
            $product->stockItems()->create([
                'quantity' => $validated['stock'] ?? 0,
                'code' => 'SKU-' . $product->id
            ]);
        }

        // --- GESTIÓN DE IMAGEN MEJORADA (Limpieza de basura) ---
        if ($request->hasFile('image_file')) {
            // 1. Buscamos si ya existe una imagen en la BD para este producto
            $currentImage = $product->images()->first();

            // 2. Si existe, borramos el ARCHIVO FÍSICO antiguo
            if ($currentImage) {
                // La ruta en BD es "/storage/products/foto.jpg"
                // El disco necesita "products/foto.jpg" (sin /storage/)
                $oldPathRelative = str_replace('/storage/', '', $currentImage->path);

                // Borramos el archivo del disco
                Storage::disk('public')->delete($oldPathRelative);

                // 3. Subimos la NUEVA imagen
                $path = $request->file('image_file')->store('products', 'public');

                // 4. Actualizamos el registro existente con la nueva ruta
                $currentImage->update([
                    'path' => '/storage/' . $path
                ]);
            } else {
                // Si no tenía imagen anterior, creamos una nueva de cero
                $path = $request->file('image_file')->store('products', 'public');

                $product->images()->create([
                    'path' => '/storage/' . $path,
                    'sort_order' => 1
                ]);
            }
        }

        return response()->json($product);
    }
}
