<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function ($user, string $token) {
            // Ajusta la URL base según tu entorno (local o producción)
            // Normalmente en local es http://localhost:5173 o lo que tengas en tu .env frontend
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

            return $frontendUrl . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
        });

        Mail::extend('brevo', function () {
            return (new BrevoTransportFactory)->create(
                new Dsn(
                    'brevo+api', // Usamos API (puerto 443) para evitar bloqueo de Render
                    'default',
                    config('services.brevo.key') // La clave que pusimos en config/services.php
                )
            );
        });
    }
}
