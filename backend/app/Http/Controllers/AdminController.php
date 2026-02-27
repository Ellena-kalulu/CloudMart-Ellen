<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Get all users for admin dashboard
     */
    public function getUsers(Request $request)
    {
        $users = User::select('id', 'fullName', 'email', 'role', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }

    /**
     * Update user role
     */
    public function updateUserRole(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|in:customer,delivery_personnel,admin'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid role specified',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Prevent admin from changing their own role
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot change your own role'
            ], 400);
        }

        $oldRole = $user->role;
        $user->role = $request->role;
        $user->save();

        Log::info('User role updated', [
            'admin_id' => $request->user()->id,
            'target_user_id' => $user->id,
            'old_role' => $oldRole,
            'new_role' => $request->role
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User role updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Get system statistics
     */
    public function getStats(Request $request)
    {
        // Get basic user stats
        $userStats = [
            'total_users' => User::count(),
            'customers' => User::where('role', 'customer')->count(),
            'delivery_personnel' => User::where('role', 'delivery_personnel')->count(),
            'admins' => User::where('role', 'admin')->count(),
            'users_today' => User::whereDate('created_at', today())->count(),
        ];

        // Get order statistics
        $orderStats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
            'orders_today' => Order::whereDate('created_at', today())->count(),
        ];

        // Get sales statistics
        $salesStats = [
            'total_sales' => Order::where('status', 'delivered')->sum('total_amount'),
            'sales_today' => Order::whereDate('created_at', today())->where('status', 'delivered')->sum('total_amount'),
            'sales_this_month' => Order::whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)
                                        ->where('status', 'delivered')
                                        ->sum('total_amount'),
        ];

        // Get product statistics
        $productStats = [
            'total_products' => Product::count(),
            'active_products' => Product::where('stock', '>', 0)->count(),
            'out_of_stock' => Product::where('stock', '=', 0)->count(),
        ];

        // Combine all stats
        $stats = array_merge($userStats, $orderStats, $salesStats, $productStats);

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Get all orders for admin dashboard
     */
    public function getOrders(Request $request)
    {
        $orders = Order::with(['user', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,delivered,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status specified',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::where('order_id', $orderId)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $oldStatus = $order->status;
        $order->status = $request->status;

        // Set delivered_at timestamp if status is being changed to delivered
        if ($request->status === 'delivered' && $oldStatus !== 'delivered') {
            $order->delivered_at = now();
        }

        $order->save();

        Log::info('Order status updated', [
            'admin_id' => $request->user()->id,
            'order_id' => $order->order_id,
            'old_status' => $oldStatus,
            'new_status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'order' => $order
        ]);
    }

    /**
     * Delete a user
     */
    public function deleteUser(Request $request, $userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Prevent admin from deleting themselves
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete your own account'
            ], 400);
        }

        // Check if user has orders
        $orderCount = $user->orders()->count();
        if ($orderCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete user with existing orders. User has ' . $orderCount . ' orders.'
            ], 400);
        }

        $userName = $user->fullName;
        $user->delete();

        Log::info('User deleted', [
            'admin_id' => $request->user()->id,
            'deleted_user_id' => $userId,
            'deleted_user_name' => $userName
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
