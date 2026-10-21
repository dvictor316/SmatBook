<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Support\SuperAdminRecordMigration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrateSuperAdminRecords extends Command
{
    protected $signature = 'workspace:migrate-super-admin-records
        {--user= : Confirmed creator/owner super-admin ID}
        {--from= : Original company ID containing confirmed records}
        {--to= : Super admin private destination company ID}
        {--expect-sales= : Expected original sales count; required when applying}
        {--expect-products= : Expected original product count; required when applying}
        {--apply : Apply the verified connected record plan; omission is read-only}';

    protected $description = 'Preview or migrate explicitly confirmed super-admin records with related stock and accounting links.';

    public function handle(SuperAdminRecordMigration $migration): int
    {
        try {
            foreach (['user', 'from', 'to'] as $key) {
                if (! ctype_digit((string) $this->option($key)) || (int) $this->option($key) < 1) throw new \RuntimeException('--'.$key.' must be an explicit positive ID.');
            }
            foreach (['expect-sales', 'expect-products'] as $key) {
                if (($this->option('apply') || $this->option($key) !== null) && ! ctype_digit((string) $this->option($key))) {
                    throw new \RuntimeException('--'.$key.' must be an explicit expected count when applying.');
                }
            }
            DB::transaction(function () use ($migration) {
                $apply = (bool) $this->option('apply');
                $userId = (int) $this->option('user'); $from = (int) $this->option('from'); $to = (int) $this->option('to');
                if ($apply) {
                    DB::table('users')->where('id', $userId)->lockForUpdate()->first();
                    DB::table('companies')->whereIn('id', [$from, $to])->orderBy('id')->lockForUpdate()->get();
                }
                $plan = $migration->plan($userId, $from, $to);
                $this->info('Confirmed account #'.$userId.': company #'.$from.' → private company #'.$to);
                $this->table(['Table', 'Linked rows', 'Company links to move'], collect($plan['counts'])->map(fn ($counts, $table) => [$table, $counts['linked'], $counts['reassigned']])->values()->all());
                $this->table(['Source records left in place', 'Count'], collect($plan['retained'])->map(fn ($count, $table) => [$table, $count])->values()->all());
                if ($plan['problems']) {
                    foreach ($plan['problems'] as $problem) $this->error('REVIEW: '.$problem);
                    throw new \RuntimeException('Migration blocked for review. No records moved.');
                }
                if (array_sum($plan['roots']) === 0) { $this->info('No original creator-matched roots remain. Nothing changed.'); return; }
                foreach (['sales' => 'expect-sales', 'products' => 'expect-products'] as $table => $option) {
                    if ($this->option($option) !== null && (int) $this->option($option) !== ($plan['roots'][$table] ?? 0)) {
                        throw new \RuntimeException('Expected '.$table.' count differs from the current data. Preview again.');
                    }
                }
                if (! $apply) { $this->info('READ ONLY: review this plan before using --apply. No records or settings changed.'); return; }
                if (! app()->environment('testing') && ! app()->isDownForMaintenance()) throw new \RuntimeException('Enable maintenance mode before applying so live sales cannot change during migration.');
                // Recheck and lock the exact rows; snapshots are saved before the first change.
                foreach ($plan['linked_before'] as $table => $rows) {
                    foreach (array_chunk(array_keys($rows), 250) as $ids) {
                        $locked = DB::table($table)->whereIn('id', $ids)->lockForUpdate()->get();
                        if ($locked->count() !== count($ids)) throw new \RuntimeException('A selected record disappeared during planning. Preview again.');
                        foreach ($locked as $row) {
                            if ((array) $row !== $rows[$row->id]) throw new \RuntimeException('A selected record changed during planning. Preview again.');
                        }
                    }
                }
                $directory = storage_path('app/private/workspace-migrations');
                if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) throw new \RuntimeException('Cannot create private backup directory.');
                $file = $directory.'/'.now()->format('Ymd-His').'-'.Str::uuid().'.json';
                $handle = fopen($file, 'x');
                if (! $handle || ! chmod($file, 0600)) throw new \RuntimeException('Cannot create protected before-change backup.');
                $json = json_encode($plan, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                if (fwrite($handle, $json) !== strlen($json)) { fclose($handle); throw new \RuntimeException('Backup write was incomplete.'); }
                fclose($handle);
                $this->info('Private before-change backup: '.$file);
                foreach ($plan['before'] as $table => $rows) {
                    foreach (array_chunk(array_keys($rows), 250) as $ids) DB::table($table)->whereIn('id', $ids)->update(['company_id' => $to]);
                }
                DB::table('settings')->updateOrInsert(['key' => 'branches_json_company_'.$to], ['value' => json_encode($plan['branches'], JSON_THROW_ON_ERROR)]);
                DB::table('settings')->updateOrInsert(['key' => 'workspace_default_branch_scope_company_'.$to], ['value' => 'all']);
                DB::table('users')->where('id', $userId)->update(['company_id' => $to]);
                ActivityLog::withoutEvents(fn () => ActivityLog::record('Workspace', 'records_migrated',
                    'Migrated confirmed account #'.$userId.' records from company #'.$from.' to #'.$to.'. Backup: '.basename($file),
                    ['user_id' => $userId, 'company_id' => $to, 'properties' => ['counts' => $plan['counts'], 'backup' => basename($file)]]));
                foreach ([$from, $to] as $companyId) {
                    Cache::forget('metrics_co_'.$companyId.'_global');
                    Cache::forget('metrics_co_'.$companyId.'_branch_all');
                    foreach ($plan['branches'] as $branch) Cache::forget('metrics_co_'.$companyId.'_branch_'.($branch['id'] ?? 'all'));
                }
                $this->info('Migration complete. Record IDs, amounts, stock and history retained. Source company, staff and subscriptions were not deleted or reassigned.');
                $this->line('Sign out and sign in, then open Business with All Branches to see the recovered records.');
            });
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
