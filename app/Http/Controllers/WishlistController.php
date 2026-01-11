<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    private function getOrCreateDefaultWishlist(Request $request): Wishlist
    {
        $user = $request->user();

        $wishlist = $user->wishlists()->first();
        if (!$wishlist) {
            $wishlist = $user->wishlists()->create([
                'name' => 'Mi Wishlist',
            ]);
        }

        return $wishlist;
    }

    // GET /api/wishlist
    public function index(Request $request)
    {
        $wishlist = $this->getOrCreateDefaultWishlist($request);

        // Devolvemos productos con imágenes (para pintar cards en frontend)
        $products = $wishlist->items()
            ->with(['product.images'])
            ->get()
            ->pluck('product')
            ->filter()
            ->values();

        return response()->json($products);
    }

    // POST /api/wishlist { product_id }
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
        ]);

        $wishlist = $this->getOrCreateDefaultWishlist($request);

        // Evitar duplicados
        $exists = $wishlist->items()->where('product_id', $data['product_id'])->exists();
        if (!$exists) {
            $wishlist->items()->create([
                'product_id' => $data['product_id'],
                'added_at' => now(),
            ]);
        }

        return response()->json(['ok' => true], 201);
    }

    // DELETE /api/wishlist/{product_id}
    public function destroy(Request $request, int $product_id)
    {
        $wishlist = $this->getOrCreateDefaultWishlist($request);

        $wishlist->items()->where('product_id', $product_id)->delete();

        return response()->json(['ok' => true]);
    }
}
