<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin {
    public function handle(Request $request, Closure $next): Response {
        // Verificamos si hay usuario y si es admin
        if (! $request->user() || ! $request->user()->is_admin) {
            // Si es una petición JSON (API), devolvemos 403
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Acceso denegado. Se requieren permisos de administrador.'], 403);
            }
            // Si intentan entrar por navegador, redirigir a home
            return redirect('/');
        }

        // Verificamos si está baneado (por seguridad extra)
        if (isset($request->user()->is_active) && !$request->user()->is_active) {
             return response()->json(['message' => 'Tu cuenta ha sido desactivada.'], 403);
        }

        return $next($request);
    }
}
