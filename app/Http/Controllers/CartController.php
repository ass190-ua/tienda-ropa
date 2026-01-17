<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\StockItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CartController extends Controller
{
    public function show(Request $request)
    {
        $cart = $this->getOrCreateCart($request);

        return response()->json($this->cartPayload($cart));
    }

    public function upsertItem(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:0'],
        ]);

        return $this->setQuantity($request, (int)$data['product_id'], (int)$data['quantity']);
    }

    public function updateItem(Request $request, int $product)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        // asegura que el producto existe
        Product::query()->whereKey($product)->firstOrFail();

        return $this->setQuantity($request, $product, (int)$data['quantity']);
    }

    public function deleteItem(Request $request, int $product)
    {
        return $this->setQuantity($request, $product, 0);
    }

    public function clear(Request $request)
    {
        $cart = $this->getOrCreateCart($request);

        DB::transaction(function () use ($cart) {
            $cart->items()->delete();
            $cart->touch(); // importante para el “carrito activo”
        });

        $cart->load(['items.product.images', 'items.product.size:id,value', 'items.product.color:id,value']);

        return response()->json($this->cartPayload($cart));
    }

    // =========================
    // Helpers
    // =========================

    private function getOrCreateCart(Request $request): Cart
    {
        $user = $request->user();

        $cart = Cart::query()->firstOrCreate([
            'user_id' => $user->id,
        ]);

        $cart->load([
            'items.product.images',
            'items.product.size:id,value',
            'items.product.color:id,value',
        ]);

        return $cart;
    }

    private function setQuantity(Request $request, int $productId, int $newQty)
    {
        $newQty = max(0, $newQty);

        $cart = $this->getOrCreateCart($request);

        return DB::transaction(function () use ($cart, $productId, $newQty) {

            $stockTotal = (int) StockItem::query()
                ->where('product_id', $productId)
                ->sum('quantity');

            if ($newQty > $stockTotal) {
                return response()->json([
                    'message' => 'Stock insuficiente actualmente para esa cantidad.',
                    'product_id' => $productId,
                    'requested' => $newQty,
                    'stock_total' => $stockTotal,
                ], 422);
            }

            // 0 = borrar línea
            if ($newQty === 0) {
                $cart->items()->where('product_id', $productId)->delete();
                $cart->touch();

                $cart->load(['items.product.images', 'items.product.size:id,value', 'items.product.color:id,value']);

                return response()->json($this->cartPayload($cart));
            }

            $product = Product::query()
                ->with(['images', 'size:id,value', 'color:id,value'])
                ->findOrFail($productId);

            $unit = (float) $product->price;
            $line = $unit * $newQty;

            $existing = $cart->items()->where('product_id', $productId)->orderBy('id')->first();

            if ($existing) {
                $existing->update([
                    'quantity'   => $newQty,
                    'unit_price' => number_format($unit, 2, '.', ''),
                    'line_total' => number_format($line, 2, '.', ''),
                ]);

                $cart->items()
                    ->where('product_id', $productId)
                    ->where('id', '<>', $existing->id)
                    ->delete();
            } else {
                $cart->items()->create([
                    'product_id' => $productId,
                    'quantity'   => $newQty,
                    'unit_price' => number_format($unit, 2, '.', ''),
                    'line_total' => number_format($line, 2, '.', ''),
                ]);
            }

            $cart->touch();

            $cart->load(['items.product.images', 'items.product.size:id,value', 'items.product.color:id,value']);

            return response()->json($this->cartPayload($cart));
        });
    }

    private function cartPayload(Cart $cart): array
    {
        return [
            'id' => $cart->id,
            'updated_at' => $cart->updated_at?->toISOString(),
            'items' => $cart->items->map(function ($it) {
                $p = $it->product;

                $images = $p?->images
                    ? $p->images->sortBy('sort_order')->map(fn($img) => Storage::url($img->path))->values()
                    : collect();

                return [
                    'id' => $it->id,
                    'product_id' => $it->product_id,
                    'quantity' => (int) $it->quantity,
                    'unit_price' => (float) $it->unit_price,
                    'line_total' => (float) $it->line_total,

                    'product' => $p ? [
                        'id' => $p->id,
                        'name' => $p->name,
                        'price' => (float) $p->price,

                        'images' => $images,

                        'size_id' => $p->size_id,
                        'size' => $p->size?->value,

                        'color_id' => $p->color_id,
                        'color' => $p->color?->value,
                    ] : null,
                ];
            })->values(),
        ];
    }
}
