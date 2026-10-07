<?php

namespace App\Services\Livestock;

use App\Models\LivestockDailyProduction;
use App\Models\LivestockFarm;
use App\Models\LivestockInventoryMovement;
use App\Models\LivestockRevenueEntry;

class LivestockInventoryService
{
    public function syncProduction(LivestockDailyProduction $production): void
    {
        $this->removeSource($production);

        if ((float) $production->feed_kg > 0) {
            $this->createSourceMovement($production, 'feed', 'consumption', -(float) $production->feed_kg, 'kg', 'Daily flock feed consumption');
        }

        if ((int) $production->total_good_eggs > 0) {
            $this->createSourceMovement($production, 'eggs', 'production', (float) $production->total_good_eggs, 'egg', 'Daily good egg production');
        }
    }

    public function syncEggSale(LivestockRevenueEntry $revenue, LivestockFarm $farm): void
    {
        $this->removeSource($revenue);
        if ($revenue->source !== 'eggs') {
            return;
        }

        $quantity = $this->eggQuantity((float) $revenue->quantity, (string) $revenue->unit, (int) $farm->eggs_per_crate);
        $this->createSourceMovement($revenue, 'eggs', 'sale', -$quantity, 'egg', $revenue->description ?: 'Egg sale');
    }

    public function removeSource(object $source): void
    {
        LivestockInventoryMovement::query()
            ->where('company_id', $source->company_id)
            ->where('source_type', $source::class)
            ->where('source_id', $source->id)
            ->delete();
    }

    public function balances(int $companyId, int $farmId): array
    {
        $balances = LivestockInventoryMovement::query()
            ->where('company_id', $companyId)
            ->where('farm_id', $farmId)
            ->selectRaw('item_type, COALESCE(SUM(quantity), 0) as balance')
            ->groupBy('item_type')
            ->pluck('balance', 'item_type');

        return ['feed' => (float) ($balances['feed'] ?? 0), 'eggs' => (float) ($balances['eggs'] ?? 0)];
    }

    private function createSourceMovement(object $source, string $itemType, string $movementType, float $quantity, string $unit, string $notes): void
    {
        $date = $source instanceof LivestockDailyProduction ? $source->production_date : $source->revenue_date;
        LivestockInventoryMovement::create([
            'company_id' => $source->company_id,
            'farm_id' => $source->farm_id,
            'flock_id' => $source->flock_id,
            'movement_date' => $date,
            'item_type' => $itemType,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'unit' => $unit,
            'reference' => $source->reference ?? null,
            'source_type' => $source::class,
            'source_id' => $source->id,
            'notes' => $notes,
            'created_by' => auth()->id(),
        ]);
    }

    private function eggQuantity(float $quantity, string $unit, int $eggsPerCrate): float
    {
        return in_array(strtolower(trim($unit)), ['crate', 'crates', 'tray', 'trays'], true)
            ? $quantity * $eggsPerCrate
            : $quantity;
    }
}
