<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreController extends Controller
{
    public function show(Request $request)
    {
        return response()->json($this->storeResponse($request->user()->getActiveStore()));
    }

    public function update(Request $request)
    {
        $store = $request->user()->getActiveStore();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'whatsapp_number' => 'sometimes|string|max:20',
            'description' => 'sometimes|nullable|string',
            'logo' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'banner' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'paystack_secret_key' => ['sometimes', 'nullable', 'string', 'regex:/^(sk)_(test|live)_[A-Za-z0-9]+$/'],
            'paystack_public_key' => ['sometimes', 'nullable', 'string', 'regex:/^(pk)_(test|live)_[A-Za-z0-9]+$/'],
        ]);

        if ($request->hasFile('logo')) {
            if ($store->logo) {
                Storage::disk('public')->delete($store->logo);
            }
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        }

        if ($request->hasFile('banner')) {
            if ($store->banner) {
                Storage::disk('public')->delete($store->banner);
            }
            $validated['banner'] = $request->file('banner')->store('banners', 'public');
        }

        $store->update($validated);

        return response()->json($this->storeResponse($store->fresh()));
    }

    private function storeResponse($store): array
    {
        return [
            'id' => $store->id,
            'user_id' => $store->user_id,
            'name' => $store->name,
            'slug' => $store->slug,
            'whatsapp_number' => $store->whatsapp_number,
            'logo' => $store->logo,
            'banner' => $store->banner,
            'description' => $store->description,
            'has_paystack' => ! empty($store->paystack_secret_key),
            'created_at' => $store->created_at,
            'updated_at' => $store->updated_at,
        ];
    }
}
