<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_transfers')) {
            Schema::create('stock_transfers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('reference', 80);
                $table->dateTime('transfer_date');
                $table->string('from_branch_id')->nullable();
                $table->string('from_branch_name')->nullable();
                $table->string('to_branch_id')->nullable();
                $table->string('to_branch_name')->nullable();
                $table->string('status', 30)->default('completed')->index();
                $table->text('notes')->nullable();
                $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['company_id', 'reference'], 'stock_transfers_company_reference_unique');
                $table->index(['company_id', 'transfer_date'], 'stock_transfers_company_date_index');
            });
        }

        if (! Schema::hasTable('stock_transfer_audits')) {
            return;
        }

        if (! Schema::hasColumn('stock_transfer_audits', 'transfer_id')) {
            Schema::table('stock_transfer_audits', function (Blueprint $table) {
                $table->foreignId('transfer_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('stock_transfers')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('stock_transfer_audits', 'reversal_of_audit_id')) {
            Schema::table('stock_transfer_audits', function (Blueprint $table) {
                $table->unsignedBigInteger('reversal_of_audit_id')->nullable()->after('transfer_id');
                $table->index('reversal_of_audit_id', 'stock_transfer_audits_reversal_of_index');
            });
        }

        DB::table('stock_transfer_audits')
            ->whereNull('transfer_id')
            ->orderBy('id')
            ->chunkById(100, function ($audits) {
                foreach ($audits as $audit) {
                    $date = $audit->created_at ?: now();
                    $transferId = DB::table('stock_transfers')->insertGetId([
                        'company_id' => $audit->company_id,
                        'reference' => 'LEGACY-' . $audit->id,
                        'transfer_date' => $date,
                        'from_branch_id' => $audit->from_branch_id,
                        'from_branch_name' => $audit->from_branch_name,
                        'to_branch_id' => $audit->to_branch_id,
                        'to_branch_name' => $audit->to_branch_name,
                        'status' => 'completed',
                        'notes' => $audit->notes,
                        'initiated_by' => $audit->initiated_by,
                        'created_at' => $date,
                        'updated_at' => $audit->updated_at ?: $date,
                    ]);

                    DB::table('stock_transfer_audits')
                        ->where('id', $audit->id)
                        ->update(['transfer_id' => $transferId]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_transfer_audits') && Schema::hasColumn('stock_transfer_audits', 'reversal_of_audit_id')) {
            Schema::table('stock_transfer_audits', function (Blueprint $table) {
                $table->dropIndex('stock_transfer_audits_reversal_of_index');
                $table->dropColumn('reversal_of_audit_id');
            });
        }

        if (Schema::hasTable('stock_transfer_audits') && Schema::hasColumn('stock_transfer_audits', 'transfer_id')) {
            Schema::table('stock_transfer_audits', function (Blueprint $table) {
                $table->dropConstrainedForeignId('transfer_id');
            });
        }

        Schema::dropIfExists('stock_transfers');
    }
};
