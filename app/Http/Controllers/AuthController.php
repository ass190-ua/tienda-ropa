<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    // REGISTRO
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Autenticar automáticamente tras el registro
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Usuario registrado exitosamente',
            'user' => $user
        ]);
    }

    // LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login exitoso',
            'user' => Auth::user()
        ]);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Sesión cerrada']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Laravel tiene un sistema nativo para enviar el link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => __($status)]);
        }

        // Si falla (ej. el email no existe), lanzamos error
        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    // Redirigir a Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    // Callback de Google
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                ]
            );

            // Ahora esto SÍ funcionará porque el middleware 'web' ha activado la sesión
            Auth::login($user, true);
            request()->session()->regenerate();

            return redirect('/');
        } catch (\Exception $e) {
            // Puedes dejar el dd($e) un momento más si quieres asegurar,
            // pero si todo va bien, deberías redirigir al login:
            return redirect('/login?error=google_failed');
        }
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->update($data);

        return response()->json($user->fresh());
    }

    public function destroyMe(Request $request)
    {
        $user = $request->user();

        DB::transaction(function () use ($user) {

            // 1) Pedidos del usuario
            $orderIds = Order::where('user_id', $user->id)->pluck('id');

            // 2) Borrar pagos y líneas de pedido (por si no hay cascade)
            if ($orderIds->isNotEmpty()) {
                Payment::whereIn('order_id', $orderIds)->delete();
                OrderLine::whereIn('order_id', $orderIds)->delete();
                Order::whereIn('id', $orderIds)->delete();
            }

            // 3) Ahora ya se pueden borrar direcciones (si no, restrict en orders.address_id te rompería)
            $user->addresses()->delete();

            // 4) Wishlist y sus items
            if ($user->wishlist) {
                $user->wishlist->items()->delete();
                $user->wishlist->delete();
            }

            // 5) Cart y sus items
            if ($user->cart) {
                $user->cart->items()->delete();
                $user->cart->delete();
            }

            // 6) Reviews
            $user->reviews()->delete();

            // 7) Usuario
            $user->delete();
        });

        // Logout / limpiar sesión (SPA sanctum)
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }
}
