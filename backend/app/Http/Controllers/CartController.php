<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
   
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get or create cart for user
        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['total' => 0]
        );

        // Load cart items with product and category information
        $cart->load(['items.product.category']);

        // Calculate totals
        $subtotal = 0;
        foreach ($cart->items as $item) {
            $subtotal += $item->quantity * $item->price;
        }

        return response()->json([
            'success' => true,
            'cart' => $cart,
            'summary' => [
                'items_count' => $cart->items->count(),
                'subtotal' => $subtotal,
                'total' => $subtotal 
            ]
        ]);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $product = Product::find($request->product_id);

        // Check availability
        if ($product->stock_quantity < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock. Only ' . $product->stock_quantity . ' items available.'
            ], 400);
        }

        // Get or create cart
        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['total' => 0]
        );

        // Check if item already exists in cart
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $request->quantity;
            
            if ($product->stock_quantity < $newQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot add more. Only ' . $product->stock_quantity . ' items available.'
                ], 400);
            }

            $cartItem->quantity = $newQuantity;
            $cartItem->save();
        } else {
            // Create new cart item
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'price' => $product->price
            ]);
        }
        $this->updateCartTotal($cart);

        // Reload cart with items
        $cart->load(['items.product.category']);

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart',
            'cart' => $cart,
            'cart_item' => $cartItem->load('product')
        ], 201);
    }

    public function update(Request $request, $itemId)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        
        $cartItem = CartItem::whereHas('cart', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->find($itemId);

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found'
            ], 404);
        }

        $product = $cartItem->product;

        // Check stock
        if ($product->stock_quantity < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock. Only ' . $product->stock_quantity . ' items available.'
            ], 400);
        }

        $cartItem->quantity = $request->quantity;
        $cartItem->save();
        $this->updateCartTotal($cartItem->cart);

        return response()->json([
            'success' => true,
            'message' => 'Cart updated',
            'cart_item' => $cartItem->load('product')
        ]);
    }
// removes items
    public function destroy(Request $request, $itemId)
    {
        $user = $request->user();
        
        $cartItem = CartItem::whereHas('cart', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->find($itemId);

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found'
            ], 404);
        }

        $cart = $cartItem->cart;
        $cartItem->delete();

        // Update cart total
        $this->updateCartTotal($cart);

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart'
        ]);
    }
    public function clear(Request $request)
    {
        $user = $request->user();
        
        $cart = Cart::where('user_id', $user->id)->first();

        if ($cart) {
            $cart->items()->delete();
            $cart->total = 0;
            $cart->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared'
        ]);
    }
    private function updateCartTotal(Cart $cart)
    {
        $total = $cart->items->sum(function($item) {
            return $item->quantity * $item->price;
        });

        $cart->total = $total;
        $cart->save();
    }
}