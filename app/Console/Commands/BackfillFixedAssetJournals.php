<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\FixedAsset;
use App\Models\Transaction;
use App\Support\LedgerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillFixedAssetJournals extends Command
{
    protected $signature   = 'backfill:fixed-asset-journals';
    protected $description = 'Correct fixed asset account sub_types and post missing acquisition journals without double-posting';

    public function handle(): int
    {
        $assets = FixedAsset::withoutGlobalScopes()->with('assetAccount')->get();

        if ($assets->isEmpty()) {
            $this->info('No fixed assets found.');
            return self::SUCCESS;
        }

        $fixedSubTypes = [Account::SUBTYPE_FIXED_ASSET, 'Non-Current Asset', 'Intangible Asset'];

        $this->info("Processing {$assets->count()} fixed asset(s)...");

        $subTypeFixed  = 0;
        $journalPosted = 0;
        $alreadyOk     = 0;
        $errors        = [];

        foreach ($assets as $asset) {
            try {
                $account = $asset->assetAccount;

                // ── 1. Always fix sub_type ──────────────────────────────────────
                if ($account && !in_array($account->sub_type, $fixedSubTypes, true)) {
                    $account->update(['sub_type' => Account::SUBTYPE_FIXED_ASSET]);
                    $subTypeFixed++;
                    $this->line("  sub_type fixed: {$asset->name} (account #{$account->id})");
                }

                // ── 2. Only post journal if the account has no balance yet ──────
                // Check for an existing acquisition journal specifically for this asset.
                $hasJournal = Transaction::withoutGlobalScopes()
                    ->where('related_id', $asset->id)
                    ->where('related_type', FixedAsset::class)
                    ->where('transaction_type', Transaction::TYPE_JOURNAL)
                    ->exists();

                if ($hasJournal) {
                    $alreadyOk++;
                    continue; // journal already posted — skip to avoid double-posting
                }

                // No journal found — check if the account already carries a balance
                // from another source (e.g. an opening balance or a purchase entry).
                $existingBalance = 0.0;
                if ($account) {
                    $totals = Transaction::withoutGlobalScopes()
                        ->where('account_id', $account->id)
                        ->whereNull('deleted_at')
                        ->selectRaw('SUM(debit) as d, SUM(credit) as c')
                        ->first();
                    $existingBalance = round((float)($totals->d ?? 0) - (float)($totals->c ?? 0), 2);
                }

                $cost = round((float)($asset->cost ?? 0), 2);

                if (abs($existingBalance) >= abs($cost) - 0.01 && $cost > 0) {
                    // Account already has the full cost as a balance — just needed sub_type fix.
                    $alreadyOk++;
                    $this->line("  balance already present: {$asset->name} (₦" . number_format($existingBalance, 2) . ")");
                    continue;
                }

                // Post the acquisition journal (DR asset account / CR AP)
                LedgerService::postFixedAssetAcquisition($asset);
                $journalPosted++;
                $this->line("  journal posted: {$asset->name} ₦" . number_format($cost, 2));

            } catch (\Throwable $e) {
                $errors[] = "Asset #{$asset->id} ({$asset->name}): " . $e->getMessage();
                $this->warn("  ERROR: {$asset->name} — " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Done.");
        $this->line("  sub_types corrected : {$subTypeFixed}");
        $this->line("  journals posted      : {$journalPosted}");
        $this->line("  already correct      : {$alreadyOk}");

        if (!empty($errors)) {
            $this->warn('Errors:');
            foreach ($errors as $err) {
                $this->line("  - {$err}");
            }
        }

        return self::SUCCESS;
    }
}
