<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Mail\NewOwnerSignup;

class AuthController extends Controller
{
    public function me(Request $request)
{
    $user = $request->user();
    $store = $user->getActiveStore();

    return response()->json([
        'user' => $user->only(['id', 'name', 'email', 'role', 'is_active', 'created_at']),
        'store' => $store,
    ]);
}

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'store_name' => 'required|string|max:255',
            'whatsapp_number' => 'required|string|max:20',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'owner',
            'is_active' => false,
        ]);

        $slug = str($validated['store_name'])->slug();
        $originalSlug = $slug;
        $count = 1;

        while (\App\Models\Store::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        $user->store()->create([
            'name' => $validated['store_name'],
            'slug' => $slug,
            'whatsapp_number' => $validated['whatsapp_number'],
        ]);

        Mail::to(env('SUPERADMIN_EMAIL'))->send(new NewOwnerSignup($user));

        return response()->json([
            'message' => 'Account created successfully. Please wait for approval before logging in.',
            'pending' => true,
        ], 201);
    }

    public function login(Request $request)
{
    $validated = $request->validate([
        'email' => 'required|string|email',
        'password' => 'required|string',
    ]);

    $user = User::where('email', $validated['email'])->first();

    if (! $user || ! Hash::check($validated['password'], $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    if (! $user->is_active) {
        return response()->json([
            'message' => 'Your account is pending approval. You will be notified once activated.',
            'pending' => true,
        ], 403);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    $store = $user->getActiveStore();

    return response()->json([
        'user' => $user->only(['id', 'name', 'email', 'role', 'is_active', 'created_at']),
        'store' => $store,
        'token' => $token,
    ]);
}

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
