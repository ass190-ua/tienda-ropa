<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'reason' => 'required|string',
            'message' => 'required|string',
        ]);

        try {
            $apiKey = config('services.brevo.key') ?? env('BREVO_KEY');

            // Construimos el contenido del correo manualmente
            $htmlContent = "
                <h1>Nuevo Mensaje de Contacto</h1>
                <p><strong>Nombre:</strong> {$validated['name']}</p>
                <p><strong>Email:</strong> {$validated['email']}</p>
                <p><strong>Motivo:</strong> {$validated['reason']}</p>
                <hr>
                <p><strong>Mensaje:</strong></p>
                <p>{$validated['message']}</p>
            ";

            // Enviamos usando la API directa de Brevo
            $response = Http::withHeaders([
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => ['name' => 'Be Urban', 'email' => 'tiendamoda.ua@gmail.com'], // Tu correo verificado
                'to' => [['email' => 'tiendamoda.ua@gmail.com']], // Te llega a ti mismo
                'replyTo' => ['email' => $validated['email'], 'name' => $validated['name']], // Para que puedas responderle
                'subject' => 'Nuevo Contacto: ' . $validated['reason'],
                'htmlContent' => $htmlContent
            ]);

            if ($response->successful()) {
                return response()->json(['message' => 'Mensaje enviado correctamente']);
            } else {
                // Logueamos el error para verlo en Render si falla
                \Illuminate\Support\Facades\Log::error('Error Brevo Contacto: ' . $response->body());
                return response()->json(['message' => 'Error al enviar el mensaje.'], 500);
            }

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error del servidor: ' . $e->getMessage()], 500);
        }
    }
}
