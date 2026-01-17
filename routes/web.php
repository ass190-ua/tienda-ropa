<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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

Route::get('/test-mail', function () {
    try {
        $config = config('mail');
        echo "Intentando enviar con: " . $config['mailers']['smtp']['transport'] . " // " . $config['mailers']['smtp']['host'] . ":" . $config['mailers']['smtp']['port'] . " // " . $config['mailers']['smtp']['encryption'] . "<br>";

        Mail::raw('Esta es una prueba de conexión desde Render.', function ($msg) {
            $msg->to('tiendamoda.ua@gmail.com')
                ->subject('Prueba de Conexión Render');
        });

        return '<h1>¡ÉXITO! El correo se ha enviado. El problema era la caché.</h1>';
    } catch (\Exception $e) {
        return '<h1>ERROR DETECTADO:</h1><pre>' . $e->getMessage() . '</pre>';
    }
});
