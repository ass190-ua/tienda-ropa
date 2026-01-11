<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:150'],
            'reason'  => ['required', 'string', 'max:60'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        Mail::raw(
            "Nuevo mensaje de contacto\n\n".
            "Nombre: {$data['name']}\n".
            "Email: {$data['email']}\n".
            "Motivo: {$data['reason']}\n\n".
            "Mensaje:\n{$data['message']}\n",
            function ($m) use ($data) {
                $m->to('tiendamoda.ua@gmail.com')
                  ->subject("Contacto TiendaModa: {$data['reason']}")
                  // CLAVE: al responder desde Gmail, se responde al usuario
                  ->replyTo($data['email'], $data['name']);
            }
        );

        return response()->json(['ok' => true]);
    }
}
