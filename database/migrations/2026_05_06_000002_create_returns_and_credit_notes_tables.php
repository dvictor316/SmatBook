<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── credit_notes ──────────────────────────────────────────────────────
        if (! Schema::hasTable('credit_notes')) {
            Schema::create('credit_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sale_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('credit_note_no')->unique();
                $table->date('credit_date');
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->string('status')->default('approved');
                $table->text('reason')->nullable();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('branch_id')->nullable();
                $table->string('branch_name')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // ── credit_note_items ─────────────────────────────────────────────────
        if (! Schema::hasTable('credit_note_items')) {
            Schema::create('credit_note_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('credit_note_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->decimal('qty', 12, 2)->default(0);
                $table->decimal('unit_price', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }

        // ── purchase_return_items ─────────────────────────────────────────────
        if (! Schema::hasTable('purchase_return_items')) {
            Schema::create('purchase_return_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('purchase_return_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->decimal('qty', 12, 2)->default(0);
                $table->decimal('unit_price', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }

        // ── purchase_returns: add missing columns if not present ──────────────
        Schema::table('purchase_returns', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_returns', 'return_date')) {
                $table->date('return_date')->nullable()->after('reason');
            }
            if (! Schema::hasColumn('purchase_returns', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->default(0)->after('return_date');
            }
            if (! Schema::hasColumn('purchase_returns', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('purchase_returns', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('company_id');
            }
            if (! Schema::hasColumn('purchase_returns', 'branch_id')) {
                $table->string('branch_id')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('purchase_returns', 'branch_name')) {
                $table->string('branch_name')->nullable()->after('branch_id');
            }
            if (! Schema::hasColumn('purchase_returns', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_note_items');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('purchase_return_items');
    }
};
