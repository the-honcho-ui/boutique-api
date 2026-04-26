<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\Reservation;
use App\Models\Store;
use App\Models\WaitlistEntry;
use App\Support\ReservationService;
use Illuminate\Http\Request;

class StorefrontReservationController extends Controller
{
    public function reserveWhatsapp(Request $request, string $slug)
    {
        $validated = $request->validate([
            'session_token' => 'required|string|max:100',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $store = Store::where('slug', $slug)->firstOrFail();
        $reservation = ReservationService::createOrUpdateReservation(
            $store,
            $validated['items'],
            $validated['session_token'],
            'whatsapp',
            [
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
            ]
        );

        return response()->json([
            'reservation' => $reservation,
            'expires_at' => $reservation->expires_at,
        ], 201);
    }

    public function waitlist(Request $request, string $slug)
    {
        $validated = $request->validate([
            'product_variant_id' => 'required|integer|exists:product_variants,id',
            'email' => 'required|email|max:255',
        ]);

        $store = Store::where('slug', $slug)->firstOrFail();
        $variant = ProductVariant::with('product')->findOrFail($validated['product_variant_id']);

        if ($variant->product->store_id !== $store->id) {
            abort(404);
        }

        $entry = WaitlistEntry::firstOrCreate(
            [
                'store_id' => $store->id,
                'product_variant_id' => $variant->id,
                'email' => $validated['email'],
                'status' => 'active',
            ]
        );

        return response()->json($entry, 201);
    }
}
