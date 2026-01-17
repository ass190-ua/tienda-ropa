<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeNewsletter;
use Illuminate\Support\Facades\Http;

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
                'is_active' => true
            ]
        );

        // 3. Enviamos el correo con API Directa
        try {
            $apiKey = config('services.brevo.key') ?? env('BREVO_KEY');

            $htmlContent = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h1>¡Bienvenido a Be Urban!</h1>
                    <p>Gracias por suscribirte a nuestra newsletter.</p>
                    <p>Aquí tienes tu regalo de bienvenida:</p>
                    <div style='background: #fdf2f8; padding: 20px; text-align: center; border: 2px dashed #db2777; margin: 20px 0;'>
                        <h2 style='color: #db2777; margin: 0;'>{$coupon->code}</h2>
                        <p style='margin-top: 5px;'>Usa este código para obtener un <strong>{$coupon->discount_value}% de descuento</strong>.</p>
                    </div>
                    <p>¡Esperamos verte pronto!</p>
                </div>
            ";

            Http::withHeaders([
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => ['name' => 'Be Urban', 'email' => 'tiendamoda.ua@gmail.com'],
                'to' => [['email' => $request->email]], // Se envía al usuario
                'subject' => '¡Tu cupón de bienvenida!',
                'htmlContent' => $htmlContent
            ]);

        } catch (\Exception $e) {
            // Si falla el correo, no bloqueamos el registro, solo lo anotamos
            \Illuminate\Support\Facades\Log::error('Error Brevo Newsletter: ' . $e->getMessage());
        }

        return response()->json(['message' => '¡Suscripción exitosa! Revisa tu correo.']);
    }
}
