<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'project_id')) {
                $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            }
            if (!Schema::hasColumn('expenses', 'expense_claim_id')) {
                $table->foreignId('expense_claim_id')->nullable()->constrained('expense_claims')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'expense_claim_id')) {
                $table->dropConstrainedForeignId('expense_claim_id');
            }
            if (Schema::hasColumn('expenses', 'project_id')) {
                $table->dropConstrainedForeignId('project_id');
            }
        });
    }
};
