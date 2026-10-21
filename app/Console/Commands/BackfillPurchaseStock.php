<?php

namespace App\Console\Commands;

use App\Support\PurchaseStockBackfill;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillPurchaseStock extends Command
{
    protected $signature = 'inventory:backfill-purchase-stock
        {--date= : Receipt creation day (YYYY-MM-DD); defaults to yesterday in the selected timezone}
        {--timezone=Africa/Lagos : Calendar timezone for the receipt and registration dates}
        {--registered-from= : First business registration day; defaults to the day before the receipt date}
        {--registered-to= : Last business registration day; defaults to the receipt date}
        {--company= : Restrict to this company ID}
        {--dry-run : Explicitly preview without changing data (the default)}
        {--apply : Apply verified corrections; refuses ambiguous business selection}';

    protected $description = 'Preview or backfill verified missing stock for one receipt day in recently registered businesses.';

    public function handle(PurchaseStockBackfill $backfill): int
    {
        try {
            if ($this->option('apply') && $this->option('dry-run')) throw new \InvalidArgumentException('Choose either --dry-run or --apply.');
            $timezone = (string) $this->option('timezone');
            new \DateTimeZone($timezone);
            $date = $this->day($this->option('date') ?: CarbonImmutable::now($timezone)->subDay()->format('Y-m-d'), $timezone);
            $from = $this->day($this->option('registered-from') ?: $date->subDay()->format('Y-m-d'), $timezone);
            $to = $this->day($this->option('registered-to') ?: $date->format('Y-m-d'), $timezone);
            if ($from > $to) throw new \InvalidArgumentException('Registration start must not follow registration end.');
            if ($date >= CarbonImmutable::now($timezone)->startOfDay()) throw new \InvalidArgumentException('Backfill only supports completed days before today.');
            if ($this->option('company') !== null && (!ctype_digit((string) $this->option('company')) || (int) $this->option('company') < 1)) {
                throw new \InvalidArgumentException('--company must be a positive company ID.');
            }
            foreach (['companies' => ['created_at'], 'purchases' => ['company_id', 'created_at', 'purchase_type', 'branch_id', 'branch_name', 'user_id'],
                'purchase_items' => ['created_at', 'stock_units', 'conversion_factor', 'branch_id', 'branch_name'],
                'products' => ['stock', 'stock_quantity', 'company_id'], 'product_branch_stocks' => ['quantity', 'company_id'],
                'inventory_history' => ['reference', 'remarks', 'company_id', 'branch_id', 'branch_name'], 'purchase_returns' => ['purchase_id']] as $table => $columns) {
                if (!Schema::hasTable($table) || !Schema::hasColumns($table, $columns)) throw new \RuntimeException("Required {$table} columns are missing. Run the application's migrations first.");
            }
            $storageTimezone = (string) config('app.timezone', 'UTC');
            $stamp = fn ($day) => $day->setTimezone($storageTimezone)->format('Y-m-d H:i:s');
            $start = $stamp($date); $end = $stamp($date->addDay());
            $companies = DB::table('companies')->where('created_at', '>=', $stamp($from))->where('created_at', '<', $stamp($to->addDay()))
                ->when($this->option('company'), fn ($q) => $q->where('id', (int) $this->option('company')))->orderBy('id')->get();
            $this->info('Receipt day: '.$date->format('Y-m-d').' ('.$timezone.'); business registrations: '.$from->format('Y-m-d').' through '.$to->format('Y-m-d').'.');
            $selected = [];
            foreach ($companies as $company) {
                $receipts = $backfill->receipts((int) $company->id, $start, $end);
                if ($receipts->isNotEmpty()) $selected[] = [$company, $receipts];
            }
            $this->table(['Company ID', 'Business', 'Eligible receipt lines'], array_map(fn ($entry) => [$entry[0]->id, $entry[0]->name, $entry[1]->count()], $selected));
            if (!$selected) { $this->info('No matching businesses with eligible purchases. Nothing changed.'); return self::SUCCESS; }
            $apply = (bool) $this->option('apply');
            if ($apply && count($selected) > 1) throw new \RuntimeException('Several businesses match. Preview first, then supply --company=ID to apply only to the intended client.');
            $review = 0; $added = 0.0;
            foreach ($selected as [$company, $receipts]) {
                $this->info('Business #'.$company->id.': '.$company->name);
                $rows = [];
                foreach ($receipts->groupBy('product_id') as $group) {
                    foreach ($backfill->reconcile((int) $company->id, $group, $start, $end, $apply, $date->format('Y-m-d')) as $row) {
                        $rows[] = [$row['product'], $row['purchase'], $row['action'], $row['units'], $row['reason']];
                        $review += $row['action'] === 'REVIEW' ? 1 : 0;
                        if (in_array($row['action'], ['ADDED', 'WOULD ADD'], true)) $added += $row['units'];
                    }
                }
                $this->table(['Product', 'Purchase', 'Action', 'Stock units', 'Evidence / reason'], $rows);
            }
            $this->info(($apply ? 'Applied verified corrections' : 'PREVIEW ONLY; no data changed').". Missing units: {$added}; review rows skipped: {$review}.");
            return $review > 0 ? self::FAILURE : self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function day(string $value, string $timezone): CarbonImmutable
    {
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $value, $timezone);
        if (!$day || $day->format('Y-m-d') !== $value) throw new \InvalidArgumentException('Dates must be valid YYYY-MM-DD calendar dates.');
        return $day;
    }
}
