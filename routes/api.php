<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\WishlistController;


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

// Rutas de productos (Públicas)
// El orden es importante: las rutas específicas primero
Route::get('/products/home', [ProductController::class, 'homeProducts']); // Novedades para la Home
Route::get('/products/featured', [ProductController::class, 'featuredProducts']); // Destacados random
Route::get('/products', [ProductController::class, 'index']);             // Catálogo completo
Route::get('products/filters', [ProductController::class, 'filters']);
Route::get('/products/{id}', [ProductController::class, 'show'])->whereNumber('id'); // Detalle de un producto
Route::get('/products/top-purchased', [ProductController::class, 'topPurchased']);
Route::get('/products/top-wishlisted', [ProductController::class, 'topWishlisted']);

// Rutas de autenticación (Públicas)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

//Rutas de contacto
Route::post('/contact', [ContactController::class, 'send']);

// Rutas protegidas (Requieren Login)
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

    // TPV
    Route::post('/checkout/start', function (Request $request) {

        // De momento lo dejamos simple: amount por request (luego será el total del carrito/pedido)
        $amount = $request->input('amount', 49.99);

        // Callback real (la que ya funciona)
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

        // Si falla, devolvemos el error tal cual para depurar
        if (!$resp->successful()) {
            return response()->json([
                'error' => 'Error iniciando pago en TPV',
                'tpv_status' => $resp->status(),
                'tpv_body' => $resp->json(),
            ], 500);
        }

        // Aquí normalmente vendrán token + paymentUrl
        return response()->json($resp->json(), 200);
    });
});


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

    // Ruta de frontend (SPA). Importante: NO /api
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
