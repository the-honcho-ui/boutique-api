<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    private function ensureOwnerTarget(User $user): void
    {
        abort_unless($user->role === 'owner', 422, 'Only owner accounts can be managed here.');
    }

    public function stats()
    {
        $totalStores = Store::count();
        $totalOrders = Order::count();
        $totalRevenue = Order::where('payment_method', 'paystack')
            ->where('status', '!=', 'pending')
            ->sum('total_amount');
        $pendingApprovals = User::where('role', 'owner')
            ->where('is_active', false)
            ->count();
        $newSignupsThisWeek = User::where('role', 'owner')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return response()->json([
            'total_stores' => $totalStores,
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'pending_approvals' => $pendingApprovals,
            'new_signups_this_week' => $newSignupsThisWeek,
        ]);
    }

    public function owners()
    {
        $owners = User::where('role', 'owner')
            ->with('store')
            ->latest()
            ->get();

        return response()->json($owners);
    }

    public function activate(User $user)
    {
        $this->ensureOwnerTarget($user);

        $user->update(['is_active' => true]);

        return response()->json([
            'message' => 'Account activated successfully.',
            'user' => $user->fresh(),
        ]);
    }

    public function deactivate(User $user)
    {
        $this->ensureOwnerTarget($user);

        $user->update(['is_active' => false]);

        return response()->json([
            'message' => 'Account deactivated.',
            'user' => $user->fresh(),
        ]);
    }

    public function destroy(User $user)
    {
        $this->ensureOwnerTarget($user);

        $user->delete();

        return response()->json(['message' => 'Account deleted.']);
    }

    public function storeDetail(Store $store)
    {
        $store->load('user');

        $products = $store->products()->with('variants')->latest()->get();

        $orders = $store->orders()->with('items')->latest()->get();

        $revenue = $store->orders()
            ->where('payment_method', 'paystack')
            ->where('status', '!=', 'pending')
            ->sum('total_amount');

        return response()->json([
            'store' => $store,
            'products' => $products,
            'orders' => $orders,
            'revenue' => $revenue,
        ]);
    }
}
