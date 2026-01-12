<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Review;

class AdminReviewController extends Controller
{
    public function approve($id)
    {
        $review = Review::findOrFail($id);
        // $review->update(['is_approved' => true]);
        return response()->json(['message' => 'Reseña aprobada (Simulado)', 'review' => $review]);
    }

    public function reject($id)
    {
        $review = Review::findOrFail($id);
        $review->delete();
        return response()->json(['message' => 'Reseña eliminada correctamente']);
    }
}
