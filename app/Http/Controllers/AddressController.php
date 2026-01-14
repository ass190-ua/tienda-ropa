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
    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'line1' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:50'],
            'country' => ['required', 'string', 'max:255'],
        ]);
    }

    public function saveShipping(Request $request)
    {
        $user = $request->user();
        $data = $this->validateAddress($request);

        $address = Address::updateOrCreate(
            ['user_id' => $user->id, 'type' => 'shipping'],
            $data + ['type' => 'shipping']
        );

        return response()->json($address->only(['id', 'type', 'line1', 'city', 'zip', 'country']));
    }

    public function saveBilling(Request $request)
    {
        $user = $request->user();
        $data = $this->validateAddress($request);

        $address = Address::updateOrCreate(
            ['user_id' => $user->id, 'type' => 'billing'],
            $data + ['type' => 'billing']
        );

        return response()->json($address->only(['id', 'type', 'line1', 'city', 'zip', 'country']));
    }
}
