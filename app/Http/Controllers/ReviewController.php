<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{

    public function __construct(private ReviewService $reviewService) {}

    /**
     * GET /api/products/{product}/reviews
     * Público (logueado o no): devuelve reviews del producto.
     */
    public function index(Product $product)
    {
        $reviews = $this->reviewService->listForProduct($product);
        //como admin se tiene q llamar: $reviews 
        //$this->reviewService->listForProduct($product, true);


        return response()->json($reviews);
    }

    /**
     * POST /api/products/{product}/reviews
     * Solo autenticado.
     */
    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        $review = $this->reviewService->createForProduct(
            $user,
            $product,
            (int) $data['rating'],
            $data['comment'] ?? null
        );

        $review->load('user');

        return response()->json($review, 201);
    }

    /**
     * PATCH /api/admin/reviews/{review}/approve
     * (ADMIN)
     */
    public function approve(Review $review)
    {
        $updated = $this->reviewService->approve($review);

        return response()->json($updated);
    }

    /**
     * PATCH /api/admin/reviews/{review}/reject
     * (ADMIN)
     */
    public function reject(Review $review)
    {
        $this->reviewService->reject($review);
        return response()->noContent(); //204
    }

    /**
     * PATCH /api/products/{product}/reviews/{review}
     * Usuario autenticado puede editar su propia review
     */
    public function update(Request $request, Product $product, Review $review)
    {
        // Aseguramos que la review pertenece a ese producto
        if ((int)$review->product_id !== (int)$product->id) {
            abort(404);
        }

        $data = $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $updated = $this->reviewService->updateByUser(
            $request->user(),
            $review,
            (int)$data['rating'],
            $data['comment'] ?? null
        );

        $updated->load('user');

        return response()->json($updated);
    }

}
