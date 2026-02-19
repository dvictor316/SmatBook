<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // How many individual units/pieces are in ONE Carton for this product?
            $table->integer('units_per_carton')->default(1)->after('price'); 
            
            // How many individual units/pieces are in ONE Roll for this product?
            $table->integer('units_per_roll')->default(1)->after('units_per_carton');
            
            // Optional: To keep track of the name of the base unit (e.g., 'pcs', 'sachets')
            $table->string('base_unit_name')->default('pcs')->after('units_per_roll');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['units_per_carton', 'units_per_roll', 'base_unit_name']);
        });
    }
};