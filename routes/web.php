<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/test-mail', function () {
    // 1. Cogemos la clave directamente del entorno o config
    $apiKey = config('services.brevo.key') ?? env('BREVO_KEY');

    if (!$apiKey) {
        return 'ERROR CRÍTICO: No se encuentra la clave BREVO_KEY. Revisa las variables en Render.';
    }

    // 2. Enviamos usando HTTP directo (Esto Render NO lo bloquea)
    $response = Http::withHeaders([
        'api-key' => $apiKey,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ])->post('https://api.brevo.com/v3/smtp/email', [
        'sender' => ['name' => 'TiendaModa', 'email' => 'tiendamoda.ua@gmail.com'],
        'to' => [['email' => 'tiendamoda.ua@gmail.com']],
        'subject' => 'Prueba Final API Directa',
        'htmlContent' => '<html><body><h1>¡FUNCIONA!</h1><p>Si lees esto, Render y Brevo se aman.</p></body></html>'
    ]);

    // 3. Mostramos el resultado en pantalla
    if ($response->successful()) {
        return '<h1>✅ ÉXITO TOTAL</h1><p>El correo ha salido de Render hacia Brevo.</p><p>Revisa tu bandeja de entrada (o Spam).</p>';
    } else {
        return '<h1>❌ ERROR EN BREVO:</h1><pre>' . $response->body() . '</pre>';
    }
});

Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api(/|$)).*');

Route::get('/{any}', function () {
    return view('welcome'); // O 'app', depende de cómo se llame tu vista principal en resources/views
})->where('any', '.*');


Route::get('/checkout/callback', function (Request $request) {
    return response()->json([
        'token' => $request->query('token'),
        'status' => $request->query('status'),
        'all' => $request->query(),
    ]);
});
