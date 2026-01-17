<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeNewsletter;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        // 1. Validar
        $request->validate([
            'email' => 'required|email'
        ]);

        // 2. Comprobar si ya existe
        // Usamos firstOrCreate para no dar error si ya está, pero sí reenviar el cupón (opcional)
        // O simplemente retornar éxito si ya estaba.
        $subscriber = Subscriber::firstOrCreate(
            ['email' => $request->email]
        );

        // 3. Enviar el email (solo si se acaba de crear o si quieres reenviarlo siempre)
        if ($subscriber->wasRecentlyCreated) {
            try {
                Mail::to($request->email)->send(new WelcomeNewsletter());
            } catch (\Exception $e) {
                // Loguear error pero no fallar la respuesta al usuario
            }
            return response()->json(['message' => '¡Suscrito! Revisa tu correo para ver el cupón.']);
        }

        return response()->json(['message' => 'Ya estabas suscrito.']);
    }
}
