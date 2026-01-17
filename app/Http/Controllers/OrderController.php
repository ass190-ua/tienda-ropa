<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Order;
use App\Models\Address;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Coupon;
use App\Models\Cart;
use App\Models\StockItem;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->with([
                'lines.product.images',
                'lines.product.size:id,value',
                'lines.product.color:id,value',
                'payments',
            ])
            ->orderByDesc('created_at')
            ->get();

        $payload = $orders->map(function ($o) {
            $latestPayment = $o->payments->sortByDesc('id')->first();
            $itemsCount = $o->lines->sum('quantity');

            $totalBase = (float)$o->subtotal
                - (float)$o->discount_total
                - (float)$o->coupon_discount_total;

            $totalBase = (float) number_format($totalBase, 2, '.', '');

            $totalPaid = $latestPayment ? (float)$latestPayment->amount : null;

            // Mantén 'total' como “lo que se pagó” si hay pago, si no el totalBase
            $total = $totalPaid !== null ? $totalPaid : $totalBase;

            // Envío derivado: (pagado - base). Si no hay pago, lo dejamos null
            $shippingTotal = $totalPaid !== null
                ? (float) number_format(max(0, $totalPaid - $totalBase), 2, '.', '')
                : null;

            return [
                'id' => $o->id,
                'created_at' => $o->created_at?->toISOString(),
                'status' => $o->status,

                'subtotal' => (float)$o->subtotal,
                'discount_total' => (float)$o->discount_total,
                'coupon_discount_total' => (float)$o->coupon_discount_total,

                'total_base' => $totalBase,

                'total_paid' => $totalPaid,

                'shipping_total' => $shippingTotal,

                'total' => (float) number_format($total, 2, '.', ''),

                'payment' => $latestPayment ? [
                    'status' => $latestPayment->status,
                    'transaction_id' => $latestPayment->transaction_id,
                    'amount' => (float)$latestPayment->amount,
                ] : null,

                'items_count' => (int)$itemsCount,

                'lines' => $o->lines->map(function ($l) {
                    $p = $l->product;
                    $firstImg = $p?->images?->sortBy('sort_order')->first();

                    return [
                        'product_id' => $l->product_id,
                        'name' => $p?->name,

                        'image_path' => $firstImg
                            ? ($firstImg->url ?? ($firstImg->path ? ('/' . ltrim($firstImg->path, '/')) : null))
                            : null,

                        'quantity' => (int) $l->quantity,
                        'unit_price' => (float) $l->unit_price,
                        'line_total' => (float) $l->line_total,

                        'size_id' => $p?->size_id,
                        'size' => $p?->size?->value,

                        'color_id' => $p?->color_id,
                        'color' => $p?->color?->value,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json(['data' => $payload]);
    }

    public function completeFromTpv(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],

            'amount' => ['nullable', 'numeric'],

            'shipping' => ['required', 'array'],
            'shipping.line1' => ['required', 'string', 'max:255'],
            'shipping.city' => ['required', 'string', 'max:255'],
            'shipping.zip' => ['required', 'string', 'max:50'],
            'shipping.country' => ['required', 'string', 'max:255'],

            'billing' => ['required', 'array'],
            'billing.line1' => ['required', 'string', 'max:255'],
            'billing.city' => ['required', 'string', 'max:255'],
            'billing.zip' => ['required', 'string', 'max:50'],
            'billing.country' => ['required', 'string', 'max:255'],

            'coupon_code' => ['nullable', 'string', 'max:50'],

            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required_with:items', 'integer', 'min:1'],
        ]);

        $token = $data['token'];

        // Idempotencia: si ya existe un pago con ese token, no duplicamos pedido
        $existing = Payment::query()
            ->where('user_id', $user->id)
            ->where('transaction_id', $token)
            ->first();

        if ($existing) {
            return response()->json([
                'data' => [
                    'order_id' => $existing->order_id,
                    'already_exists' => true,
                ]
            ], 200);
        }

        // Verificar con el TPV (no confiar en la query string del frontend)
        $tpvBase = rtrim(env('TPV_BASE_URL'), '/');
        $apiKey  = env('TPV_API_KEY');

        $verifyResp = Http::withHeaders([
            'X-API-KEY' => $apiKey,
            'Accept' => 'application/json',
        ])->get($tpvBase . '/api/v1/payments/verify/' . $token);

        if (!$verifyResp->successful()) {
            return response()->json([
                'error' => 'No se pudo verificar el pago con el TPV',
                'tpv_status' => $verifyResp->status(),
                'tpv_body' => $verifyResp->json(),
            ], 502);
        }

        $body = $verifyResp->json() ?? [];
        $finalStatus = $body['status'] ?? 'UNKNOWN';

        if ($finalStatus !== 'COMPLETED') {
            return response()->json([
                'error' => 'El pago no está COMPLETED',
                'status' => $finalStatus,
            ], 422);
        }

        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->with('items')
            ->first();

        $useCart = $cart && $cart->items->count() > 0;

        if ($useCart) {
            $checkoutItems = $cart->items
                ->groupBy('product_id')
                ->map(fn($g, $pid) => [
                    'product_id' => (int) $pid,
                    'qty' => (int) $g->sum('quantity'),
                ])
                ->values()
                ->all();
        } else {
            $checkoutItems = collect($data['items'] ?? [])
                ->groupBy('product_id')
                ->map(fn($g, $pid) => [
                    'product_id' => (int) $pid,
                    'qty' => (int) $g->sum('qty'),
                ])
                ->values()
                ->all();
        }

        if (count($checkoutItems) === 0) {
            return response()->json([
                'message' => 'El carrito está vacío o no se pudieron obtener los items para el pedido.'
            ], 422);
        }

        return DB::transaction(function () use ($user, $data, $token, $checkoutItems, $useCart, $cart) {
            // Direcciones
            $shippingAddr = Address::create([
                'user_id' => $user->id,
                'line1' => $data['shipping']['line1'],
                'city' => $data['shipping']['city'],
                'zip' => $data['shipping']['zip'],
                'country' => $data['shipping']['country'],
                'type' => 'shipping',
            ]);

            Address::create([
                'user_id' => $user->id,
                'line1' => $data['billing']['line1'],
                'city' => $data['billing']['city'],
                'zip' => $data['billing']['zip'],
                'country' => $data['billing']['country'],
                'type' => 'billing',
            ]);

            // Crear pedido
            $order = \App\Models\Order::create([
                'user_id' => $user->id,
                'address_id' => $shippingAddr->id,
                'coupon_id' => null,
                'subtotal' => 0,
                'discount_total' => 0,
                'coupon_discount_total' => 0,
                'status' => \App\Models\Order::STATUS_PAID,
            ]);

            $subtotal = 0.0;

            foreach ($checkoutItems as $it) {
                $productId = (int) $it['product_id'];
                $qty = (int) $it['qty'];

                if ($qty <= 0) continue;

                // Bloqueamos filas de stock para este producto (evita carreras)
                $stockRows = StockItem::query()
                    ->where('product_id', $productId)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $stockTotal = (int) $stockRows->sum('quantity');

                if ($stockTotal < $qty) {
                    throw new HttpResponseException(response()->json([
                        'message' => 'Stock insuficiente para completar el pedido.',
                        'product_id' => $productId,
                        'requested' => $qty,
                        'stock_total' => $stockTotal,
                    ], 422));
                }

                $remaining = $qty;
                foreach ($stockRows as $row) {
                    if ($remaining <= 0) break;

                    $take = min((int)$row->quantity, $remaining);
                    if ($take > 0) {
                        $row->quantity = (int)$row->quantity - $take;
                        $row->save();
                        $remaining -= $take;
                    }
                }

                $product = Product::findOrFail($productId);
                $unit = (float) $product->price;
                $line = $unit * $qty;

                $subtotal += $line;

                $order->lines()->create([
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => number_format($unit, 2, '.', ''),
                    'line_total' => number_format($line, 2, '.', ''),
                ]);
            }

            $couponId = null;
            $couponDiscount = 0.0;

            $code = trim((string)($data['coupon_code'] ?? ''));

            if ($code !== '') {
                $coupon = Coupon::query()
                    ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
                    ->first();

                if ($coupon && $coupon->is_active) {
                    $now = now();

                    $okDates = (!$coupon->start_date || $now->gte($coupon->start_date))
                        && (!$coupon->end_date || $now->lte($coupon->end_date));

                    $okMin = ($coupon->min_order_total === null)
                        || ((float)$subtotal >= (float)$coupon->min_order_total);

                    if ($okDates && $okMin) {
                        if ($coupon->discount_type === 'percent') {
                            $couponDiscount = (float)$subtotal * ((float)$coupon->discount_value / 100.0);
                        } elseif ($coupon->discount_type === 'fixed') {
                            $couponDiscount = (float)$coupon->discount_value;
                        }

                        $couponDiscount = round(min((float)$subtotal, (float)$couponDiscount), 2);
                        $couponId = $coupon->id;
                    }
                }
            }

            $order->update([
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'coupon_id' => $couponId,
                'coupon_discount_total' => number_format($couponDiscount, 2, '.', ''),
            ]);

            // Pago asociado
            $amount = $data['amount'] ?? $subtotal;

            Payment::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'amount' => number_format((float)$amount, 2, '.', ''),
                'status' => Payment::STATUS_SUCCESS,
                'transaction_id' => $token,
            ]);

            if ($useCart && $cart) {
                $cart->items()->delete();
                $cart->touch();
            }

            return response()->json([
                'data' => [
                    'order_id' => $order->id,
                    'token' => $token,
                ]
            ], 201);
        });
    }
}
