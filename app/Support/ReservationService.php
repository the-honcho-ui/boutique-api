<?php

namespace App\Support;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Reservation;
use App\Models\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public static function expireStaleReservations(?Store $store = null): void
    {
        $query = Reservation::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());

        if ($store) {
            $query->where('store_id', $store->id);
        }

        $query->update(['status' => 'expired']);
    }

    public static function activeReservedQuantity(int $variantId, ?int $ignoreReservationId = null): int
    {
        self::expireStaleReservations();

        return (int) DB::table('reservation_items')
            ->join('reservations', 'reservations.id', '=', 'reservation_items.reservation_id')
            ->where('reservation_items.product_variant_id', $variantId)
            ->where('reservations.status', 'active')
            ->where(function ($query) {
                $query->whereNull('reservations.expires_at')
                    ->orWhere('reservations.expires_at', '>', now());
            })
            ->when($ignoreReservationId, fn ($query) => $query->where('reservations.id', '!=', $ignoreReservationId))
            ->sum('reservation_items.quantity');
    }

    public static function availableQuantity(ProductVariant $variant, ?int $ignoreReservationId = null): int
    {
        return max(0, $variant->quantity - self::activeReservedQuantity($variant->id, $ignoreReservationId));
    }

    public static function validateAvailability(array $items, ?int $ignoreReservationId = null): array
    {
        $resolved = [];
        $grouped = collect($items)->groupBy('product_variant_id');

        foreach ($grouped as $variantId => $variantItems) {
            $requested = (int) $variantItems->sum('quantity');
            $variant = ProductVariant::with('product')->findOrFail($variantId);
            $available = self::availableQuantity($variant, $ignoreReservationId);

            if ($requested > $available) {
                throw ValidationException::withMessages([
                    'items' => ["{$variant->product->name} ({$variant->size}) only has {$available} item(s) available right now."],
                ]);
            }

            foreach ($variantItems as $item) {
                $resolved[] = [
                    'variant' => $variant,
                    'quantity' => (int) $item['quantity'],
                ];
            }
        }

        return $resolved;
    }

    public static function createOrUpdateReservation(
        Store $store,
        array $items,
        string $sessionToken,
        string $source,
        array $customer = [],
        ?Reservation $reservation = null
    ): Reservation {
        self::expireStaleReservations($store);

        if (! $reservation) {
            $reservation = Reservation::where('store_id', $store->id)
                ->where('session_token', $sessionToken)
                ->where('status', 'active')
                ->latest()
                ->first();
        }

        $resolvedItems = self::validateAvailability($items, $reservation?->id);
        $totalAmount = collect($resolvedItems)->sum(fn ($item) => $item['variant']->product->price * $item['quantity']);

        return DB::transaction(function () use ($store, $resolvedItems, $sessionToken, $source, $customer, $reservation, $totalAmount) {
            $reservation ??= Reservation::create([
                'store_id' => $store->id,
                'session_token' => $sessionToken,
                'source' => $source,
                'status' => 'active',
            ]);

            $reservation->update([
                'source' => $source,
                'customer_name' => $customer['customer_name'] ?? $reservation->customer_name,
                'customer_email' => $customer['customer_email'] ?? $reservation->customer_email,
                'customer_phone' => $customer['customer_phone'] ?? $reservation->customer_phone,
                'total_amount' => $totalAmount,
                'status' => 'active',
                'expires_at' => Carbon::now()->addMinutes(30),
            ]);

            $reservation->items()->delete();
            $reservation->items()->createMany(array_map(function ($item) {
                return [
                    'product_variant_id' => $item['variant']->id,
                    'product_name' => $item['variant']->product->name,
                    'size' => $item['variant']->size,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['variant']->product->price,
                ];
            }, $resolvedItems));

            return $reservation->fresh('items.productVariant.product');
        });
    }

    public static function confirmReservation(Reservation $reservation, string $paymentMethod, array $customer = [], ?string $paystackReference = null): Order
    {
        self::expireStaleReservations($reservation->store);
        $reservation->loadMissing('items.productVariant.product');

        if ($reservation->status !== 'active') {
            throw ValidationException::withMessages([
                'reservation' => ['This reservation is no longer active.'],
            ]);
        }

        $resolvedItems = self::validateAvailability(
            $reservation->items->map(fn ($item) => [
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
            ])->all(),
            $reservation->id
        );

        return DB::transaction(function () use ($reservation, $paymentMethod, $customer, $paystackReference, $resolvedItems) {
            foreach ($resolvedItems as $item) {
                $item['variant']->decrement('quantity', $item['quantity']);
            }

            $order = $reservation->order;

            if (! $order) {
                $order = $reservation->store->orders()->create([
                    'customer_name' => $customer['customer_name'] ?? $reservation->customer_name ?? 'WhatsApp Customer',
                    'customer_email' => $customer['customer_email'] ?? $reservation->customer_email,
                    'customer_phone' => $customer['customer_phone'] ?? $reservation->customer_phone,
                    'total_amount' => $reservation->total_amount,
                    'payment_method' => $paymentMethod,
                    'status' => 'confirmed',
                    'paystack_reference' => $paystackReference,
                ]);

                $order->items()->createMany($reservation->items->map(fn ($item) => [
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->product_name,
                    'size' => $item->size,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                ])->all());
            } else {
                $order->update([
                    'customer_name' => $customer['customer_name'] ?? $order->customer_name,
                    'customer_email' => $customer['customer_email'] ?? $order->customer_email,
                    'customer_phone' => $customer['customer_phone'] ?? $order->customer_phone,
                    'payment_method' => $paymentMethod,
                    'status' => 'confirmed',
                    'paystack_reference' => $paystackReference ?? $order->paystack_reference,
                    'total_amount' => $reservation->total_amount,
                ]);
            }

            $reservation->update([
                'order_id' => $order->id,
                'status' => 'confirmed',
                'expires_at' => null,
            ]);

            return $order->fresh('items');
        });
    }
}
