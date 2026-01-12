<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coupon;

class CouponController extends Controller
{
    public function validateCoupon(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $code = trim($data['code']);
        $subtotal = round((float)$data['subtotal'], 2);

        $coupon = Coupon::query()
            ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
            ->first();

        if (!$coupon || !$coupon->is_active) {
            return response()->json(['error' => 'Cupón no válido o inactivo.'], 422);
        }

        $now = now();

        if ($coupon->start_date && $now->lt($coupon->start_date)) {
            return response()->json(['error' => 'Este cupón aún no está activo.'], 422);
        }

        if ($coupon->end_date && $now->gt($coupon->end_date)) {
            return response()->json(['error' => 'Este cupón ya ha caducado.'], 422);
        }

        if ($coupon->min_order_total !== null && $subtotal < (float)$coupon->min_order_total) {
            return response()->json([
                'error' => 'No alcanzas el mínimo para usar este cupón.',
                'min_order_total' => (float)$coupon->min_order_total,
            ], 422);
        }

        $discount = 0.0;

        if ($coupon->discount_type === Coupon::TYPE_PERCENT) {
            $discount = $subtotal * ((float)$coupon->discount_value / 100.0);
        } elseif ($coupon->discount_type === Coupon::TYPE_FIXED) {
            $discount = (float)$coupon->discount_value;
        }

        $discount = round(min($subtotal, $discount), 2);

        return response()->json([
            'valid' => true,
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => (float)$coupon->discount_value,
                'min_order_total' => $coupon->min_order_total !== null ? (float)$coupon->min_order_total : null,
            ],
            'discount_amount' => $discount,
            'subtotal' => $subtotal,
            'subtotal_after_discount' => round($subtotal - $discount, 2),
        ]);
    }
}
