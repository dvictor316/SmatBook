<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('folio_items')) {
            Schema::table('folio_items', function (Blueprint $table) {
                if (!Schema::hasColumn('folio_items', 'service_code')) {
                    $table->string('service_code', 60)->nullable()->after('type');
                    $table->index('service_code');
                }
                if (!Schema::hasColumn('folio_items', 'service_date')) {
                    $table->date('service_date')->nullable()->after('service_code');
                    $table->index('service_date');
                }
                if (!Schema::hasColumn('folio_items', 'quantity')) {
                    $table->decimal('quantity', 14, 3)->default(1)->after('amount');
                }
                if (!Schema::hasColumn('folio_items', 'unit_price')) {
                    $table->decimal('unit_price', 14, 2)->default(0)->after('quantity');
                }
                if (!Schema::hasColumn('folio_items', 'source_type')) {
                    $table->string('source_type', 80)->nullable()->after('reservation_id');
                    $table->index('source_type');
                }
                if (!Schema::hasColumn('folio_items', 'source_id')) {
                    $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                    $table->index('source_id');
                }
                if (!Schema::hasColumn('folio_items', 'payment_account_id')) {
                    $table->unsignedBigInteger('payment_account_id')->nullable()->after('source_id');
                    $table->index('payment_account_id');
                }
                if (!Schema::hasColumn('folio_items', 'posting_key')) {
                    $table->string('posting_key', 120)->nullable()->after('description');
                    $table->index('posting_key');
                }
                if (!Schema::hasColumn('folio_items', 'meta')) {
                    $table->json('meta')->nullable()->after('posted_by');
                }
            });
        }

        if (!Schema::hasTable('hotel_night_audits')) {
            Schema::create('hotel_night_audits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('property_id')->index();
                $table->date('audit_date')->index();
                $table->string('status', 30)->default('completed')->index();
                $table->unsignedInteger('stays_scanned')->default(0);
                $table->unsignedInteger('charges_posted')->default(0);
                $table->unsignedInteger('charges_skipped')->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->unsignedBigInteger('run_by')->nullable()->index();
                $table->timestamp('run_at')->nullable();
                $table->unsignedBigInteger('reopened_by')->nullable()->index();
                $table->timestamp('reopened_at')->nullable();
                $table->text('reopen_reason')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'property_id', 'audit_date'], 'hotel_night_audits_unique');
            });
        }

        if (!Schema::hasTable('hotel_nightly_charges')) {
            Schema::create('hotel_nightly_charges', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('property_id')->index();
                $table->unsignedBigInteger('stay_id')->index();
                $table->unsignedBigInteger('folio_id')->index();
                $table->unsignedBigInteger('room_id')->nullable()->index();
                $table->date('charge_date')->index();
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('status', 30)->default('posted')->index();
                $table->unsignedBigInteger('folio_item_id')->nullable()->index();
                $table->unsignedBigInteger('night_audit_id')->nullable()->index();
                $table->unsignedBigInteger('posted_by')->nullable()->index();
                $table->timestamp('posted_at')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'property_id', 'stay_id', 'charge_date'], 'hotel_nightly_charge_unique');
            });
        }

        if (!Schema::hasTable('hotel_housekeeping_tasks')) {
            Schema::create('hotel_housekeeping_tasks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('property_id')->index();
                $table->unsignedBigInteger('room_id')->index();
                $table->unsignedBigInteger('stay_id')->nullable()->index();
                $table->string('task_type', 40)->default('checkout_clean')->index();
                $table->string('status', 30)->default('open')->index();
                $table->string('priority', 20)->default('normal')->index();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('completed_by')->nullable()->index();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hotel_maintenance_tickets')) {
            Schema::create('hotel_maintenance_tickets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('property_id')->index();
                $table->unsignedBigInteger('room_id')->index();
                $table->string('ticket_no', 60)->index();
                $table->string('status', 30)->default('open')->index();
                $table->string('severity', 20)->default('medium')->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('reported_by')->nullable()->index();
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->unsignedBigInteger('resolved_by')->nullable()->index();
                $table->timestamp('resolved_at')->nullable();
                $table->text('resolution_note')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'ticket_no']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_maintenance_tickets');
        Schema::dropIfExists('hotel_housekeeping_tasks');
        Schema::dropIfExists('hotel_nightly_charges');
        Schema::dropIfExists('hotel_night_audits');
    }
};
