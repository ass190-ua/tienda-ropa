<?php

use Illuminate\Http\Request;
use App\Models\Address;
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
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminReviewController;

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
Route::get('/products/featured', [ProductController::class, 'featuredProducts']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('products/filters', [ProductController::class, 'filters']);
Route::get('/products/{id}', [ProductController::class, 'show'])->whereNumber('id');
Route::get('/products/top-purchased', [ProductController::class, 'topPurchased']);
Route::get('/products/top-wishlisted', [ProductController::class, 'topWishlisted']);
Route::get('/products/grouped', [ProductController::class, 'grouped']);
Route::post('/products/grouped-by-ids', [ProductController::class, 'groupedByIds']);
Route::post('/products/resolve-variant', [ProductController::class, 'resolveVariant']);

// Reviews de productos (Público ver)
Route::get('/products/{product}/reviews', [ReviewController::class, 'index'])
    ->whereNumber('product');

// Autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Contacto
Route::post('/contact', [ContactController::class, 'send']);

// ==========================================
// RUTAS PROTEGIDAS (CLIENTE)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::put('/user', function (Request $request) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->update($data);

        return response()->json($user->fresh());
    });

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

    // TPV - Inicio de pago
    Route::post('/checkout/start', function (Request $request) {
        $amount = $request->input('amount', 49.99);
        $callbackUrl = $request->getSchemeAndHttpHost() . '/api/checkout/callback';
        $tpvBase = rtrim(env('TPV_BASE_URL'), '/');
        $apiKey  = env('TPV_API_KEY');

        $resp = Http::withHeaders([
            'X-API-KEY' => $apiKey,
            'Accept' => 'application/json',
        ])->post($tpvBase . '/api/v1/payments/init', [
            'amount' => $amount,
            'callbackUrl' => $callbackUrl,
        ]);

        if (!$resp->successful()) {
            return response()->json([
                'error' => 'Error iniciando pago en TPV',
                'tpv_status' => $resp->status(),
                'tpv_body' => $resp->json(),
            ], 500);
        }

        return response()->json($resp->json(), 200);
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
        return response()->json([
            'users_count' => \App\Models\User::count(),
            'orders_count' => \App\Models\Order::count(),
            'revenue'     => \App\Models\Order::sum('subtotal'), // Ajustar si usas total_amount
            'products_count' => \App\Models\Product::count(),
            'reviews_count' => \App\Models\Review::count(),
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
    Route::get('/reviews', [App\Http\Controllers\Admin\AdminReviewController::class, 'index']); // <--- ESTA FALTABA
    Route::delete('/reviews/{review}', [App\Http\Controllers\Admin\AdminReviewController::class, 'reject']);
    Route::patch('/reviews/{review}/approve', [App\Http\Controllers\Admin\AdminReviewController::class, 'approve']);
});
