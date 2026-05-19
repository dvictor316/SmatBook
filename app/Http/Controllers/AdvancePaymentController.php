<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdvancePaymentController extends Controller
{
    public function customers(Request $request)
    {
        $query = $this->scopedCustomerQuery();

        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'balance')) {
            $query->withSum(['sales as sales_balance_sum' => function ($salesQuery) {
                if (Schema::hasColumn('sales', 'order_status')) {
                    $salesQuery->where(function ($sub) {
                        $sub->whereNull('order_status')
                            ->orWhere('order_status', '!=', 'draft');
                    });
                }
            }], 'balance');
        }

        $customers = $query->orderByRaw($this->customerNameOrderExpression())
            ->paginate(20)
            ->withQueryString();

        return view('Finance.advance-payments', [
            'mode' => 'customers',
            'title' => 'Customer Advances',
            'subtitle' => 'Receive deposits or overpayments from customers. Excess payments become wallet/customer advance credit.',
            'records' => $customers,
        ]);
    }

    public function suppliers(Request $request)
    {
        $suppliers = $this->scopedSupplierQuery()
            ->orderByRaw($this->supplierNameOrderExpression())
            ->paginate(20)
            ->withQueryString();

        return view('Finance.advance-payments', [
            'mode' => 'suppliers',
            'title' => 'Supplier Advances',
            'subtitle' => 'Pay suppliers before goods or bills are fully settled. Excess payments are treated as supplier advances/prepayments.',
            'records' => $suppliers,
        ]);
    }

    private function scopedCustomerQuery()
    {
        $query = Customer::query();
        $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (auth()->id() ?? 0);

        if ($companyId > 0 && Schema::hasColumn('customers', 'company_id')) {
            $query->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('customers', 'user_id')) {
            $query->where('user_id', $userId);
        }

        return $query;
    }

    private function scopedSupplierQuery()
    {
        $query = Supplier::query();
        $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (auth()->id() ?? 0);

        if ($companyId > 0 && Schema::hasColumn('suppliers', 'company_id')) {
            $query->where('company_id', $companyId);
        } elseif ($userId > 0 && Schema::hasColumn('suppliers', 'user_id')) {
            $query->where('user_id', $userId);
        }

        return $query;
    }

    private function customerNameOrderExpression(): string
    {
        $columns = [];
        if (Schema::hasColumn('customers', 'customer_name')) {
            $columns[] = 'customer_name';
        }
        if (Schema::hasColumn('customers', 'name')) {
            $columns[] = 'name';
        }

        return $columns === [] ? 'id desc' : 'COALESCE(' . implode(', ', $columns) . ') asc';
    }

    private function supplierNameOrderExpression(): string
    {
        $columns = [];
        foreach (['supplier_name', 'company_name', 'name'] as $column) {
            if (Schema::hasColumn('suppliers', $column)) {
                $columns[] = $column;
            }
        }

        return $columns === [] ? 'id desc' : 'COALESCE(' . implode(', ', $columns) . ') asc';
    }
}
