<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Address;

class AddressController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();

        $shipping = Address::where('user_id', $user->id)
            ->where('type', 'shipping')
            ->latest('id')
            ->first();

        $billing = Address::where('user_id', $user->id)
            ->where('type', 'billing')
            ->latest('id')
            ->first();

        return response()->json([
            'shipping' => $shipping ? $shipping->only(['line1', 'city', 'zip', 'country']) : null,
            'billing' => $billing ? $billing->only(['line1', 'city', 'zip', 'country']) : null,
        ]);
    }
}
