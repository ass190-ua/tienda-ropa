<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscriber;
use App\Models\Coupon;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:subscribers,email'
        ]);

        // 1. Guardamos el suscriptor
        $subscriber = Subscriber::create(['email' => $request->email]);

        // 2. Buscamos (o creamos) un cupón de bienvenida
        $couponCode = 'BIENVENIDA10';
        $coupon = Coupon::firstOrCreate(
            ['code' => $couponCode],
            [
                'discount_type' => 'percent',
                'discount_value' => 10,
                'is_active' => true,
                'min_order_total' => 0
            ]
        );

        // 3. Intentamos renderizar y enviar
        try {
            $apiKey = config('services.brevo.key') ?? env('BREVO_KEY');

            // INTENTO 1: Renderizar la vista
            if (!view()->exists('emails.welcome')) {
                return response()->json([
                    'message' => 'ERROR: No encuentro el archivo resources/views/emails/welcome.blade.php'
                ], 500);
            }

            $htmlContent = view('emails.welcome', [
                'couponCode' => $coupon->code,
                'discount'   => $coupon->discount_value,
                'minTotal'   => $coupon->min_order_total ?? 0
            ])->render();

            // INTENTO 2: Enviar a Brevo vía API
            $response = Http::withHeaders([
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => ['name' => 'Be Urban', 'email' => 'tiendamoda.ua@gmail.com'],
                'to' => [['email' => $request->email]],
                'subject' => '¡Bienvenido a Be Urban! Tu regalo dentro 🎁',
                'htmlContent' => $htmlContent
            ]);

            // Verificamos respuesta de Brevo
            if ($response->successful()) {
                return response()->json(['message' => '¡Suscripción exitosa! Revisa tu correo.']);
            } else {
                return response()->json([
                    'message' => 'Error de Brevo',
                    'error_details' => $response->body()
                ], 500);
            }

        } catch (\Exception $e) {
            // Error técnico del servidor
            return response()->json([
                'message' => 'Error Interno del Servidor',
                'technical_error' => $e->getMessage()
            ], 500);
        }
    }
}
