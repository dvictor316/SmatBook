<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\TenantScoped;

class Income extends Model
{
    use HasFactory, TenantScoped;

    public function index()
    {
        // Now you can call the model without errors
        $incomes = Income::all(); 
        return view('income-report', compact('incomes'));
    }
}
