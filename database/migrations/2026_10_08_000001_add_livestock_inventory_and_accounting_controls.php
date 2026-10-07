<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livestock_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('farm_id')->index();
            $table->unsignedBigInteger('flock_id')->nullable()->index();
            $table->date('movement_date')->index();
            $table->string('item_type', 20)->index();
            $table->string('movement_type', 30)->index();
            $table->decimal('quantity', 16, 3);
            $table->string('unit', 20);
            $table->decimal('unit_cost', 16, 4)->default(0);
            $table->decimal('total_value', 18, 2)->default(0);
            $table->string('reference', 100)->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->index(['source_type', 'source_id']);
            $table->unique(['source_type', 'source_id', 'item_type', 'movement_type'], 'livestock_inventory_source_unique');
        });

        $this->backfillInventoryHistory();

        foreach (['livestock_opex_entries', 'livestock_revenue_entries'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('debit_account_id')->nullable()->index();
                $table->unsignedBigInteger('credit_account_id')->nullable()->index();
                $table->string('journal_reference', 100)->nullable()->index();
                $table->timestamp('posted_at')->nullable();
                $table->timestamp('reversed_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['livestock_opex_entries', 'livestock_revenue_entries'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['debit_account_id', 'credit_account_id', 'journal_reference', 'posted_at', 'reversed_at']);
            });
        }

        Schema::dropIfExists('livestock_inventory_movements');
    }

    private function backfillInventoryHistory(): void
    {
        DB::table('livestock_daily_productions')->orderBy('id')->chunkById(500, function ($rows) {
            $movements = [];
            foreach ($rows as $row) {
                if ((float) $row->feed_kg > 0) {
                    $movements[] = $this->historyRow($row, 'feed', 'consumption', -(float) $row->feed_kg, 'kg', 'Daily flock feed consumption');
                }
                if ((int) $row->total_good_eggs > 0) {
                    $movements[] = $this->historyRow($row, 'eggs', 'production', (float) $row->total_good_eggs, 'egg', 'Daily good egg production');
                }
            }
            if ($movements) {
                DB::table('livestock_inventory_movements')->insert($movements);
            }
        });

        DB::table('livestock_revenue_entries as revenue')
            ->join('livestock_farms as farm', 'farm.id', '=', 'revenue.farm_id')
            ->where('revenue.source', 'eggs')
            ->select('revenue.*', 'farm.eggs_per_crate')
            ->orderBy('revenue.id')
            ->chunkById(500, function ($rows) {
                $movements = [];
                foreach ($rows as $row) {
                    $multiplier = in_array(strtolower(trim((string) $row->unit)), ['crate', 'crates', 'tray', 'trays'], true) ? (int) $row->eggs_per_crate : 1;
                    $movements[] = [
                        'company_id' => $row->company_id, 'farm_id' => $row->farm_id, 'flock_id' => $row->flock_id,
                        'movement_date' => $row->revenue_date, 'item_type' => 'eggs', 'movement_type' => 'sale',
                        'quantity' => -((float) $row->quantity * $multiplier), 'unit' => 'egg', 'unit_cost' => 0,
                        'total_value' => 0, 'reference' => $row->reference, 'source_type' => App\Models\LivestockRevenueEntry::class,
                        'source_id' => $row->id, 'notes' => $row->description ?: 'Egg sale', 'created_by' => $row->created_by,
                        'created_at' => $row->created_at, 'updated_at' => $row->updated_at,
                    ];
                }
                if ($movements) {
                    DB::table('livestock_inventory_movements')->insert($movements);
                }
            }, 'revenue.id', 'id');
    }

    private function historyRow(object $row, string $itemType, string $movementType, float $quantity, string $unit, string $notes): array
    {
        return [
            'company_id' => $row->company_id, 'farm_id' => $row->farm_id, 'flock_id' => $row->flock_id,
            'movement_date' => $row->production_date, 'item_type' => $itemType, 'movement_type' => $movementType,
            'quantity' => $quantity, 'unit' => $unit, 'unit_cost' => 0, 'total_value' => 0, 'reference' => null,
            'source_type' => App\Models\LivestockDailyProduction::class, 'source_id' => $row->id,
            'notes' => $notes, 'created_by' => $row->recorded_by, 'created_at' => $row->created_at, 'updated_at' => $row->updated_at,
        ];
    }
};
