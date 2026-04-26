<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    protected function ownerStore(Request $request)
    {
        $user = $request->user();

        abort_unless($user, 401, 'Unauthenticated.');
        abort_unless($user->isOwner(), 403, 'Only store owners can manage staff.');

        $store = $user->getActiveStore();
        abort_unless($store, 422, 'No store is linked to this account.');

        return $store;
    }

    // Owner views all staff for their store
    public function index(Request $request)
    {
        $store = $this->ownerStore($request);

        $staff = User::where('role', 'staff')
            ->where('store_id', $store->id)
            ->latest()
            ->get();

        return response()->json($staff);
    }

    // Owner creates a staff account
    public function store(Request $request)
    {
        $store = $this->ownerStore($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $staff = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'staff',
            'is_active' => true,
            'store_id' => $store->id,
        ]);

        return response()->json($staff, 201);
    }

    // Owner removes a staff member
    public function destroy(Request $request, User $user)
    {
        $store = $this->ownerStore($request);

        if ($user->role !== 'staff' || $user->store_id !== $store->id) {
            abort(403, 'Unauthorized');
        }

        $user->delete();

        return response()->json(['message' => 'Staff member removed.']);
    }

    // Owner toggles staff active status
    public function toggleActive(Request $request, User $user)
    {
        $store = $this->ownerStore($request);

        if ($user->role !== 'staff' || $user->store_id !== $store->id) {
            abort(403, 'Unauthorized');
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json($user->fresh());
    }
}
