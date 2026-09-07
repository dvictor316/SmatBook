<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_units')) {
            Schema::create('product_units', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('unit_id')->nullable()->index();
                $table->string('unit_name', 120);
                $table->string('unit_symbol', 40)->nullable();
                $table->decimal('conversion_factor', 18, 6)->default(1);
                $table->boolean('is_base_unit')->default(false)->index();
                $table->boolean('is_purchase_unit')->default(false)->index();
                $table->boolean('is_default_sales_unit')->default(false)->index();
                $table->decimal('purchase_price', 18, 2)->nullable();
                $table->decimal('selling_price', 18, 2)->nullable();
                $table->decimal('wholesale_price', 18, 2)->nullable();
                $table->string('barcode', 191)->nullable()->index();
                $table->string('status', 30)->default('active')->index();
                $table->timestamps();

                $table->unique(['product_id', 'unit_name']);
            });
        }

        if (Schema::hasTable('purchase_items')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_items', 'unit_type')) {
                    $table->string('unit_type', 80)->nullable()->after('unit_price');
                }
                if (!Schema::hasColumn('purchase_items', 'conversion_factor')) {
                    $table->decimal('conversion_factor', 18, 6)->default(1)->after('unit_type');
                }
                if (!Schema::hasColumn('purchase_items', 'stock_units')) {
                    $table->decimal('stock_units', 18, 6)->nullable()->after('conversion_factor');
                }
            });
        }

        if (Schema::hasTable('product_barcodes')) {
            Schema::table('product_barcodes', function (Blueprint $table) {
                if (!Schema::hasColumn('product_barcodes', 'product_unit_id')) {
                    $table->unsignedBigInteger('product_unit_id')->nullable()->after('product_id')->index();
                }
                if (!Schema::hasColumn('product_barcodes', 'unit_name')) {
                    $table->string('unit_name', 120)->nullable()->after('barcode');
                }
            });
        }

        if (Schema::hasTable('products') && Schema::hasTable('product_units')) {
            DB::table('products')
                ->orderBy('id')
                ->chunkById(300, function ($products) {
                    foreach ($products as $product) {
                        $baseName = trim((string) ($product->base_unit_name ?? '')) ?: 'pcs';
                        $baseUnitId = $product->base_unit_id ?? $product->unit_id ?? null;
                        $now = now();

                        DB::table('product_units')->updateOrInsert(
                            ['product_id' => $product->id, 'unit_name' => $baseName],
                            [
                                'company_id' => $product->company_id ?? null,
                                'user_id' => $product->user_id ?? null,
                                'unit_id' => $baseUnitId,
                                'unit_symbol' => $baseName,
                                'conversion_factor' => 1,
                                'is_base_unit' => true,
                                'is_purchase_unit' => empty($product->purchase_unit_id),
                                'is_default_sales_unit' => true,
                                'purchase_price' => $product->purchase_price ?? null,
                                'selling_price' => $product->price ?? null,
                                'wholesale_price' => $product->wholesale_price ?? null,
                                'barcode' => $product->barcode ?? null,
                                'status' => 'active',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]
                        );

                        $purchaseUnitId = $product->purchase_unit_id ?? null;
                        $conversionRate = (float) ($product->conversion_rate ?? 0);
                        if ($purchaseUnitId && $conversionRate > 0 && Schema::hasTable('units')) {
                            $unit = DB::table('units')->where('id', $purchaseUnitId)->first();
                            $unitName = trim((string) ($unit->symbol ?? $unit->name ?? ''));
                            if ($unitName !== '' && strtolower($unitName) !== strtolower($baseName)) {
                                DB::table('product_units')->updateOrInsert(
                                    ['product_id' => $product->id, 'unit_name' => $unitName],
                                    [
                                        'company_id' => $product->company_id ?? null,
                                        'user_id' => $product->user_id ?? null,
                                        'unit_id' => $purchaseUnitId,
                                        'unit_symbol' => $unit->symbol ?? $unitName,
                                        'conversion_factor' => $conversionRate,
                                        'is_base_unit' => false,
                                        'is_purchase_unit' => true,
                                        'is_default_sales_unit' => false,
                                        'purchase_price' => $product->purchase_price ?? null,
                                        'selling_price' => null,
                                        'wholesale_price' => null,
                                        'barcode' => null,
                                        'status' => 'active',
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                    ]
                                );
                            }
                        }

                        $unitsPerRoll = max((float) ($product->units_per_roll ?? 0), 0);
                        $cartonFactor = 0;
                        if ((float) ($product->units_per_carton ?? 0) > 0) {
                            $cartonFactor = $unitsPerRoll > 0
                                ? (float) $product->units_per_carton * $unitsPerRoll
                                : (float) $product->units_per_carton;
                        }

                        foreach (['roll' => $unitsPerRoll, 'carton' => $cartonFactor] as $unitName => $factor) {
                            if ($factor <= 1) {
                                continue;
                            }

                            DB::table('product_units')->updateOrInsert(
                                ['product_id' => $product->id, 'unit_name' => $unitName],
                                [
                                    'company_id' => $product->company_id ?? null,
                                    'user_id' => $product->user_id ?? null,
                                    'unit_id' => null,
                                    'unit_symbol' => $unitName === 'carton' ? 'ctn' : $unitName,
                                    'conversion_factor' => $factor,
                                    'is_base_unit' => false,
                                    'is_purchase_unit' => ($product->unit_type ?? '') === $unitName && empty($product->purchase_unit_id),
                                    'is_default_sales_unit' => ($product->unit_type ?? '') === $unitName,
                                    'purchase_price' => !empty($product->purchase_price) ? round((float) $product->purchase_price * $factor, 2) : null,
                                    'selling_price' => !empty($product->price) ? round((float) $product->price * $factor, 2) : null,
                                    'wholesale_price' => !empty($product->wholesale_price) ? round((float) $product->wholesale_price * $factor, 2) : null,
                                    'barcode' => null,
                                    'status' => 'active',
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ]
                            );
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_barcodes')) {
            Schema::table('product_barcodes', function (Blueprint $table) {
                if (Schema::hasColumn('product_barcodes', 'unit_name')) {
                    $table->dropColumn('unit_name');
                }
                if (Schema::hasColumn('product_barcodes', 'product_unit_id')) {
                    $table->dropColumn('product_unit_id');
                }
            });
        }

        if (Schema::hasTable('purchase_items')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                foreach (['stock_units', 'conversion_factor', 'unit_type'] as $column) {
                    if (Schema::hasColumn('purchase_items', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('product_units');
    }
};
