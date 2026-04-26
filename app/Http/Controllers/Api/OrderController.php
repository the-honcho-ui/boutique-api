<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Reservation;
use App\Models\Store;
use App\Support\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->getActiveStore()->orders()
            ->with('items')
            ->latest()
            ->get();

        return response()->json($orders);
    }

    public function show(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        return response()->json($order->load('items'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,shipped,delivered',
        ]);

        $order->update($validated);

        return response()->json($order->fresh()->load('items'));
    }

    public function storeWhatsapp(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $store = $request->user()->getActiveStore();
        ReservationService::validateAvailability($validated['items']);

        DB::transaction(function () use ($validated, $store, &$order) {
            $totalAmount = 0;
            $orderItems = [];

            foreach ($validated['items'] as $item) {
                $variant = ProductVariant::with('product')->findOrFail($item['product_variant_id']);
                $variant->decrement('quantity', $item['quantity']);

                $subtotal = $variant->product->price * $item['quantity'];
                $totalAmount += $subtotal;

                $orderItems[] = [
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'size' => $variant->size,
                    'quantity' => $item['quantity'],
                    'unit_price' => $variant->product->price,
                ];
            }

            $order = $store->orders()->create([
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'total_amount' => $totalAmount,
                'payment_method' => 'whatsapp',
                'status' => 'confirmed',
            ]);

            $order->items()->createMany($orderItems);
        });

        return response()->json($order->load('items'), 201);
    }

    public function initializePaystack(Request $request, string $slug)
    {
        $validated = $request->validate([
            'session_token' => 'required|string|max:100',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $store = Store::where('slug', $slug)->firstOrFail();

        if (! $store->paystack_secret_key) {
            return response()->json([
                'message' => 'This store has not configured online payments yet. Please use WhatsApp to order.',
            ], 422);
        }

        $reservation = ReservationService::createOrUpdateReservation(
            $store,
            $validated['items'],
            $validated['session_token'],
            'paystack',
            [
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
            ]
        );

        $response = Http::withToken($store->paystack_secret_key)
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $validated['customer_email'],
                'amount' => (int) round($reservation->total_amount * 100),
                'callback_url' => env('PAYSTACK_CALLBACK_URL') . '/' . $slug . '/payment-success',
                'metadata' => [
                    'customer_name' => $validated['customer_name'],
                    'customer_email' => $validated['customer_email'],
                    'store_id' => $store->id,
                    'reservation_id' => $reservation->id,
                    'session_token' => $validated['session_token'],
                ],
            ]);

        if (! $response->successful()) {
            return response()->json(['message' => 'Payment initialization failed'], 500);
        }

        return response()->json($response->json());
    }

    public function paystackWebhook(Request $request)
    {
        $payload = $request->all();

        if (! isset($payload['data']['metadata']['store_id'])) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $store = Store::find($payload['data']['metadata']['store_id']);

        if (! $store || ! $store->paystack_secret_key) {
            return response()->json(['message' => 'Store not found or payment not configured'], 400);
        }

        $signature = $request->header('x-paystack-signature');
        $computedSignature = hash_hmac('sha512', $request->getContent(), $store->paystack_secret_key);

        if ($signature !== $computedSignature) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        if ($payload['event'] !== 'charge.success') {
            return response()->json(['message' => 'Event ignored'], 200);
        }

        $data = $payload['data'];
        $metadata = $data['metadata'];

        if (Order::where('paystack_reference', $data['reference'])->exists()) {
            return response()->json(['message' => 'Order already processed'], 200);
        }

        $reservation = Reservation::with('items')
            ->where('id', $metadata['reservation_id'] ?? null)
            ->where('store_id', $store->id)
            ->first();

        if (! $reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        ReservationService::confirmReservation(
            $reservation,
            'paystack',
            [
                'customer_name' => $metadata['customer_name'] ?? $reservation->customer_name,
                'customer_email' => $data['customer']['email'] ?? $metadata['customer_email'] ?? $reservation->customer_email,
            ],
            $data['reference']
        );

        return response()->json(['message' => 'Order created successfully'], 200);
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        if ($order->store_id !== $request->user()->getActiveStore()->id) {
            abort(403, 'Unauthorized');
        }
    }
}
