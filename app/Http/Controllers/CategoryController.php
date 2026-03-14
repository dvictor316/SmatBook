<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CategoryController extends Controller
{
public function index()
{
    if (!Schema::hasTable('categories')) {
        $categories = collect();
        return view('Inventory.Products.categories', compact('categories'));
    }

    $query = Category::query();

    if (Schema::hasTable('products')) {
        $query->withCount('products');
    }

    $orderColumn = Schema::hasColumn('categories', 'created_at') ? 'created_at' : 'id';
    $categories = $query->orderByDesc($orderColumn)->get();

    return view('Inventory.Products.categories', compact('categories'));
}
    /**
     * Store a new category.
     * Matches Route: categories.store
     */
public function store(Request $request)
{
    if (!Schema::hasTable('categories')) {
        return redirect()->back()->with('error', 'Categories table is not available yet.');
    }

    // 1. Validation
    $request->validate([
        'name' => 'required|string|max:255|unique:categories,name',
        'description' => 'nullable|string',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
        'status' => 'nullable', // HTML select/checkbox can be tricky with 'boolean' validation
    ]);

    // 2. Handle Image Upload
    $imageName = null;
    if ($request->hasFile('image')) {
        // Create unique name to prevent overwriting
        $imageName = time() . '_' . uniqid() . '.' . $request->image->extension();
        
        // Ensure the directory exists
        $path = public_path('assets/img/category');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $request->image->move($path, $imageName);
    }

    // 3. Create Record
    $payload = [
        'name' => $request->name,
    ];

    if (Schema::hasColumn('categories', 'description')) {
        $payload['description'] = $request->description;
    }

    if (Schema::hasColumn('categories', 'image')) {
        $payload['image'] = $imageName;
    }

    if (Schema::hasColumn('categories', 'status')) {
        $payload['status'] = $request->has('status') ? (int) $request->status : 1;
    }

    $category = \App\Models\Category::create($payload);

    if ($request->expectsJson()) {
        return response()->json([
            'ok' => true,
            'message' => 'Category created successfully!',
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
        ]);
    }

    return redirect()->back()->with('success', 'Category created successfully!');
}

    /**
     * Update an existing category.
     * Matches Route: categories.update
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
        ]);

        $payload = ['name' => $request->name];

        if (Schema::hasColumn('categories', 'description')) {
            $payload['description'] = $request->description;
        }

        if (Schema::hasColumn('categories', 'status')) {
            $payload['status'] = $request->input('status', $category->status ?? 1);
        }

        $category->update($payload);

        return redirect()->back()->with('success', 'Category updated successfully!');
    }

    /**
     * Remove the category.
     * Matches Route: categories.destroy
     */
    public function destroy(Category $category)
    {
        // Prevent deletion if category is not empty
        if ($category->products()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete! This category contains products.');
        }

        $category->delete();
        return redirect()->back()->with('success', 'Category deleted successfully!');
    }
}
