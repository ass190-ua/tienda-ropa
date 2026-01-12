<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ReviewService
{
    /**
     * Lista reviews de un producto.
     * - Argumento para mostrar todas (caso admin y pruebas) o solo las aceptadas (caso vista producto)
     */
    public function listForProduct(Product $product, bool $includeUnapproved = true): Collection
    {

        $query = $product->reviews()
            ->with('user')
            ->latest(); // ORDER BY created_at DESC

        if (!$includeUnapproved) {
            $query->where('status', Review::STATUS_APPROVED);
        }

        return $query->get();
    }

    /**
     * Crea una review para un producto.
     * Por defecto se guarda como "pending" para q la acepte el admin.
     */
    public function createForProduct(User $user, Product $product, int $rating, ?string $comment): Review
    {
        //comprobamos valoracion entre 1-5
        $rating = max(1, min(5, $rating));

        return $product->reviews()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => $rating,
            'comment' => $comment,
            'status' => Review::STATUS_PENDING,
        ]);
    }

    /**
     * Aprueba una review.
     */
    public function approve(Review $review): Review
    {
        return $this->setStatus($review, Review::STATUS_APPROVED);
    }

    /**
     * Rechaza una review (no se borra).
     */
    public function reject(Review $review): void
    {
        $review->delete();
    }

    //CAmbiamos estado de la review
    public function setStatus(Review $review, string $status): Review
    {
        $review->status = $status;
        $review->save();

        return $review;
    }

    public function updateByUser(User $user, Review $review, int $rating, ?string $comment): Review 
    {
        //solo el autor puede editar
        if ((int)$review->user_id !== (int)$user->id) {
            abort(403, 'No puedes editar esta valoración.');
        }

        $rating = max(1, min(5, $rating));

        $review->rating = $rating;
        $review->comment = $comment;

        // Si estaba aprobada, al editar pasa a pending para re-moderación
        $review->status = Review::STATUS_PENDING;

        $review->save();

        return $review;
    }
}
