<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    //to display products
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->has('category')) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('name', $request->category);
            });
        }

        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });
        }
        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'price_low':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_high':
                    $query->orderBy('price', 'desc');
                    break;
                case 'name':
                    $query->orderBy('name', 'asc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $products = $query->get();

        // Transform image URLs for frontend
        $products->transform(function ($product) {
            if ($product->image_url) {
                $product->image_url = url('storage/' . $product->image_url);
            }
            return $product;
        });

        return response()->json([
            'success' => true,
            'products' => $products,
            'count' => $products->count()
        ]);
    }

    public function featured()
    {
        $products = Product::with('category')
            ->where('featured', true)
            ->orWhere('stock_quantity', '>', 0)
            ->limit(6)
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform image URLs for frontend
        $products->transform(function ($product) {
            if ($product->image_url) {
                $product->image_url = url('storage/' . $product->image_url);
            }
            return $product;
        });

        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }

    public function show($id)
    {
        $product = Product::with('category')->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // Transform image URL for frontend
        if ($product->image_url) {
            $product->image_url = url('storage/' . $product->image_url);
        }

        return response()->json([
            'success' => true,
            'product' => $product
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        try {
            // Generate slug from product name
            $slug = Str::slug($request->name);

            // Ensure unique slug
            $originalSlug = $slug;
            $counter = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Handle image upload
            $imageUrl = $request->image_url;
            if (isset($request->image) && $request->image instanceof \Illuminate\Http\UploadedFile) {
                $imagePath = $request->image->store('products', 'public');
                $imageUrl = $imagePath;
            }

            $product = Product::create([
                'category_id' => $request->category_id,
                'name' => $request->name,
                'slug' => $slug,
                'description' => $request->description,
                'price' => $request->price,
                'stock_quantity' => $request->stock_quantity ?? 0,
                'image_url' => $imageUrl,
                'is_active' => $request->is_active ?? true,
                'featured' => $request->featured ?? false,
            ]);

            $product->load('category');

            Log::info('Product Created');
            Log::info($product);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }    
    }


    /**
     * Update an existing product.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        // Convert string boolean values from FormData to actual booleans
        $request->merge([
            'is_active' => filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN),
            'is_featured' => filter_var($request->input('is_featured'), FILTER_VALIDATE_BOOLEAN),
        ]);

        $validated = $request->validate([
            'category_id'    => 'sometimes|required|exists:categories,id',
            'name'           => 'sometimes|required|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'sometimes|required|numeric|min:0',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
            'is_active'      => 'boolean',
            'is_featured'    => 'boolean',
        ]);

        if (isset($request->image) && $request->image instanceof \Illuminate\Http\UploadedFile) {
            if ($product->image_url) {
                Storage::disk('public')->delete($product->image_url);
            }
            $validated['image_url'] = $request->image->store('products', 'public');
        }

        $product->update($validated);

        // Reload the product with category relationship
        $product->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data'    => $product,
        ]);
    }

/**
     * Delete a product.
     */
    public function destroy($id)
    {
        $product = Product::find($id);
        if ($product->image_url) {
            Storage::disk('public')->delete($product->image_url);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }

    public function getByCategory($categoryName)
    {
        $products = Product::with('category')
            ->whereHas('category', function($q) use ($categoryName) {
                $q->where('name', 'LIKE', "%{$categoryName}%");
            })
            ->where('stock_quantity', '>', 0)
            ->get();

        // Transform image URLs for frontend
        $products->transform(function ($product) {
            if ($product->image_url) {
                $product->image_url = url('storage/' . $product->image_url);
            }
            return $product;
        });

        return response()->json([
            'success' => true,
            'category' => $categoryName,
            'products' => $products,
            'count' => $products->count()
        ]);
    }

    public function checkStock($id, Request $request)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $requestedQuantity = $request->input('quantity', 1);

        $available = $product->stock_quantity >= $requestedQuantity;

        return response()->json([
            'success' => true,
            'available' => $available,
            'stock_quantity' => $product->stock_quantity,
            'requested' => $requestedQuantity
        ]);
    }


    public function updateStock($id, Request $request)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer',
            'operation' => 'required|in:add,subtract'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->operation === 'add') {
            $product->stock_quantity += $request->quantity;
        } else {
            if ($product->stock_quantity < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock'
                ], 400);
            }
            $product->stock_quantity -= $request->quantity;
        }

        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Stock updated successfully',
            'new_stock' => $product->stock_quantity
        ]);
    }

    /**
     * Get all categories for product management
     */
    public function categories()
    {
        $categories = \App\Models\Category::select('id', 'name', 'slug')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}