<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\Product;
use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    
    public function index(Request $request)
    {
        $user = $request->user();
        
        $orders = Order::where('user_id', $user->id)
            ->with(['items.product.category'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders,
            'count' => $orders->count()
        ]);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'delivery_address' => 'required|string',
            'delivery_location' => 'required|string',
            'phone' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        $cart = Cart::where('user_id', $user->id)->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty'
            ], 400);
        }

        // Validate location
        if (!$this->validateLocation($request->latitude, $request->longitude, $request->delivery_location)) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery location is outside our service area. We only deliver to Mzuzu University and surrounding areas.'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Check stock availability for all items
            foreach ($cart->items as $item) {
                if ($item->product->stock_quantity < $item->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for {$item->product->name}. Only {$item->product->stock_quantity} available."
                    ], 400);
                }
            }

            // Calculate total
            $subtotal = $cart->items->sum(function($item) {
                return $item->quantity * $item->price;
            });

            // Create order with unique order ID
            $order = Order::create([
                'user_id' => $user->id,
                'order_id' => $this->generateOrderId(),
                'total_amount' => $subtotal,
                'delivery_address' => $request->delivery_address,
                'delivery_location' => $request->delivery_location,
                'phone' => $request->phone,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'notes' => $request->notes,
                'status' => 'pending',
                'payment_status' => 'paid' 
            ]);

            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price
                ]);

                
                $product = Product::find($cartItem->product_id);
                $product->decreaseStock($cartItem->quantity);
            }

            // Clear cart
            $cart->items()->delete();
            $cart->total = 0;
            $cart->save();

            DB::commit();

            // TODO: Send email/SMS notification with order_id
            // $this->sendOrderNotification($order);

            $order->load(['items.product.category', 'user']);

            // Send SMS and Email notifications
            try {
                $notificationController = new NotificationController();
                $notifications = $notificationController->sendOrderNotifications($order);
                
                Log::info('Order notifications sent', [
                    'order_id' => $order->order_id,
                    'sms_sent' => $notifications['sms'],
                    'email_sent' => $notifications['email']
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send order notifications: ' . $e->getMessage());
                
            }

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'order' => $order,
                'order_id' => $order->order_id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, $orderId)
    {
        $order = Order::where('order_id', $orderId)
            ->with(['items.product.category', 'user'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order' => $order
        ]);
    }

    public function confirmDelivery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
            'collector_phone' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::where('order_id', $request->order_id)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }
        if ($order->phone !== $request->collector_phone) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number does not match order records'
            ], 400);
        }

        if ($order->status === 'delivered') {
            return response()->json([
                'success' => false,
                'message' => 'Order already delivered'
            ], 400);
        }

        $order->status = 'delivered';
        $order->delivered_at = now();
        $order->save();

        // Send delivery confirmation notification
        try {
            $notificationController = new NotificationController();
            $notifications = $notificationController->sendDeliveryConfirmation($order);
            
            Log::info('Delivery confirmation notifications sent', [
                'order_id' => $order->order_id,
                'sms_sent' => $notifications['sms'],
                'email_sent' => $notifications['email']
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send delivery confirmation: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Delivery confirmed successfully',
            'order' => $order
        ]);
    }

    public function cancel(Request $request, $orderId)
    {
        $user = $request->user();
        
        $order = Order::where('user_id', $user->id)
            ->where('order_id', $orderId)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel order that is already being processed'
            ], 400);
        }

        DB::beginTransaction();

        try {
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);
                $product->increaseStock($item->quantity);
            }

            $order->status = 'cancelled';
            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order: ' . $e->getMessage()
            ], 500);
        }
    }

   
    private function generateOrderId()
    {
        do {
            // Format: CLM-YYYYMMDD-XXXX (e.g., CLM-20240215-A3B9)
            $orderId = 'CLM-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        } while (Order::where('order_id', $orderId)->exists());

        return $orderId;
    }


    private function validateLocation($latitude, $longitude, $deliveryLocation = null)
    {
        // Log the incoming coordinates for debugging
        Log::info('Location validation attempt', [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'delivery_location' => $deliveryLocation
        ]);

        // Define allowed locations with coordinates and radius (in km)
        $allowedLocations = [
            ['name' => 'Mzuzu University', 'lat' => -11.4477, 'lng' => 34.0167, 'radius' => 5],
            ['name' => 'Mzuzu Central Hospital', 'lat' => -11.4593, 'lng' => 34.0151, 'radius' => 1],
            ['name' => 'Luwinga', 'lat' => -11.4612, 'lng' => 34.0189, 'radius' => 1.5],
            ['name' => 'Area 1B', 'lat' => -11.4523, 'lng' => 34.0213, 'radius' => 1],
            ['name' => 'KAKA', 'lat' => -11.4489, 'lng' => 34.0156, 'radius' => 1],
        ];

        // Special case: If user explicitly selected "Mzuzu University", be more lenient
        if ($deliveryLocation === 'Mzuzu University') {
            $mzuzuUni = $allowedLocations[0]; // Mzuzu University is first in array
            $distance = $this->calculateDistance($latitude, $longitude, $mzuzuUni['lat'], $mzuzuUni['lng']);
            
            Log::info('Mzuzu University special check', [
                'distance' => $distance,
                'allowed_radius' => $mzuzuUni['radius'],
                'within_range' => $distance <= $mzuzuUni['radius']
            ]);
            
            // Allow up to 10km radius for Mzuzu University if explicitly selected
            if ($distance <= 10) {
                return true;
            }
        }

        foreach ($allowedLocations as $location) {
            $distance = $this->calculateDistance(
                $latitude, 
                $longitude, 
                $location['lat'], 
                $location['lng']
            );

            Log::info('Distance check', [
                'location_name' => $location['name'],
                'distance' => $distance,
                'allowed_radius' => $location['radius'],
                'within_range' => $distance <= $location['radius']
            ]);

            if ($distance <= $location['radius']) {
                return true;
            }
        }

        return false;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;

        return $distance;
    }
}