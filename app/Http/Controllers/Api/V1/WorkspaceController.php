<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        return response()->json(['data' => [
            'company_id' => $companyId,
            'products' => $this->companyQuery(Product::query(), $companyId)->count(),
            'customers' => $this->companyQuery(Customer::query(), $companyId)->count(),
            'invoices' => $this->companyQuery(Invoice::query(), $companyId)->count(),
            'generated_at' => now()->toIso8601String(),
        ]]);
    }

    public function products(Request $request): JsonResponse
    {
        abort_unless($request->user()->tokenCan('products:read'), 403, 'Token lacks products:read ability.');
        $products = $this->companyQuery(Product::query(), $this->companyId($request))
            ->select(['id', 'name', 'sku', 'barcode', 'description', 'price', 'purchase_price', 'stock', 'stock_quantity', 'base_unit_name', 'status', 'updated_at'])
            ->orderBy('id')
            ->paginate($this->pageSize($request));

        return response()->json($products);
    }

    public function customers(Request $request): JsonResponse
    {
        abort_unless($request->user()->tokenCan('customers:read'), 403, 'Token lacks customers:read ability.');
        $customers = $this->companyQuery(Customer::query(), $this->companyId($request))
            ->select(['id', 'customer_name', 'email', 'phone', 'status', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->paginate($this->pageSize($request));

        return response()->json($customers);
    }

    public function invoices(Request $request): JsonResponse
    {
        abort_unless($request->user()->tokenCan('invoices:read'), 403, 'Token lacks invoices:read ability.');
        $invoices = $this->companyQuery(Invoice::query(), $this->companyId($request))
            ->select(['id', 'customer_id', 'invoice_date', 'due_date', 'amount', 'total_amount', 'status', 'description', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->paginate($this->pageSize($request));

        return response()->json($invoices);
    }

    private function companyQuery(Builder $query, int $companyId): Builder
    {
        return $query->withoutGlobalScopes()->where($query->getModel()->getTable() . '.company_id', $companyId);
    }

    private function companyId(Request $request): int
    {
        return (int) $request->attributes->get('api_company_id');
    }

    private function pageSize(Request $request): int
    {
        return min(100, max(1, (int) $request->query('per_page', 25)));
    }
}
