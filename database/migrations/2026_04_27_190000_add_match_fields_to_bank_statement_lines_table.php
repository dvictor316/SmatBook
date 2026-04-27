<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bank_statement_lines')) {
            return;
        }

        Schema::table('bank_statement_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_statement_lines', 'matched_transaction_id')) {
                $table->unsignedBigInteger('matched_transaction_id')->nullable()->after('bank_id')->index();
            }
            if (!Schema::hasColumn('bank_statement_lines', 'matched_at')) {
                $table->timestamp('matched_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('bank_statement_lines', 'matched_by')) {
                $table->unsignedBigInteger('matched_by')->nullable()->after('matched_at')->index();
            }
            if (!Schema::hasColumn('bank_statement_lines', 'review_notes')) {
                $table->text('review_notes')->nullable()->after('matched_by');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('bank_statement_lines')) {
            return;
        }

        Schema::table('bank_statement_lines', function (Blueprint $table) {
            if (Schema::hasColumn('bank_statement_lines', 'review_notes')) {
                $table->dropColumn('review_notes');
            }
            if (Schema::hasColumn('bank_statement_lines', 'matched_by')) {
                $table->dropColumn('matched_by');
            }
            if (Schema::hasColumn('bank_statement_lines', 'matched_at')) {
                $table->dropColumn('matched_at');
            }
            if (Schema::hasColumn('bank_statement_lines', 'matched_transaction_id')) {
                $table->dropColumn('matched_transaction_id');
            }
        });
    }
};
