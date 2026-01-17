<?php

use Illuminate\Http\Request;
use App\Models\Address;
use App\Models\Cart;
use App\Models\StockItem;
use App\Models\Coupon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\NewsletterController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==========================================
// RUTAS PÚBLICAS
// ==========================================

// Productos
Route::get('/products/home', [ProductController::class, 'homeProducts']);
Route::get('/products/home-grouped', [ProductController::class, 'homeGroupedProducts']);
Route::get('/products/featured', [ProductController::class, 'featuredProducts']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('products/filters', [ProductController::class, 'filters']);
Route::get('/products/{id}/availability', [ProductController::class, 'availability'])->whereNumber('id');
Route::get('/products/{id}', [ProductController::class, 'show'])->whereNumber('id');
Route::get('/products/top-purchased', [ProductController::class, 'topPurchased']);
Route::get('/products/top-wishlisted', [ProductController::class, 'topWishlisted']);
Route::get('/products/grouped', [ProductController::class, 'grouped']);
Route::get('/products/novedades-grouped', [ProductController::class, 'novedadesGroupedProducts']);
Route::post('/products/grouped-by-ids', [ProductController::class, 'groupedByIds']);
Route::post('/products/variants-by-ids', [ProductController::class, 'variantsByIds']);
Route::post('/products/resolve-variant', [ProductController::class, 'resolveVariant']);

// Reviews de productos (Público ver)
Route::get('/products/{product}/reviews', [ReviewController::class, 'index'])
    ->whereNumber('product');

// Autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::middleware(['web'])->group(function () {
    Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle']);
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
});

// Contacto
Route::post('/contact', [ContactController::class, 'send']);

// Newsletter
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);

// ==========================================
// RUTAS PROTEGIDAS (CLIENTE)
// ==========================================
Route::middleware(['web', 'auth'])->group(function () {

    Route::get('/me', function (Request $request) {
        return response()->json(['user' => $request->user()]);
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::put('/user', [AuthController::class, 'updateProfile']);

    Route::delete('/me', [AuthController::class, 'destroyMe']);

    // Direcciones del usuario autenticado
    Route::get('/addresses/me', [AddressController::class, 'me']);
    Route::put('/addresses/shipping', [AddressController::class, 'saveShipping']);
    Route::put('/addresses/billing', [AddressController::class, 'saveBilling']);


    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{product_id}', [WishlistController::class, 'destroy'])->whereNumber('product_id');

    // Orders (Nuevas de tus compañeros)
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders/complete', [OrderController::class, 'completeFromTpv']);

    // Coupons
    Route::post('/coupons/validate', [CouponController::class, 'validateCoupon']);

    // Cart
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'upsertItem']);
    Route::patch('/cart/items/{product}', [CartController::class, 'updateItem'])->whereNumber('product');
    Route::delete('/cart/items/{product}', [CartController::class, 'deleteItem'])->whereNumber('product');
    Route::delete('/cart', [CartController::class, 'clear']);

    // TPV - Inicio de pago
    Route::post('/checkout/start', function (Request $request) {

        $user = $request->user();

        // Opcional: el frontend puede mandar cupón aplicado, pero NO amount.
        $data = $request->validate([
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ]);

        $couponCode = trim((string)($data['coupon_code'] ?? ''));

        // Cargar carrito del usuario (BD)
        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->with('items') // relación definida en Cart.php
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'message' => 'El carrito está vacío.',
                'code' => 'CART_EMPTY',
            ], 422);
        }

        // Agrupar cantidades por product_id
        $grouped = $cart->items
            ->groupBy('product_id')
            ->map(fn($g, $pid) => [
                'product_id' => (int)$pid,
                'qty' => (int)$g->sum('quantity'),
            ])
            ->values();

        // 1) Validación de stock (sin reservar: modelo mínimo)
        $insufficient = [];
        foreach ($grouped as $it) {
            $pid = (int)$it['product_id'];
            $qty = (int)$it['qty'];

            $stockTotal = (int) StockItem::query()
                ->where('product_id', $pid)
                ->sum('quantity');

            if ($qty > $stockTotal) {
                $insufficient[] = [
                    'product_id' => $pid,
                    'requested' => $qty,
                    'available' => $stockTotal,
                ];
            }
        }

        if (!empty($insufficient)) {
            return response()->json([
                'message' => 'No hay stock suficiente para iniciar el pago.',
                'code' => 'OUT_OF_STOCK_BEFORE_TPV',
                'items' => $insufficient,
            ], 422);
        }

        // 2) Calcular subtotal desde BD (cart_items tiene line_total)
        $subtotal = (float) $cart->items->sum('line_total');

        // 3) Cupón (misma idea que en OrderController)
        $couponDiscount = 0.0;
        if ($couponCode !== '') {
            $coupon = Coupon::query()
                ->whereRaw('LOWER(code) = ?', [mb_strtolower($couponCode)])
                ->first();

            if ($coupon && $coupon->is_active) {
                $now = now();

                $okDates = (!$coupon->start_date || $now->gte($coupon->start_date))
                    && (!$coupon->end_date || $now->lte($coupon->end_date));

                $okMin = ($coupon->min_order_total === null)
                    || ($subtotal >= (float)$coupon->min_order_total);

                if ($okDates && $okMin) {
                    if ($coupon->discount_type === 'percent') {
                        $couponDiscount = $subtotal * ((float)$coupon->discount_value / 100.0);
                    } elseif ($coupon->discount_type === 'fixed') {
                        $couponDiscount = (float)$coupon->discount_value;
                    }

                    $couponDiscount = round(min($subtotal, $couponDiscount), 2);
                }
            }
        }

        $base = max(0, round($subtotal - $couponDiscount, 2));

        // 4) Envío (igual que tu Checkout.vue)
        $shippingCost = ($base >= 60) ? 0.0 : 4.99;
        $total = round($base + $shippingCost, 2);

        // 5) Llamar al TPV con total del servidor
        $callbackUrl = $request->getSchemeAndHttpHost() . '/api/checkout/callback';
        $tpvBase = rtrim(env('TPV_BASE_URL'), '/');
        $apiKey  = env('TPV_API_KEY');

        $resp = Http::withHeaders([
            'X-API-KEY' => $apiKey,
            'Accept' => 'application/json',
        ])->post($tpvBase . '/api/v1/payments/init', [
            'amount' => $total,
            'callbackUrl' => $callbackUrl,
        ]);

        if (!$resp->successful()) {
            return response()->json([
                'error' => 'Error iniciando pago en TPV',
                'tpv_status' => $resp->status(),
                'tpv_body' => $resp->json(),
            ], 500);
        }

        // Importante: devolvemos también amount y breakdown para que el frontend guarde lo correcto
        return response()->json(array_merge($resp->json() ?? [], [
            'amount' => $total,
            'breakdown' => [
                'subtotal' => round($subtotal, 2),
                'coupon_discount' => $couponDiscount,
                'shipping' => $shippingCost,
                'base' => $base,
            ],
        ]), 200);
    });

    // Reviews (Escritura y Edición)
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])
        ->whereNumber('product');
    Route::patch('/products/{product}/reviews/{review}', [ReviewController::class, 'update'])
        ->whereNumber('product')
        ->whereNumber('review');

    // Gestión de Reviews (Admin provisional)
    Route::delete('/admin/reviews/{review}', [AdminReviewController::class, 'reject'])
        ->whereNumber('review');
    Route::patch('/admin/reviews/{review}/approve', [AdminReviewController::class, 'approve'])
        ->whereNumber('review');
});

// ==========================================
// CALLBACKS TPV
// ==========================================
Route::get('/checkout/callback', function (Request $request) {
    $token = $request->query('token');
    $statusHint = $request->query('status');

    if (!$token) {
        return response()->json(['error' => 'Falta token'], 400);
    }

    $tpvBase = rtrim(env('TPV_BASE_URL'), '/');
    $apiKey  = env('TPV_API_KEY');

    $verifyResp = Http::withHeaders([
        'X-API-KEY' => $apiKey,
        'Accept' => 'application/json',
    ])->get($tpvBase . '/api/v1/payments/verify/' . $token);

    $body = $verifyResp->json() ?? [];
    $finalStatus = $body['status'] ?? 'UNKNOWN';
    $failureReason = $body['failureReason'] ?? null;

    $frontendUrl = $request->getSchemeAndHttpHost()
        . '/pago/resultado?token=' . urlencode($token)
        . '&status=' . urlencode($finalStatus)
        . '&hint=' . urlencode((string)$statusHint);

    if ($failureReason) {
        $frontendUrl .= '&reason=' . urlencode((string)$failureReason);
    }

    return redirect()->away($frontendUrl);
});

Route::get('/tpv/verify/{token}', function (string $token) {
    $tpvBase = rtrim(env('TPV_BASE_URL'), '/');
    $apiKey  = env('TPV_API_KEY');

    $resp = Http::withHeaders([
        'X-API-KEY' => $apiKey,
        'Accept' => 'application/json',
    ])->get($tpvBase . '/api/v1/payments/verify/' . $token);

    return response()->json($resp->json(), $resp->status());
});

// ==========================================
// GRUPO DE RUTAS DE ADMINISTRADOR
// ==========================================
// Nota: Verifica si el middleware se llama 'admin' o 'is_admin' en tu Kernel.php
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    // Stats Dashboard
    Route::get('/stats', function () {
        // Definimos qué estados suman DINERO real
        $validStatuses = ['paid', 'shipped', 'delivered'];

        // Calculamos los ingresos (Solo de pedidos válidos/cobrados)
        // Usamos ->get() y ->sum('total') para usar el atributo calculado en el Modelo
        $revenue = \App\Models\Order::whereIn('status', $validStatuses)
            ->get()
            ->sum('total');

        return response()->json([
            'users_count' => \App\Models\User::count(),

            //Aquí volvemos a contar TODOS los pedidos para ver el volumen total de actividad
            'orders_count' => \App\Models\Order::count(),

            'revenue'     => $revenue, // El dinero SÍ se mantiene filtrado (solo lo real)
            'products_count' => \App\Models\Product::count(),
            'reviews_count' => \App\Models\Review::count(),
            'coupons_count' => \App\Models\Coupon::count(),
        ]);
    });

    // Gestión de Usuarios
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::put('/users/{id}', [AdminUserController::class, 'update']);
    Route::post('/users', [AdminUserController::class, 'store']);
    Route::patch('/users/{id}/toggle-active', [AdminUserController::class, 'toggleActive']);
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);

    // Gestión de Productos
    Route::get('/products', [\App\Http\Controllers\Admin\AdminProductController::class, 'index']);
    Route::get('/products/form-data', [\App\Http\Controllers\Admin\AdminProductController::class, 'formData']);
    Route::delete('/products/{id}', [\App\Http\Controllers\Admin\AdminProductController::class, 'destroy']);
    Route::post('/products', [\App\Http\Controllers\Admin\AdminProductController::class, 'store']);
    Route::get('/products/{id}', [\App\Http\Controllers\Admin\AdminProductController::class, 'show']);
    Route::post('/products/{id}', [\App\Http\Controllers\Admin\AdminProductController::class, 'update']);
    Route::delete('/product-images/{id}', [\App\Http\Controllers\Admin\AdminProductController::class, 'deleteImage']);

    // Gestión de Pedidos
    Route::get('/orders', [App\Http\Controllers\Admin\AdminOrderController::class, 'index']);
    Route::get('/orders/export', [App\Http\Controllers\Admin\AdminOrderController::class, 'export']);
    Route::get('/orders/{id}', [App\Http\Controllers\Admin\AdminOrderController::class, 'show']);
    Route::put('/orders/{id}', [App\Http\Controllers\Admin\AdminOrderController::class, 'update']);

    // GESTIÓN DE REVIEWS
    Route::get('/reviews', [App\Http\Controllers\Admin\AdminReviewController::class, 'index']);
    Route::delete('/reviews/{review}', [App\Http\Controllers\Admin\AdminReviewController::class, 'reject']);
    Route::patch('/reviews/{review}/approve', [App\Http\Controllers\Admin\AdminReviewController::class, 'approve']);

    // GESTIÓN DE CUPONES
    Route::get('/coupons', [\App\Http\Controllers\Admin\AdminCouponController::class, 'index']);
    Route::post('/coupons', [\App\Http\Controllers\Admin\AdminCouponController::class, 'store']);
    Route::put('/coupons/{id}', [\App\Http\Controllers\Admin\AdminCouponController::class, 'update']);
    Route::delete('/coupons/{id}', [\App\Http\Controllers\Admin\AdminCouponController::class, 'destroy']);
    Route::patch('/coupons/{id}/toggle', [\App\Http\Controllers\Admin\AdminCouponController::class, 'toggleActive']);
});
