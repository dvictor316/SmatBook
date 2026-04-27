<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Batch / Lot tracking
        Schema::create('product_lots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('product_id');
            $table->string('lot_number', 100);
            $table->string('batch_number', 100)->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity_received', 18, 4)->default(0);
            $table->decimal('quantity_available', 18, 4)->default(0);
            $table->decimal('quantity_used', 18, 4)->default(0);
            $table->string('status')->default('active')->comment('active,consumed,expired,recalled');
            $table->unsignedBigInteger('grn_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'product_id']);
            $table->index(['company_id', 'lot_number']);
        });

        // Serial number tracking
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('product_id');
            $table->string('serial_number', 200)->unique();
            $table->string('status')->default('in_stock')
                ->comment('in_stock,sold,returned,scrapped,transferred');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('grn_id')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->date('sold_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'product_id', 'status']);
        });

        // Barcode lookup — products can have multiple barcodes
        Schema::create('product_barcodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('product_id');
            $table->string('barcode', 200)->index();
            $table->string('barcode_type')->default('EAN13')
                ->comment('EAN13,EAN8,UPC,QR,CODE128,ITF,custom');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'barcode']);
        });

        // Add lot_tracking and serial_tracking flags to products
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'track_lots')) {
                $table->boolean('track_lots')->default(false)->after('stock_quantity');
            }
            if (!Schema::hasColumn('products', 'track_serials')) {
                $table->boolean('track_serials')->default(false)->after('track_lots');
            }
            if (!Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode', 200)->nullable()->after('track_serials');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['track_lots', 'track_serials', 'barcode'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::dropIfExists('product_barcodes');
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('product_lots');
    }
};
