<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'reviews.user',
        ])->findOrFail($id);

        return response()->json($product);
    }


    public function featuredProducts()
    {
        $products = Product::with('images')
            ->inRandomOrder()
            ->limit(12)
            ->get();

        return response()->json($products);
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
}
