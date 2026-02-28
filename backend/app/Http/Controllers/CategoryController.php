<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->get();

        return response()->json([
            'success' => true,
            'categories' => $categories
        ]);
    }

    /**
     * Create a new category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $category = Category::create([
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']),
            
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data'    => $category,
        ], 201);
    }

    public function show($slug)
    {
        $category = Category::where('slug', $slug)
            ->with(['products' => function($query) {
                $query->where('stock', '>', 0);
            }])
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'category' => $category
        ]);
    }

    /**
     * Update an existing category.
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255' . $category->id,
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data'    => $category->toArray(),
        ]);
    }

    /**
     * Delete a category.
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with existing products. Remove products first.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }
}