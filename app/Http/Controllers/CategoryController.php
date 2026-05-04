<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;

class CategoryController extends Controller
{
private function activeTenantScope(): array
{
    return [
        'company_id' => (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0),
        'user_id' => (int) (Auth::id() ?? 0),
        'branch_id' => trim((string) session('active_branch_id', '')),
        'branch_name' => trim((string) session('active_branch_name', '')),
    ];
}

private function expectsJsonResponse(Request $request): bool
{
    return $request->expectsJson()
        || $request->wantsJson()
        || $request->ajax()
        || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest'
        || str_contains(strtolower((string) $request->header('Accept')), 'application/json');
}

private function scopedCategoryQuery()
{
    $scope = $this->activeTenantScope();
    $query = $this->applyTenantScope(Category::withoutGlobalScopes()->newQuery());
    $branchId = $scope['branch_id'];
    $branchName = $scope['branch_name'];
    $hasBranchId = Schema::hasColumn('categories', 'branch_id');
    $hasBranchName = Schema::hasColumn('categories', 'branch_name');

    if (($branchId !== '' || $branchName !== '') && ($hasBranchId || $hasBranchName)) {
        $query->where(function ($scoped) use ($branchId, $branchName, $hasBranchId, $hasBranchName) {
            $matched = false;

            if ($hasBranchId && $branchId !== '') {
                $scoped->where('categories.branch_id', $branchId);
                $matched = true;
            }

            if ($hasBranchName && $branchName !== '') {
                $method = $matched ? 'orWhere' : 'where';
                $scoped->{$method}('categories.branch_name', $branchName);
                $matched = true;
            }
        });
    }

    return $query;
}

private function applyTenantScope($query)
{
    $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
    $userId = (int) (Auth::id() ?? 0);

    if ($companyId > 0 && Schema::hasColumn('categories', 'company_id')) {
        $query->where('categories.company_id', $companyId);
    } elseif ($userId > 0 && Schema::hasColumn('categories', 'user_id')) {
        $query->where('categories.user_id', $userId);
    }

    return $query;
}

public function index(Request $request)
{
    if (!Schema::hasTable('categories')) {
        $categories = collect();
        if ($this->expectsJsonResponse($request)) {
            return response()->json(['ok' => true, 'data' => []]);
        }
        return view('Inventory.Products.categories', compact('categories'));
    }

    $query = $this->scopedCategoryQuery();

    if (Schema::hasTable('products')) {
        $query->withCount('products');
    }

    // Determine the type scope:
    //  - JSON/AJAX callers may pass ?type=product|expense|... to get only that type
    //  - The HTML management page (/inventory/products/category) is always product-scoped
    //  - Legacy rows (type IS NULL) are included for both 'product' and the HTML page
    if (Schema::hasColumn('categories', 'type')) {
        $typeFilter = $request->get('type');
        if ($typeFilter !== null && $typeFilter !== '') {
            // Explicit type requested (e.g. ?type=product from product views)
            $query->where(function ($q) use ($typeFilter) {
                $q->where('categories.type', $typeFilter)
                  ->orWhereNull('categories.type');
            });
        } else {
            // HTML management page — always show product categories (+ legacy nulls)
            $query->where(function ($q) {
                $q->where('categories.type', 'product')
                  ->orWhereNull('categories.type');
            });
        }
    }

    $orderColumn = Schema::hasColumn('categories', 'created_at') ? 'created_at' : 'id';
    $categories = $query->orderByDesc($orderColumn)->get();

    if ($this->expectsJsonResponse($request)) {
        return response()->json([
            'ok' => true,
            'data' => $categories->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])->values(),
        ]);
    }

    return view('Inventory.Products.categories', compact('categories'));
}
    /**
     * Store a new category.
     * Matches Route: categories.store
     */
public function store(Request $request)
{
    if (!Schema::hasTable('categories')) {
        if ($this->expectsJsonResponse($request)) {
            return response()->json([
                'ok' => false,
                'message' => 'Categories table is not available yet.',
                'data' => [],
            ], 500);
        }
        return redirect()->back()->with('error', 'Categories table is not available yet.');
    }

    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
        'status' => 'nullable',
    ]);

    if ($validator->fails()) {
        if ($this->expectsJsonResponse($request)) {
            return response()->json([
                'ok' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        return redirect()->back()->withErrors($validator)->withInput();
    }

    $normalizedName = mb_strtolower(trim((string) $request->name));

    $existingCategory = $this->scopedCategoryQuery()
        ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])
        ->first();

    if ($existingCategory) {
        if ($this->expectsJsonResponse($request)) {
            return response()->json([
                'ok' => true,
                'message' => 'Category already exists and has been selected.',
                'data' => [
                    'id' => $existingCategory->id,
                    'name' => $existingCategory->name,
                ],
            ]);
        }

        return redirect()->back()->withErrors(['name' => 'A category with this name already exists.'])->withInput();
    }

    // 2. Handle Image Upload
    $imageName = null;
    if ($request->hasFile('image')) {
        $imageName = time() . '_' . uniqid() . '.' . $request->image->extension();
        Storage::disk('public')->putFileAs('categories', $request->file('image'), $imageName);
    }

    // 3. Create Record
    $payload = [
        'name' => trim((string) $request->name),
    ];

    if (Schema::hasColumn('categories', 'description')) {
        $payload['description'] = $request->description;
    }

    if (Schema::hasColumn('categories', 'image')) {
        $payload['image'] = $imageName ? 'categories/' . $imageName : null;
    }

    if (Schema::hasColumn('categories', 'status')) {
        $payload['status'] = $request->has('status') ? (int) $request->status : 1;
    }

    if (Schema::hasColumn('categories', 'company_id')) {
        $payload['company_id'] = Auth::user()?->company_id ?? session('current_tenant_id');
    }

    if (Schema::hasColumn('categories', 'user_id')) {
        $payload['user_id'] = Auth::id();
    }

    if (Schema::hasColumn('categories', 'branch_id')) {
        $payload['branch_id'] = session('active_branch_id');
    }

    if (Schema::hasColumn('categories', 'branch_name')) {
        $payload['branch_name'] = session('active_branch_name');
    }

    // Categories created from the product-category management page are product-type
    if (Schema::hasColumn('categories', 'type')) {
        $payload['type'] = 'product';
    }

    try {
        $category = \App\Models\Category::create($payload);
    } catch (QueryException $exception) {
        if ((int) $exception->getCode() === 23000 || (int) ($exception->errorInfo[1] ?? 0) === 1062) {
            $duplicateCategory = $this->scopedCategoryQuery()
                ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])
                ->first();

            if ($duplicateCategory && $this->expectsJsonResponse($request)) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Category already exists and has been selected.',
                    'data' => [
                        'id' => $duplicateCategory->id,
                        'name' => $duplicateCategory->name,
                    ],
                ]);
            }

            return redirect()->back()->withErrors([
                'name' => 'A category with this name already exists.',
            ])->withInput();
        }

        throw $exception;
    }

    if ($this->expectsJsonResponse($request)) {
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

    public function clearProducts(Category $category)
    {
        $deleted = 0;
        $failed = 0;

        $category->products()->chunk(100, function ($products) use (&$deleted, &$failed) {
            foreach ($products as $product) {
                try {
                    if (Schema::hasTable('inventory_history')) {
                        \DB::table('inventory_history')->where('product_id', $product->id)->delete();
                    }
                    $product->delete();
                    $deleted++;
                } catch (\Throwable $e) {
                    $failed++;
                }
            }
        });

        return redirect()->back()->with(
            'success',
            "Deleted {$deleted} products from this category. Failed: {$failed}."
        );
    }
}
