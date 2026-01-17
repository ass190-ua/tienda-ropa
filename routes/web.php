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
    $apiKey = config('services.brevo.key');

    if (!$apiKey) {
        return 'ERROR: No se encuentra la clave BREVO_KEY en config/services.php';
    }

    // Llamada directa a la API de Brevo (sin drivers de Laravel)
    $response = Http::withHeaders([
        'api-key' => $apiKey,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ])->post('https://api.brevo.com/v3/smtp/email', [
        'sender' => ['name' => 'TiendaModa', 'email' => 'tiendamoda.ua@gmail.com'],
        'to' => [['email' => 'tiendamoda.ua@gmail.com']], // Se envía a ti mismo
        'subject' => 'Prueba API Directa Render',
        'htmlContent' => '<html><body><h1>¡Funciona!</h1><p>Enviado vía API HTTP directa.</p></body></html>'
    ]);

    if ($response->successful()) {
        return '<h1>¡ÉXITO TOTAL! (API Directa)</h1><p>Correo enviado correctamente saltándose el driver.</p>';
    } else {
        return '<h1>ERROR API:</h1><pre>' . $response->body() . '</pre>';
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
