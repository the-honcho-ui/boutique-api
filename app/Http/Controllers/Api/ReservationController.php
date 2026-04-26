<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Support\ReservationService;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isOwner(), 403, 'Only store owners can manage reservations.');

        $store = $request->user()->getActiveStore();
        ReservationService::expireStaleReservations($store);

        $reservations = $store->reservations()
            ->with(['items.productVariant.product', 'order'])
            ->latest()
            ->get();

        $waitlists = $store->waitlistEntries()
            ->with('productVariant.product')
            ->latest()
            ->get();

        return response()->json([
            'reservations' => $reservations,
            'waitlists' => $waitlists,
        ]);
    }

    public function confirm(Request $request, Reservation $reservation)
    {
        abort_unless($request->user()->isOwner(), 403, 'Only store owners can confirm reservations.');

        $store = $request->user()->getActiveStore();

        if ($reservation->store_id !== $store->id) {
            abort(403, 'Unauthorized');
        }

        $order = ReservationService::confirmReservation(
            $reservation,
            'whatsapp',
            [
                'customer_name' => $reservation->customer_name,
                'customer_email' => $reservation->customer_email,
                'customer_phone' => $reservation->customer_phone,
            ]
        );

        return response()->json($order);
    }
}
