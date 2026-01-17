<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * GET /api/products
     * Lista de productos para /shop con búsqueda + filtros + paginación + ordenación
     *
     * Filtros:
     * - page, per_page
     * - q
     * - price_min, price_max
     * - sizes (csv ids), colors (csv ids), types (csv ids)
     * - sort: relevance | price_asc | price_desc | newest
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 12);
        $perPage = max(1, min($perPage, 48)); // límite razonable

        $q = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'relevance');

        $query = Product::query()->with('images');

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'ILIKE', "%{$q}%")
                    ->orWhere('description_short', 'ILIKE', "%{$q}%")
                    ->orWhere('description_long', 'ILIKE', "%{$q}%");
            });
        }

        if ($request->filled('category')) {
            $categoryIds = $this->resolveCategoryIdsFromName($request->query('category'));
            if (!empty($categoryIds)) {
                $query->whereIn('category_id', $categoryIds);
            }
        }

        if ($request->filled('price_min')) {
            $query->where('price', '>=', (float) $request->query('price_min'));
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', (float) $request->query('price_max'));
        }

        if ($request->filled('sizes')) {
            $ids = $this->csvToIntArray($request->query('sizes'));
            if (!empty($ids)) $query->whereIn('size_id', $ids);
        }

        if ($request->filled('colors')) {
            $ids = $this->csvToIntArray($request->query('colors'));
            if (!empty($ids)) $query->whereIn('color_id', $ids);
        }

        if ($request->filled('types')) {
            $ids = $this->csvToIntArray($request->query('types'));
            if (!empty($ids)) $query->whereIn('type_id', $ids);
        }

        if ($sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } elseif ($sort === 'newest') {
            $query->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return response()->json(
            $query->paginate($perPage)->appends($request->query())
        );
    }

    /**
     * Muestra solo los productos recientes (Para la Home)
     * Ahora acepta query params opcionales: category_id, limit
     */
    public function homeProducts(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'integer'],
            'limit'       => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        // ✅ Mantiene comportamiento actual si no se pasa limit
        $limit = $validated['limit'] ?? 8;

        $query = Product::with('images')
            ->latest(); // Ordenar por fecha (el más nuevo primero)

        // ✅ Filtrado opcional por categoría
        if (!empty($validated['category_id'])) {
            $categoryId = (int) $validated['category_id'];

            // Usamos la relación 'category' (ya existe porque en show() la cargas)
            $query->whereHas('category', function ($q) use ($categoryId) {
                $q->where('id', $categoryId);
            });
        }

        $products = $query
            ->take($limit)
            ->get();

        return response()->json($products);
    }


    /**
     * Muestra el detalle de UN solo producto (Para /shop/{id})
     */
    public function show($id)
    {
        $product = Product::with([
            'images',
            'color',
            'size',
            'category',
            'type',
            //'reviews.user', las carrgamos en el controller de reviews
        ])->findOrFail($id);

        return response()->json($product);
    }

    public function grouped(Request $request)
    {
        $query = Product::query()
            ->select('name', 'category_id', 'type_id')
            ->selectRaw('MIN(id) as id, MIN(price) as price')
            ->groupBy('name', 'category_id', 'type_id')
            ->with(['images']);

        // ✅ Filtrar por category (igual que en index/filters)
        if ($request->filled('category')) {
            $categoryIds = $this->resolveCategoryIdsFromName($request->query('category'));
            if (!empty($categoryIds)) {
                $query->whereIn('category_id', $categoryIds);
            }
        }

        // ✅ (Opcional) filtrar por types si algún día lo pasas desde el front
        if ($request->filled('types')) {
            $ids = $this->csvToIntArray($request->query('types'));
            if (!empty($ids)) $query->whereIn('type_id', $ids);
        }

        $products = $query->get()->map(function ($p) {
            $groupProductIds = Product::where('name', $p->name)
                ->where('category_id', $p->category_id)
                ->where('type_id', $p->type_id)
                ->orderBy('id')
                ->pluck('id')
                ->values();

            $colors = Product::where('name', $p->name)
                ->where('category_id', $p->category_id)
                ->where('type_id', $p->type_id)
                ->with('color:id,value')
                ->get()
                ->pluck('color')
                ->filter()
                ->unique('id')
                ->values();

            $sizes = Product::where('name', $p->name)
                ->where('category_id', $p->category_id)
                ->where('type_id', $p->type_id)
                ->with('size:id,value')
                ->get()
                ->pluck('size')
                ->filter()
                ->unique('id')
                ->values();

            return [
                'id' => $p->id,
                'name' => $p->name,
                'price' => $p->price,
                'category_id' => $p->category_id,
                'type_id' => $p->type_id,
                'images' => $p->images,
                'colors' => $colors,
                'sizes' => $sizes,
                'representative_id' => $groupProductIds->first() ?? $p->id,
                'product_ids' => $groupProductIds,
            ];
        });

        return response()->json($products);
    }

    public function groupedByIds(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids)) $ids = [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($n) => $n > 0)));

        if (empty($ids)) {
            return response()->json([]);
        }

        // Primero obtenemos los productos que están en wishlist (variantes)
        $seedProducts = Product::query()
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'category_id', 'type_id']);

        if ($seedProducts->isEmpty()) {
            return response()->json([]);
        }

        // Generamos las claves de grupo (name + category_id + type_id)
        $groups = $seedProducts->map(fn($p) => [
            'name' => $p->name,
            'category_id' => $p->category_id,
            'type_id' => $p->type_id,
        ])->unique()->values();

        // Construimos una query OR por cada grupo
        $query = Product::query()
            ->select('name', 'category_id', 'type_id')
            ->selectRaw('MIN(id) as id, MIN(price) as price')
            ->groupBy('name', 'category_id', 'type_id')
            ->with(['images']);

        $query->where(function ($q) use ($groups) {
            foreach ($groups as $g) {
                $q->orWhere(function ($qq) use ($g) {
                    $qq->where('name', $g['name'])
                        ->where('category_id', $g['category_id'])
                        ->where('type_id', $g['type_id']);
                });
            }
        });

        $products = $query->get()->map(function ($p) {
            $groupProductIds = Product::where('name', $p->name)
                ->where('category_id', $p->category_id)
                ->where('type_id', $p->type_id)
                ->orderBy('id')
                ->pluck('id')
                ->values();

            $colors = Product::where('name', $p->name)
                ->where('category_id', $p->category_id)
                ->where('type_id', $p->type_id)
                ->with('color:id,value')
                ->get()
                ->pluck('color')
                ->filter()
                ->unique('id')
                ->values();

            $sizes = Product::where('name', $p->name)
                ->where('category_id', $p->category_id)
                ->where('type_id', $p->type_id)
                ->with('size:id,value')
                ->get()
                ->pluck('size')
                ->filter()
                ->unique('id')
                ->values();

            return [
                'id' => $p->id,
                'representative_id' => $groupProductIds->first() ?? $p->id,
                'product_ids' => $groupProductIds,
                'name' => $p->name,
                'price' => $p->price,
                'category_id' => $p->category_id,
                'type_id' => $p->type_id,
                'images' => $p->images,
                'colors' => $colors,
                'sizes' => $sizes,
            ];
        });

        return response()->json($products);
    }

    public function variantsByIds(Request $request)
    {
        $data = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['product_ids'])));

        // 1) Variantes
        $products = Product::query()
            ->with(['images', 'size:id,value', 'color:id,value'])
            ->whereIn('id', $ids)
            ->get();

        // 2) Stock real en lote
        $stockByProduct = DB::table('stock_items')
            ->select('product_id', DB::raw('COALESCE(SUM(quantity),0) as stock_total'))
            ->whereIn('product_id', $ids)
            ->groupBy('product_id')
            ->pluck('stock_total', 'product_id'); // [product_id => stock_total]

        return response()->json(
            $products->map(function ($p) use ($stockByProduct) {
                $images = $p->images
                    ? $p->images->sortBy('sort_order')->map(fn($img) => Storage::url($img->path))->values()
                    : collect();

                $stockTotal = (int) ($stockByProduct[$p->id] ?? 0);

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => (float) $p->price,
                    'images' => $images,

                    'size_id' => $p->size_id,
                    'size' => $p->size?->value,

                    'color_id' => $p->color_id,
                    'color' => $p->color?->value,


                    'stock_total' => $stockTotal,
                    'available' => $stockTotal,
                ];
            })->values()
        );
    }

    public function novedadesGroupedProducts(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $limit = $validated['limit'] ?? 8;

        // Para asegurar variedad, sacamos más candidatos y luego intercalamos por type_id
        $candidateLimit = min($limit * 5, 200);

        // 1) Sacamos "prendas" agrupadas: mismo name + category + type
        $groups = Product::query()
            ->where('category_id', (int) $validated['category_id'])
            ->select('name', 'category_id', 'type_id')
            ->selectRaw('MIN(id) as representative_id, MIN(price) as price, MAX(created_at) as newest_created_at')
            ->groupBy('name', 'category_id', 'type_id')
            ->orderByDesc('newest_created_at')
            ->limit($candidateLimit) // ✅ antes era $limit
            ->get();

        // Intercalamos los grupos por type_id para mayor variedad
        $byType = $groups->groupBy('type_id')->map(fn($items) => $items->values());
        $interleaved = collect();

        while ($interleaved->count() < $limit && $byType->isNotEmpty()) {
            foreach ($byType->keys() as $typeId) {
                if ($interleaved->count() >= $limit) break;

                $bucket = $byType->get($typeId);
                if ($bucket && $bucket->isNotEmpty()) {
                    $interleaved->push($bucket->shift());
                    $byType->put($typeId, $bucket);
                }

                if (!$byType->has($typeId) || $byType->get($typeId)->isEmpty()) {
                    $byType->forget($typeId);
                }
            }
        }

        $groups = $interleaved;

        // 2) Enriquecemos cada grupo con sus variantes (ids, colores, tallas) y la imagen del representative
        $result = $groups->map(function ($g) {
            $variants = Product::query()
                ->where('name', $g->name)
                ->where('category_id', $g->category_id)
                ->where('type_id', $g->type_id)
                ->with(['images'])
                ->get();

            $representative = $variants->firstWhere('id', $g->representative_id) ?? $variants->first();

            return [
                // Para mantener compatibilidad con el front: id = representative
                'id' => $representative?->id ?? $g->representative_id,
                'representative_id' => $representative?->id ?? $g->representative_id,

                'name' => $g->name,
                'category_id' => $g->category_id,
                'type_id' => $g->type_id,
                'price' => (float) $g->price,

                // Variantes agrupadas
                'product_ids' => $variants->pluck('id')->values(),
                'colors' => $variants->pluck('color')->filter()->unique()->values(),
                'sizes' => $variants->pluck('size')->filter()->unique()->values(),

                // Imágenes: usamos las del representative
                'images' => $representative?->images ?? [],
            ];
        });

        return response()->json($result);
    }


    /**
     * GET /api/products/filters
     * Devuelve filtros *válidos* según category/types actuales
     *
     * Params:
     * - category: "Hombre" | "Zapatos Hombre" | "Mujer" | ...
     * - types: csv de ids (ej: "56,57")  (los mandará el frontend)
     * - q, price_min, price_max, colors (opcional)
     */
    public function filters(Request $request)
    {
        $baseContext = Product::query();

        // 1) category por nombre (Hombre incluye Zapatos Hombre)
        $categoryName = trim((string) $request->query('category', ''));
        $categoryIds = $this->resolveCategoryIdsFromName($categoryName);
        if (!empty($categoryIds)) {
            $baseContext->whereIn('category_id', $categoryIds);
        }

        // 2) Búsqueda (opcional)
        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $baseContext->where(function ($qq) use ($q) {
                $qq->where('name', 'ILIKE', "%{$q}%")
                    ->orWhere('description_short', 'ILIKE', "%{$q}%")
                    ->orWhere('description_long', 'ILIKE', "%{$q}%");
            });
        }

        // 3) Precio (opcional)
        if ($request->filled('price_min')) {
            $baseContext->where('price', '>=', (float) $request->query('price_min'));
        }
        if ($request->filled('price_max')) {
            $baseContext->where('price', '<=', (float) $request->query('price_max'));
        }

        // 4) Colores seleccionados (opcional)
        if ($request->filled('colors')) {
            $ids = $this->csvToIntArray($request->query('colors'));
            if (!empty($ids)) $baseContext->whereIn('color_id', $ids);
        }

        // baseSelected = contexto + types seleccionados (para tallas/precio contextual)
        $baseSelected = (clone $baseContext);

        if ($request->filled('types')) {
            $ids = $this->csvToIntArray($request->query('types'));
            if (!empty($ids)) $baseSelected->whereIn('type_id', $ids);
        }

        // Max precio contextual (según types seleccionados)
        $max = (float) (clone $baseSelected)->max('price');
        if ($max <= 0) $max = (float) Product::max('price');

        // types: DEL CONTEXTO (no colapsa al seleccionar uno)
        $typeIds = (clone $baseContext)
            ->whereNotNull('type_id')
            ->distinct()
            ->pluck('type_id')
            ->all();

        // sizes/colors: DE LA SELECCIÓN (ropa vs zapatos)
        $sizeIds = (clone $baseSelected)
            ->whereNotNull('size_id')
            ->distinct()
            ->pluck('size_id')
            ->all();

        $colorIds = (clone $baseSelected)
            ->whereNotNull('color_id')
            ->distinct()
            ->pluck('color_id')
            ->all();

        $sizes  = AttributeValue::whereIn('id', $sizeIds)->orderBy('value')->get(['id', 'value as name']);
        $colors = AttributeValue::whereIn('id', $colorIds)->orderBy('value')->get(['id', 'value as name']);
        $types  = AttributeValue::whereIn('id', $typeIds)->orderBy('value')->get(['id', 'value as name']);

        return response()->json([
            'price' => [
                'min' => 0,
                'max' => (int) ceil($max),
            ],
            'sizes' => $sizes,
            'colors' => $colors,
            'types' => $types,
        ]);
    }


    /**
     * Convierte "1,2, 3" => [1,2,3]
     */
    private function csvToIntArray($csv): array
    {
        if ($csv === null) return [];

        $str = is_array($csv) ? implode(',', $csv) : (string) $csv;

        $unique = [];
        foreach (explode(',', $str) as $part) {
            $n = (int) trim($part);
            if ($n > 0) $unique[$n] = $n; // evita duplicados
        }

        return array_values($unique);
    }

    /**
     * "Hombre" => ids de ["Hombre", "Zapatos Hombre"]
     * "Zapatos Hombre" => id de ["Zapatos Hombre"]
     */
    private function resolveCategoryIdsFromName(string $name): array
    {
        $name = trim($name);
        if ($name === '') return [];

        $dept = ['Hombre', 'Mujer', 'Niño', 'Niña'];
        $names = in_array($name, $dept, true) ? [$name, "Zapatos {$name}"] : [$name];

        return AttributeValue::whereHas('attribute', fn($q) => $q->where('code', 'category'))
            ->whereIn('value', $names)
            ->pluck('id')
            ->all();
    }


    public function topPurchased()
    {
        $topIds = DB::table('order_lines')
            ->select('product_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->pluck('product_id')
            ->toArray();

        $products = Product::with('images')
            ->whereIn('id', $topIds)
            ->get()
            ->sortBy(fn($p) => array_search($p->id, $topIds))
            ->values();

        return response()->json($products);
    }

    public function topWishlisted()
    {
        $topIds = DB::table('wishlist_items')
            ->select('product_id', DB::raw('COUNT(*) as total_saves'))
            ->groupBy('product_id')
            ->orderByDesc('total_saves')
            ->limit(10)
            ->pluck('product_id')
            ->toArray();

        $products = Product::with('images')
            ->whereIn('id', $topIds)
            ->get()
            ->sortBy(fn($p) => array_search($p->id, $topIds))
            ->values();

        return response()->json($products);
    }

    public function availability(int $id)
    {
        Product::query()->whereKey($id)->firstOrFail();

        $stockTotal = (int) DB::table('stock_items')
            ->where('product_id', $id)
            ->sum('quantity');

        return response()->json([
            'product_id' => $id,
            'stock_total' => $stockTotal,
            'available' => $stockTotal,
        ]);
    }

    public function resolveVariant(Request $request)
    {
        $data = $request->validate([
            'product_ids'   => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer'],
            'size_id'       => ['nullable', 'integer'],
            'color_id'      => ['nullable', 'integer'],
        ]);

        $ids = array_values(array_unique(array_filter(
            array_map('intval', $data['product_ids']),
            fn($n) => $n > 0
        )));

        if (empty($ids)) {
            return response()->json(['message' => 'product_ids vacío.'], 422);
        }

        $q = Product::query()
            ->with(['images', 'size:id,value', 'color:id,value'])
            ->whereIn('id', $ids);

        if (!empty($data['size_id'])) {
            $q->where('size_id', (int) $data['size_id']);
        }

        if (!empty($data['color_id'])) {
            $q->where('color_id', (int) $data['color_id']);
        }

        $product = $q->orderBy('id')->first();

        if (!$product) {
            return response()->json([
                'message' => 'No existe variante para esa combinación.',
            ], 404);
        }

        $images = $product->images
            ? $product->images->sortBy('sort_order')->map(fn($img) => Storage::url($img->path))->values()
            : collect();

        return response()->json([
            'product_id' => $product->id,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'images' => $images,
                'size_id' => $product->size_id,
                'size' => $product->size?->value,
                'color_id' => $product->color_id,
                'color' => $product->color?->value,
            ],
        ]);
    }
}
