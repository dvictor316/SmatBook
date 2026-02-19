<?php
// app/Http/Controllers/ProductSaleController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductSale;

class ProductSaleController extends Controller
{
    // Display a list of sales for a product
    public function index()
    {
        // Fetch total number of companies
        $totalCompanies = Company::count();

        // Count active and inactive companies
        $activeCompanies = Company::where('status', 'active')->count();
        $inactiveCompanies = Company::where('status', 'inactive')->count();

        // Count companies with address
        $companiesWithAddress = Company::whereNotNull('address')->count();

        // Fetch sales data for the chart
        $monthlySales = [
            ['month_name' => 'Jan', 'total_sales' => 1200],
            ['month_name' => 'Feb', 'total_sales' => 1700],
            ['month_name' => 'Mar', 'total_sales' => 1400],
            ['month_name' => 'Apr', 'total_sales' => 1600],
        ];

        // Sales by status for donut chart
        $salesByStatus = [
            ['status' => 'active', 'total_amount' => 5000],
            ['status' => 'inactive', 'total_amount' => 2000],
        ];

        // Fetch product sales data
        $productSales = ProductSale::select('product_name', \DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_name')
            ->get();

        // Prepare labels and data arrays for product sales bar chart
        $productSalesLabels = $productSales->pluck('product_name')->toArray();
        $productSalesData = $productSales->pluck('total_sold')->toArray();

        // Example invoiced vs received data for the area chart
        $invoicedTotal = 10000; // Set actual values from your database
        $receivedTotal = 8000;  // Set actual values from your database

        return view('SuperAdmin.dashboard', compact(
            'totalCompanies',
            'activeCompanies',
            'inactiveCompanies',
            'companiesWithAddress',
            'monthlySales',
            'salesByStatus',
            'productSalesLabels',
            'productSalesData',
            'invoicedTotal',
            'receivedTotal'
        ));
    }
}

