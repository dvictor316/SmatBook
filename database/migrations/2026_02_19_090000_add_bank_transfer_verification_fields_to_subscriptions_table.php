<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            return;
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'transfer_bank_id')) {
                $table->unsignedBigInteger('transfer_bank_id')->nullable()->after('payment_gateway');
            }
            if (!Schema::hasColumn('subscriptions', 'transfer_reference')) {
                $table->string('transfer_reference')->nullable()->after('transfer_bank_id');
            }
            if (!Schema::hasColumn('subscriptions', 'transfer_payer_name')) {
                $table->string('transfer_payer_name')->nullable()->after('transfer_reference');
            }
            if (!Schema::hasColumn('subscriptions', 'transfer_proof')) {
                $table->string('transfer_proof')->nullable()->after('transfer_payer_name');
            }
            if (!Schema::hasColumn('subscriptions', 'transfer_submitted_at')) {
                $table->timestamp('transfer_submitted_at')->nullable()->after('transfer_proof');
            }
            if (!Schema::hasColumn('subscriptions', 'transfer_validated_by')) {
                $table->unsignedBigInteger('transfer_validated_by')->nullable()->after('transfer_submitted_at');
            }
            if (!Schema::hasColumn('subscriptions', 'transfer_validated_at')) {
                $table->timestamp('transfer_validated_at')->nullable()->after('transfer_validated_by');
            }
            if (!Schema::hasColumn('subscriptions', 'transfer_validation_note')) {
                $table->string('transfer_validation_note')->nullable()->after('transfer_validated_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            return;
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            $drop = [];
            foreach ([
                'transfer_bank_id',
                'transfer_reference',
                'transfer_payer_name',
                'transfer_proof',
                'transfer_submitted_at',
                'transfer_validated_by',
                'transfer_validated_at',
                'transfer_validation_note',
            ] as $col) {
                if (Schema::hasColumn('subscriptions', $col)) {
                    $drop[] = $col;
                }
            }

            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};

