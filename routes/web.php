<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

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


Route::get('/checkout/callback', function (Request $request) {
    return response()->json([
        'token' => $request->query('token'),
        'status' => $request->query('status'),
        'all' => $request->query(),
    ]);
});
