<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bill of Materials
        Schema::create('bill_of_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('bom_number', 100)->nullable();
            $table->unsignedBigInteger('product_id')->comment('finished/parent product');
            $table->decimal('output_quantity', 18, 4)->default(1)->comment('units produced per BOM');
            $table->string('unit')->default('pcs');
            $table->string('bom_type')->default('standard')->comment('standard,assembly,phantom');
            $table->string('status')->default('active')->comment('active,inactive,draft');
            $table->decimal('standard_cost', 18, 4)->default(0);
            $table->text('instructions')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'product_id']);
        });

        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bom_id');
            $table->unsignedBigInteger('component_product_id');
            $table->string('component_name');
            $table->decimal('quantity', 18, 4);
            $table->string('unit')->nullable();
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->decimal('scrap_percentage', 8, 4)->default(0);
            $table->string('item_type')->default('component')->comment('component,byproduct,phantom');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('bom_id')->references('id')->on('bill_of_materials')->onDelete('cascade');
        });

        // Manufacturing / Production Orders
        Schema::create('manufacturing_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('mo_number', 100)->unique();
            $table->unsignedBigInteger('bom_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->decimal('planned_quantity', 18, 4);
            $table->decimal('produced_quantity', 18, 4)->default(0);
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->string('status')->default('draft')
                ->comment('draft,confirmed,in_progress,completed,cancelled');
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->decimal('total_material_cost', 18, 2)->default(0);
            $table->decimal('total_labour_cost', 18, 2)->default(0);
            $table->decimal('total_overhead_cost', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
        });

        Schema::create('manufacturing_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manufacturing_order_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name');
            $table->decimal('required_quantity', 18, 4);
            $table->decimal('consumed_quantity', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->string('unit')->nullable();
            $table->string('lot_number')->nullable();
            $table->timestamps();

            $table->foreign('manufacturing_order_id')
                ->references('id')->on('manufacturing_orders')->onDelete('cascade');
        });

        // Stock Valuation Settings
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'costing_method')) {
                $table->string('costing_method', 20)->default('weighted_avg')
                    ->comment('fifo,lifo,weighted_avg,specific')->nullable()->after('barcode');
            }
            if (!Schema::hasColumn('products', 'standard_cost')) {
                $table->decimal('standard_cost', 18, 4)->default(0)->nullable()->after('costing_method');
            }
            if (!Schema::hasColumn('products', 'landed_cost')) {
                $table->decimal('landed_cost', 18, 4)->default(0)->nullable()->after('standard_cost');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['costing_method', 'standard_cost', 'landed_cost'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::dropIfExists('manufacturing_order_items');
        Schema::dropIfExists('manufacturing_orders');
        Schema::dropIfExists('bom_items');
        Schema::dropIfExists('bill_of_materials');
    }
};
