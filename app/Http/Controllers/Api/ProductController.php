<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = $request->user()->getActiveStore()->products()
            ->with('variants')
            ->latest()
            ->get();

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'style' => 'nullable|string|max:255',
            'collection' => 'nullable|string|max:255',
            'is_published' => 'boolean',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'variants' => 'required|array|min:1',
            'variants.*.size' => 'required|string|max:50',
            'variants.*.quantity' => 'required|integer|min:0',
        ]);

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('products', 'public');
            }
        }

        $product = $request->user()->getActiveStore()->products()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'category' => $validated['category'] ?? null,
            'style' => $validated['style'] ?? null,
            'collection' => $validated['collection'] ?? null,
            'is_published' => $validated['is_published'] ?? false,
            'images' => $imagePaths,
        ]);

        foreach ($validated['variants'] as $variant) {
            $product->variants()->create($variant);
        }

        return response()->json($product->load('variants'), 201);
    }

    public function show(Request $request, Product $product)
    {
        $this->authorizeProduct($request, $product);

        return response()->json($product->load('variants'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeProduct($request, $product);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'category' => 'sometimes|nullable|string|max:255',
            'style' => 'sometimes|nullable|string|max:255',
            'collection' => 'sometimes|nullable|string|max:255',
            'is_published' => 'sometimes|boolean',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'variants' => 'sometimes|array|min:1',
            'variants.*.id' => 'nullable|integer|exists:product_variants,id',
            'variants.*.size' => 'required_with:variants|string|max:50',
            'variants.*.quantity' => 'required_with:variants|integer|min:0',
        ]);

        if ($request->hasFile('images')) {
            foreach ($product->images ?? [] as $oldImage) {
                Storage::disk('public')->delete($oldImage);
            }
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('products', 'public');
            }
            $validated['images'] = $imagePaths;
        }

        $product->update($validated);

        if (isset($validated['variants'])) {
            $product->variants()->delete();
            foreach ($validated['variants'] as $variant) {
                $product->variants()->create([
                    'size' => $variant['size'],
                    'quantity' => $variant['quantity'],
                ]);
            }
        }

        return response()->json($product->load('variants'));
    }

    public function destroy(Request $request, Product $product)
    {
        $this->authorizeProduct($request, $product);

        foreach ($product->images ?? [] as $image) {
            Storage::disk('public')->delete($image);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function togglePublish(Request $request, Product $product)
    {
        $this->authorizeProduct($request, $product);

        $product->update(['is_published' => ! $product->is_published]);

        return response()->json($product->fresh()->load('variants'));
    }

    private function authorizeProduct(Request $request, Product $product): void
    {
        if ($product->store_id !== $request->user()->getActiveStore()->id) {
            abort(403, 'Unauthorized');
        }
    }
}
