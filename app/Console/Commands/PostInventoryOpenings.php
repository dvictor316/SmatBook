<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Support\LedgerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PostInventoryOpenings extends Command
{
    protected $signature = 'ledger:post-inventory-openings
        {company : Business ID}
        {--product= : Limit the reconciliation to one product ID}
        {--apply : Post verified openings. The default is a read-only preview}';

    protected $description = 'Reconcile initial stock history with its original cost and post missing opening journals';

    public function handle(): int
    {
        $company = filter_var($this->argument('company'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $product = filter_var($this->option('product'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$company || ($this->option('product') !== null && !$product)
            || !DB::table('companies')->where('id', $company)->whereNull('deleted_at')->exists()) {
            $this->error('Provide a valid business ID and optional product ID.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $counts = [];
        DB::table('inventory_history')->where('company_id', $company)
            ->whereRaw("LOWER(TRIM(reference)) = 'initial stock' AND LOWER(type) = 'in'")->where('quantity', '>', 0)
            ->when($product, fn ($query) => $query->where('product_id', $product))
            ->chunkById(200, function ($movements) use ($company, $apply, &$counts) {
                foreach ($movements as $movement) {
                    [$status, $value, $reason] = DB::transaction(fn () => $this->reconcile($company, $movement, $apply));
                    $counts[$status] = ($counts[$status] ?? 0) + 1;
                    if ($status !== 'Already recorded') {
                        $this->line(sprintf('#%d · %s · %s · %s · %s', $movement->product_id,
                            $movement->branch_name ?: ($movement->branch_id ?: 'Unassigned'), $status,
                            number_format($value, 2), $reason));
                    }
                }
            });

        $this->table(['Result', 'Records'], collect($counts)->map(fn ($total, $status) => [$status, $total]));
        $this->info($apply ? 'Reconciliation completed.' : 'Preview completed. No postings were made.');

        return self::SUCCESS;
    }

    private function reconcile(int $company, object $movement, bool $apply): array
    {
        $product = Product::withoutGlobalScopes()->where('company_id', $company)->whereKey($movement->product_id)
            ->when($apply, fn ($query) => $query->lockForUpdate())->first();
        if (!$product) {
            return ['Review', 0, 'Product ownership could not be verified.'];
        }

        $journals = Transaction::withoutGlobalScopes()->where('company_id', $company);
        if ((clone $journals)->where('related_type', Product::class)->where('related_id', $product->id)
            ->where('transaction_type', Transaction::TYPE_OPENING_BALANCE)->exists()) {
            return ['Already recorded', 0, 'An opening journal already exists for the product.'];
        }

        if (blank($movement->branch_id) && blank($movement->branch_name)) {
            return ['Review', 0, 'Original branch is missing.'];
        }

        $multipleOpenings = DB::table('inventory_history')->where('company_id', $company)->where('product_id', $product->id)
            ->whereRaw("LOWER(TRIM(reference)) = 'initial stock' AND LOWER(type) = 'in'")->where('quantity', '>', 0)
            ->where('id', '<>', $movement->id)->exists();
        if ($multipleOpenings) {
            return ['Review', 0, 'More than one positive initial movement exists for the product.'];
        }

        $reference = 'AUTO-STK-'.$movement->id;
        $purchases = DB::table('purchases')->where('purchase_no', $reference)
            ->where(fn ($query) => $query->where('company_id', $company)->orWhereNull('company_id'))
            ->when(Schema::hasColumn('purchases', 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
            ->limit(2)->get();
        if ($purchases->count() !== 1) {
            return ['Review', 0, 'A unique original cost record could not be found.'];
        }

        $purchase = $purchases->first();
        if ((clone $journals)->where(fn ($query) => $query->where('reference', $reference)
            ->orWhere(fn ($source) => $source->where('related_type', Purchase::class)->where('related_id', $purchase->id)))->exists()) {
            return ['Review', 0, 'A linked journal exists. Check it before adding an opening.'];
        }

        $items = DB::table('purchase_items')->where('purchase_id', $purchase->id)->limit(2)->get();
        $item = $items->first();
        $quantity = (float) $movement->quantity;
        $cost = (float) $product->purchase_price;
        $value = round($quantity * $cost, 2);
        // Older mirrored receipts could use the selling price if the product had no saved cost.
        if ($items->count() !== 1 || (int) $item->product_id !== (int) $product->id || $value <= 0
            || abs((float) $item->qty - $quantity) > 0.000001
            || abs((float) $item->unit_price - $cost) > 0.000001
            || abs((float) $purchase->total_amount - $value) >= 0.005) {
            return ['Review', 0, 'Original quantity and cost could not both be confirmed.'];
        }

        if (!$apply) {
            return ['Would post', $value, $reference];
        }

        $posted = LedgerService::postProductOpeningStock(
            $product, $quantity, $movement->branch_id, $movement->branch_name,
            $company, $movement->user_id, substr($movement->created_at, 0, 10)
        );

        return [$posted ? 'Posted' : 'Review', $value, $posted ? $reference : 'The opening journal could not be posted.'];
    }
}
