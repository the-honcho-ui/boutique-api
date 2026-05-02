<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use App\Support\ReservationService;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function show(string $slug)
    {
        $store = Store::where('slug', $slug)->firstOrFail();
        ReservationService::expireStaleReservations($store);

        return response()->json([
            ...$store->toArray(),
            'has_paystack' => ! empty($store->paystack_secret_key),
        ]);
    }

    public function products(Request $request, string $slug)
    {
        $store = Store::where('slug', $slug)->firstOrFail();
        ReservationService::expireStaleReservations($store);

        $query = $store->products()
            ->where('is_published', true)
            ->with('variants');

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('size')) {
            $query->whereHas('variants', function ($q) use ($request) {
                $q->where('size', $request->size)->where('quantity', '>', 0);
            });
        }

        $products = $query->latest()->get();

        return response()->json($products->map(fn ($product) => $this->transformProduct($product)));
    }

    public function product(string $slug, Product $product)
    {
        $store = Store::where('slug', $slug)->firstOrFail();
        ReservationService::expireStaleReservations($store);

        if ($product->store_id !== $store->id || ! $product->is_published) {
            abort(404);
        }

        $product->load('variants');
        $recommendations = $store->products()
            ->where('is_published', true)
            ->whereKeyNot($product->id)
            ->with('variants')
            ->get()
            ->map(fn (Product $candidate) => [
                'product' => $candidate,
                'score' => $this->getRecommendationScore($product, $candidate),
            ])
            ->filter(fn (array $entry) => $entry['score'] > 0)
            ->sortByDesc('score')
            ->take(4)
            ->map(fn (array $entry) => $this->transformProduct($entry['product']))
            ->values()
            ->all();

        return response()->json([
            ...$this->transformProduct($product),
            'recommendations' => $recommendations,
        ]);
    }

    public function categories(string $slug)
    {
        $store = Store::where('slug', $slug)->firstOrFail();

        $categories = $store->products()
            ->where('is_published', true)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return response()->json($categories);
    }

    private function transformProduct(Product $product): array
    {
        $productArray = $product->toArray();
        $productArray['tags'] = array_values(array_filter([
            $product->category,
            $product->style,
            $product->collection,
        ]));
        $productArray['variants'] = $product->variants->map(function ($variant) {
            $availableQuantity = ReservationService::availableQuantity($variant);

            return [
                ...$variant->toArray(),
                'available_quantity' => $availableQuantity,
                'is_reserved' => $availableQuantity < $variant->quantity,
                'is_sold_out' => $availableQuantity === 0,
            ];
        })->all();

        return $productArray;
    }

    private function getRecommendationScore(Product $currentProduct, Product $candidate): int
    {
        $score = 0;

        if ($currentProduct->category && $currentProduct->category === $candidate->category) {
            $score += 3;
        }

        if ($currentProduct->style && $currentProduct->style === $candidate->style) {
            $score += 2;
        }

        if ($currentProduct->collection && $currentProduct->collection === $candidate->collection) {
            $score += 2;
        }

        return $score;
    }
}
