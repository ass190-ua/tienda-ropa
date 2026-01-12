<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index()
    {
        // Ordenamos primero por 'is_admin' (descendente para que true/1 salga antes)
        // Y luego por fecha de creación (los más nuevos primero)
        return User::orderBy('is_admin', 'desc')
                   ->orderBy('created_at', 'desc')
                   ->get();
    }

    public function toggleActive($id)
    {
        $user = User::findOrFail($id);

        // Evitar que el admin se bloquee a sí mismo
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'No puedes bloquearte a ti mismo'], 400);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json(['message' => 'Estado actualizado', 'is_active' => $user->is_active]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'No puedes eliminarte a ti mismo'], 400);
        }

        $user->delete();
        return response()->json(['message' => 'Usuario eliminado']);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // Importante: ignorar el email del propio usuario al comprobar duplicados
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8', // La contraseña es opcional al editar
            'is_admin' => 'boolean',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->is_admin = $validated['is_admin'] ?? false;

        // Solo actualizamos la contraseña si escribieron una nueva
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json($user);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8', // El admin asigna una contraseña inicial
            'is_admin' => 'boolean', // Recibiremos true/false
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin'] ?? false,
            'is_active' => true,
            'email_verified_at' => now(), // Lo marcamos como verificado directamente
        ]);

        return response()->json($user, 201);
    }
}
