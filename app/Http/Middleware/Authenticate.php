<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     // En esta app (SPA + API), no redirigimos en backend.
    // Si no hay auth, Laravel devolverá 401 en endpoints protegidos.
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}
