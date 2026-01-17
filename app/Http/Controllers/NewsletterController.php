<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscriber;
use App\Models\Coupon;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeNewsletter;
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
                'is_active' => true
            ]
        );

        // 3. Enviamos el correo con API Directa
        try {
            $apiKey = config('services.brevo.key') ?? env('BREVO_KEY');

            // --- AQUÍ ESTÁ LA MAGIA ---
            // Usamos view(...)->render() para convertir el archivo blade en texto HTML
            $htmlContent = view('emails.welcome', [
                'couponCode' => $coupon->code,
                'discount'   => $coupon->discount_value,
                'minTotal'   => $coupon->min_order_total ?? 0
            ])->render();
            // --------------------------

            Http::withHeaders([
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => ['name' => 'Be Urban', 'email' => 'tiendamoda.ua@gmail.com'],
                'to' => [['email' => $request->email]],
                'subject' => '¡Bienvenido a Be Urban! Tu regalo dentro 🎁',
                'htmlContent' => $htmlContent // Le pasamos el HTML bonito generado arriba
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error Brevo Newsletter: ' . $e->getMessage());
        }

        return response()->json(['message' => '¡Suscripción exitosa! Revisa tu correo.']);
    }
}
