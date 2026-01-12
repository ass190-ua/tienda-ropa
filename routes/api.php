<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\Admin\AdminUserController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// RUTAS PÚBLICAS

// Rutas de productos
// El orden es importante: las rutas específicas primero
Route::get('/products/home', [ProductController::class, 'homeProducts']); // Novedades para la Home
Route::get('/products/featured', [ProductController::class, 'featuredProducts']); // Destacados random
Route::get('/products', [ProductController::class, 'index']);             // Catálogo completo
Route::get('products/filters', [ProductController::class, 'filters']);
Route::get('/products/{id}', [ProductController::class, 'show'])->whereNumber('id'); // Detalle de un producto
Route::get('/products/top-purchased', [ProductController::class, 'topPurchased']);
Route::get('/products/top-wishlisted', [ProductController::class, 'topWishlisted']);

// Rutas de autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas de contacto
Route::post('/contact', [ContactController::class, 'send']);

// RUTAS PROTEGIDAS (CLIENTE)
Route::middleware('auth:sanctum')->group(function () {

    // Cerrar sesión
    Route::post('/logout', [AuthController::class, 'logout']);

    // Obtener datos del usuario logueado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{product_id}', [WishlistController::class, 'destroy'])->whereNumber('product_id');

    // TPV - Inicio de pago
    Route::post('/checkout/start', function (Request $request) {

        // De momento lo dejamos simple: amount por request
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
});

// CALLBACKS TPV (PÚBLICAS O MIXTAS)
Route::get('/checkout/callback', function (Request $request) {

    $token = $request->query('token');
    $statusHint = $request->query('status');

    if (!$token) {
        return response()->json([
            'error' => 'Falta token en la callbackUrl',
            'status_hint' => $statusHint,
            'all' => $request->query(),
        ], 400);
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

    // Ruta de frontend (SPA)
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

// GRUPO DE RUTAS DE ADMINISTRADOR
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    // Dashboard Stats
    Route::get('/stats', function() {
        return response()->json([
            'users_count' => \App\Models\User::count(),
            'orders_count' => \App\Models\Order::count(),
            // Sumamos el subtotal de todos los pedidos para saber el ingreso bruto
            'revenue'     => \App\Models\Order::sum('subtotal'),
            'products_count' => \App\Models\Product::count(),
            'reviews_count' => \App\Models\Review::count(),
        ]);
    });

    // GESTIÓN DE USUARIOS
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::put('/users/{id}', [AdminUserController::class, 'update']);
    Route::post('/users', [AdminUserController::class, 'store']);
    Route::patch('/users/{id}/toggle-active', [AdminUserController::class, 'toggleActive']);
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);

    // GESTIÓN DE PRODUCTOS
    Route::get('/products', [\App\Http\Controllers\Admin\AdminProductController::class, 'index']);
    Route::get('/products/form-data', [\App\Http\Controllers\Admin\AdminProductController::class, 'formData']);
    Route::delete('/products/{id}', [\App\Http\Controllers\Admin\AdminProductController::class, 'destroy']);
    Route::post('/products', [\App\Http\Controllers\Admin\AdminProductController::class, 'store']);
    Route::get('/products/{id}', [\App\Http\Controllers\Admin\AdminProductController::class, 'show']); // Para editar
    Route::post('/products/{id}', [\App\Http\Controllers\Admin\AdminProductController::class, 'update']);
});
