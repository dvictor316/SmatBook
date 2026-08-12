<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('reservations')) {
            $this->ensureIndexes();
            return;
        }

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('property_id')->index();
            $table->string('reservation_number')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('room_type_id')->nullable()->index();
            $table->unsignedBigInteger('room_id')->nullable()->index();
            $table->date('arrival_date');
            $table->time('arrival_time')->nullable();
            $table->date('departure_date');
            $table->time('departure_time')->nullable();
            $table->unsignedInteger('nights')->default(1);
            $table->unsignedInteger('adults')->default(1);
            $table->unsignedInteger('children')->default(0);
            $table->unsignedBigInteger('rate_plan_id')->nullable()->index();
            $table->decimal('nightly_rate', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('service_charge', 14, 2)->default(0);
            $table->decimal('other_charges', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('deposit_required', 14, 2)->default(0);
            $table->decimal('deposit_received', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);
            $table->string('source')->nullable();
            $table->text('special_requests')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('status')->default('inquiry')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('confirmed_by')->nullable()->index();
            $table->unsignedBigInteger('cancelled_by')->nullable()->index();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('no_show_at')->nullable();
            $table->unsignedBigInteger('checkin_id')->nullable()->index();
            $table->unsignedBigInteger('checkout_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['company_id', 'reservation_number'], 'res_company_resno_uq');
            $table->index(['company_id','property_id','arrival_date','departure_date'], 'res_cmp_prop_arr_dep_idx');
        });

        $this->ensureIndexes();
    }

    private function ensureIndexes(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (!$this->indexExists('reservations', 'res_company_resno_uq')) {
                $table->unique(['company_id', 'reservation_number'], 'res_company_resno_uq');
            }

            if (!$this->indexExists('reservations', 'res_cmp_prop_arr_dep_idx')) {
                $table->index(['company_id','property_id','arrival_date','departure_date'], 'res_cmp_prop_arr_dep_idx');
            }
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        $result = DB::select(
            'SELECT COUNT(1) AS count FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $tableName, $indexName]
        );

        return (int) ($result[0]->count ?? 0) > 0;
    }

    public function down()
    {
        Schema::dropIfExists('reservations');
    }
};
