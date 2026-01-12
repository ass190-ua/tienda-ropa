<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Review;

class AdminReviewController extends Controller
{
    // 1. MOSTRAR LISTA DE RESEÑAS
    public function index()
    {
        // Cargamos producto y usuario para mostrar nombres y fotos en la tabla
        $reviews = Review::with(['user', 'product'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($reviews);
    }

    // 2. APROBAR (Cambiar estado a 'approved')
    public function approve($id)
    {
        $review = Review::findOrFail($id);

        // Usamos 'status' como aparece en tu migración
        $review->update(['status' => 'approved']);

        return response()->json(['message' => 'Reseña aprobada correctamente', 'review' => $review]);
    }

    // 3. RECHAZAR / ELIMINAR
    public function reject($id)
    {
        $review = Review::findOrFail($id);
        $review->delete();

        return response()->json(['message' => 'Reseña eliminada correctamente']);
    }
}
