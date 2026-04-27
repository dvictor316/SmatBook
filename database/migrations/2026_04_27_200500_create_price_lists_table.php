<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customer Price Lists
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('type')->default('discount')->comment('discount,fixed,markup');
            $table->decimal('adjustment_value', 10, 4)->default(0)
                ->comment('% discount or fixed amount or % markup');
            $table->string('currency', 10)->default('NGN');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('price_list_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('unit_price', 18, 4);
            $table->decimal('min_quantity', 18, 4)->default(1);
            $table->timestamps();

            $table->foreign('price_list_id')->references('id')->on('price_lists')->onDelete('cascade');
            $table->unique(['price_list_id', 'product_id', 'min_quantity']);
        });

        // Link customers to price lists
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'price_list_id')) {
                $table->unsignedBigInteger('price_list_id')->nullable()->after('credit_limit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'price_list_id')) {
                $table->dropColumn('price_list_id');
            }
        });
        Schema::dropIfExists('price_list_items');
        Schema::dropIfExists('price_lists');
    }
};
