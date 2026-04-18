<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures every table that participates in report queries has a company_id column,
 * then backfills historical records by walking up the foreign-key chain.
 *
 * Tables covered:
 *   purchase_returns       ← purchases.company_id (via purchase_id)
 *   purchase_return_items  ← purchase_returns.company_id (via purchase_return_id)
 *   credit_notes           ← invoices.company_id OR sales.company_id (via invoice_id / sale_id)
 *   credit_note_items      ← credit_notes.company_id (via credit_note_id)
 *   sale_items             ← sales.company_id (via sale_id)
 *   purchase_items         ← purchases.company_id (via purchase_id)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. purchase_returns ─────────────────────────────────────────────
        if (Schema::hasTable('purchase_returns')) {
            Schema::table('purchase_returns', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_returns', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('purchase_returns', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('company_id')->index();
                }
            });

            // Backfill: join to purchases to get company_id
            if (Schema::hasColumn('purchase_returns', 'company_id')
                && Schema::hasTable('purchases')
                && Schema::hasColumn('purchases', 'company_id')) {
                DB::statement(
                    'UPDATE purchase_returns pr
                     JOIN purchases p ON p.id = pr.purchase_id
                     SET pr.company_id = p.company_id
                     WHERE pr.company_id IS NULL AND p.company_id IS NOT NULL'
                );
            }

            // Backfill user_id from purchases if available
            if (Schema::hasColumn('purchase_returns', 'user_id')
                && Schema::hasTable('purchases')
                && Schema::hasColumn('purchases', 'user_id')) {
                DB::statement(
                    'UPDATE purchase_returns pr
                     JOIN purchases p ON p.id = pr.purchase_id
                     SET pr.user_id = p.user_id
                     WHERE pr.user_id IS NULL AND p.user_id IS NOT NULL'
                );
            }
        }

        // ── 2. purchase_return_items ─────────────────────────────────────────
        if (Schema::hasTable('purchase_return_items')) {
            Schema::table('purchase_return_items', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_return_items', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('purchase_return_items', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('company_id')->index();
                }
            });

            // Backfill via purchase_returns
            if (Schema::hasColumn('purchase_return_items', 'company_id')
                && Schema::hasTable('purchase_returns')
                && Schema::hasColumn('purchase_returns', 'company_id')) {
                DB::statement(
                    'UPDATE purchase_return_items pri
                     JOIN purchase_returns pr ON pr.id = pri.purchase_return_id
                     SET pri.company_id = pr.company_id
                     WHERE pri.company_id IS NULL AND pr.company_id IS NOT NULL'
                );
            }

            if (Schema::hasColumn('purchase_return_items', 'user_id')
                && Schema::hasTable('purchase_returns')
                && Schema::hasColumn('purchase_returns', 'user_id')) {
                DB::statement(
                    'UPDATE purchase_return_items pri
                     JOIN purchase_returns pr ON pr.id = pri.purchase_return_id
                     SET pri.user_id = pr.user_id
                     WHERE pri.user_id IS NULL AND pr.user_id IS NOT NULL'
                );
            }
        }

        // ── 3. credit_notes ──────────────────────────────────────────────────
        if (Schema::hasTable('credit_notes')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                if (!Schema::hasColumn('credit_notes', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('credit_notes', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('company_id')->index();
                }
            });

            // Backfill via invoices (preferred)
            if (Schema::hasColumn('credit_notes', 'company_id')
                && Schema::hasTable('invoices')
                && Schema::hasColumn('invoices', 'company_id')
                && Schema::hasColumn('credit_notes', 'invoice_id')) {
                DB::statement(
                    'UPDATE credit_notes cn
                     JOIN invoices i ON i.id = cn.invoice_id
                     SET cn.company_id = i.company_id
                     WHERE cn.company_id IS NULL AND i.company_id IS NOT NULL'
                );
            }

            // Fallback: backfill via sales table if invoices not available
            if (Schema::hasColumn('credit_notes', 'company_id')
                && Schema::hasTable('sales')
                && Schema::hasColumn('sales', 'company_id')
                && Schema::hasColumn('credit_notes', 'invoice_id')) {
                DB::statement(
                    'UPDATE credit_notes cn
                     JOIN sales s ON s.id = cn.invoice_id
                     SET cn.company_id = s.company_id
                     WHERE cn.company_id IS NULL AND s.company_id IS NOT NULL'
                );
            }
        }

        // ── 4. credit_note_items ──────────────────────────────────────────────
        if (Schema::hasTable('credit_note_items')) {
            Schema::table('credit_note_items', function (Blueprint $table) {
                if (!Schema::hasColumn('credit_note_items', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('credit_note_items', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('company_id')->index();
                }
            });

            // Backfill via credit_notes
            if (Schema::hasColumn('credit_note_items', 'company_id')
                && Schema::hasTable('credit_notes')
                && Schema::hasColumn('credit_notes', 'company_id')) {
                DB::statement(
                    'UPDATE credit_note_items cni
                     JOIN credit_notes cn ON cn.id = cni.credit_note_id
                     SET cni.company_id = cn.company_id
                     WHERE cni.company_id IS NULL AND cn.company_id IS NOT NULL'
                );
            }

            if (Schema::hasColumn('credit_note_items', 'user_id')
                && Schema::hasTable('credit_notes')
                && Schema::hasColumn('credit_notes', 'user_id')) {
                DB::statement(
                    'UPDATE credit_note_items cni
                     JOIN credit_notes cn ON cn.id = cni.credit_note_id
                     SET cni.user_id = cn.user_id
                     WHERE cni.user_id IS NULL AND cn.user_id IS NOT NULL'
                );
            }
        }

        // ── 5. sale_items — backfill company_id from sales ───────────────────
        if (Schema::hasTable('sale_items')
            && Schema::hasColumn('sale_items', 'company_id')
            && Schema::hasTable('sales')
            && Schema::hasColumn('sales', 'company_id')) {

            $saleJoinCol = Schema::hasColumn('sale_items', 'sale_id') ? 'sale_id' : 'order_id';

            DB::statement(
                "UPDATE sale_items si
                 JOIN sales s ON s.id = si.{$saleJoinCol}
                 SET si.company_id = s.company_id
                 WHERE si.company_id IS NULL AND s.company_id IS NOT NULL"
            );

            // Also propagate user_id
            if (Schema::hasColumn('sale_items', 'user_id') && Schema::hasColumn('sales', 'user_id')) {
                DB::statement(
                    "UPDATE sale_items si
                     JOIN sales s ON s.id = si.{$saleJoinCol}
                     SET si.user_id = s.user_id
                     WHERE si.user_id IS NULL AND s.user_id IS NOT NULL"
                );
            }
        }

        // ── 6. purchase_items — backfill company_id from purchases ───────────
        if (Schema::hasTable('purchase_items')
            && Schema::hasColumn('purchase_items', 'company_id')
            && Schema::hasTable('purchases')
            && Schema::hasColumn('purchases', 'company_id')) {

            DB::statement(
                'UPDATE purchase_items pi
                 JOIN purchases p ON p.id = pi.purchase_id
                 SET pi.company_id = p.company_id
                 WHERE pi.company_id IS NULL AND p.company_id IS NOT NULL'
            );

            if (Schema::hasColumn('purchase_items', 'user_id') && Schema::hasColumn('purchases', 'user_id')) {
                DB::statement(
                    'UPDATE purchase_items pi
                     JOIN purchases p ON p.id = pi.purchase_id
                     SET pi.user_id = p.user_id
                     WHERE pi.user_id IS NULL AND p.user_id IS NOT NULL'
                );
            }
        }
    }

    public function down(): void
    {
        $columns = ['company_id', 'user_id'];

        $tables = [
            'purchase_returns',
            'purchase_return_items',
            'credit_notes',
            'credit_note_items',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $tbl) use ($table, $columns) {
                foreach ($columns as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        $tbl->dropColumn($col);
                    }
                }
            });
        }
    }
};
