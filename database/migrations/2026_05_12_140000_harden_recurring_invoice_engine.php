<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_invoice_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('recurring_invoice_templates', 'source_type')) {
                $table->string('source_type', 40)->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('recurring_invoice_templates', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type')->index();
            }
            if (!Schema::hasColumn('recurring_invoice_templates', 'timezone')) {
                $table->string('timezone', 80)->default('Africa/Lagos')->after('due_days');
            }
            if (!Schema::hasColumn('recurring_invoice_templates', 'payment_link_enabled')) {
                $table->boolean('payment_link_enabled')->default(true)->after('payment_instructions');
            }
            if (!Schema::hasColumn('recurring_invoice_templates', 'auto_payment_enabled')) {
                $table->boolean('auto_payment_enabled')->default(false)->after('payment_link_enabled');
            }
            if (!Schema::hasColumn('recurring_invoice_templates', 'custom_fields')) {
                $table->json('custom_fields')->nullable()->after('items');
            }
            if (!Schema::hasColumn('recurring_invoice_templates', 'attachments')) {
                $table->json('attachments')->nullable()->after('custom_fields');
            }
            if (!Schema::hasColumn('recurring_invoice_templates', 'failure_count')) {
                $table->unsignedInteger('failure_count')->default(0)->after('occurrences_count');
            }
            if (!Schema::hasColumn('recurring_invoice_templates', 'last_failure_at')) {
                $table->timestamp('last_failure_at')->nullable()->after('failure_count');
            }
            if (!Schema::hasColumn('recurring_invoice_templates', 'last_failure_message')) {
                $table->text('last_failure_message')->nullable()->after('last_failure_at');
            }
        });

        Schema::table('recurring_invoice_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('recurring_invoice_logs', 'event_type')) {
                $table->string('event_type', 40)->default('generation')->after('sale_id')->index();
            }
            if (!Schema::hasColumn('recurring_invoice_logs', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('generated_by');
            }
            if (!Schema::hasColumn('recurring_invoice_logs', 'finished_at')) {
                $table->timestamp('finished_at')->nullable()->after('started_at');
            }
            if (!Schema::hasColumn('recurring_invoice_logs', 'payload')) {
                $table->json('payload')->nullable()->after('message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recurring_invoice_logs', function (Blueprint $table) {
            foreach (['payload', 'finished_at', 'started_at', 'event_type'] as $column) {
                if (Schema::hasColumn('recurring_invoice_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('recurring_invoice_templates', function (Blueprint $table) {
            foreach ([
                'last_failure_message', 'last_failure_at', 'failure_count',
                'attachments', 'custom_fields', 'auto_payment_enabled',
                'payment_link_enabled', 'timezone', 'source_id', 'source_type',
            ] as $column) {
                if (Schema::hasColumn('recurring_invoice_templates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
