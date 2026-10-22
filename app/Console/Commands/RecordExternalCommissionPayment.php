<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Support\DeploymentCommissionPayoutService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecordExternalCommissionPayment extends Command
{
    protected $signature = 'commissions:record-external-payment
        {--manager= : Exact manager user ID}
        {--commission= : Exact previously paid commission ID}
        {--payout= : Existing manual-review payout to record as externally paid}
        {--duplicate= : Comma-separated duplicate manual-review payout IDs to cancel}
        {--amount= : Exact amount already paid, in naira}
        {--expect-available= : Exact pending balance expected after reconciliation}
        {--recorded-by= : Super-admin user ID recording the confirmed external payment}
        {--reason= : Explanation of the previously completed external payment}
        {--apply : Persist the reconciliation; omission is a read-only preview}';

    protected $description = 'Record a confirmed previous external commission payment without sending money or deleting history.';

    public function handle(DeploymentCommissionPayoutService $service): int
    {
        try {
            foreach (['manager', 'commission', 'payout', 'recorded-by'] as $option) {
                if (! preg_match('/^[1-9][0-9]*$/', (string) $this->option($option))) throw new \RuntimeException('Provide an exact positive --'.$option.' ID.');
            }
            foreach (['amount', 'expect-available'] as $option) {
                if (! preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/', (string) $this->option($option))) throw new \RuntimeException('Provide an exact naira --'.$option.' amount.');
            }
            $amount = (int) round((float) $this->option('amount') * 100);
            $expected = (int) round((float) $this->option('expect-available') * 100);
            if ($amount <= 0 || trim((string) $this->option('reason')) === '') throw new \RuntimeException('A positive paid amount and an explanation are required.');
            $duplicateInput = (string) ($this->option('duplicate') ?? '');
            if ($duplicateInput !== '' && ! preg_match('/^[1-9][0-9]*(,[1-9][0-9]*)*$/', $duplicateInput)) throw new \RuntimeException('Provide exact comma-separated duplicate payout IDs.');
            $duplicates = $duplicateInput === '' ? [] : array_values(array_unique(array_map('intval', explode(',', $duplicateInput))));
            $managerId = (int) $this->option('manager');
            $commissionId = (int) $this->option('commission');
            $payoutId = (int) $this->option('payout');
            if (in_array($payoutId, $duplicates, true)) throw new \RuntimeException('The settlement payout cannot also be a duplicate.');

            DB::transaction(function () use ($service, $managerId, $commissionId, $payoutId, $duplicates, $amount, $expected) {
                // Use the same profile lock as payout creation to prevent concurrent claims.
                $profile = DB::table('deployment_managers')->where('user_id', $managerId)->lockForUpdate()->first();
                $user = DB::table('users')->where('id', $managerId)->first();
                $recorder = DB::table('users')->where('id', (int) $this->option('recorded-by'))->first();
                if (! $profile || ! $user || ($user->deleted_at ?? null)) throw new \RuntimeException('Active manager account/profile not found.');
                if (! $recorder || ($recorder->deleted_at ?? null) || ! in_array(strtolower((string) $recorder->role), ['super_admin', 'superadmin'], true)) throw new \RuntimeException('The recording user must be an active super admin.');
                $commissions = DB::table('deployment_commissions')->where('manager_id', $managerId)->orderBy('id')->lockForUpdate()->get();
                $payouts = DB::table('deployment_manager_payouts')->where('manager_id', $managerId)->orderBy('id')->lockForUpdate()->get();
                $commission = $commissions->firstWhere('id', $commissionId);
                $payout = $payouts->firstWhere('id', $payoutId);
                if (! $commission || ! $payout) throw new \RuntimeException('Commission and payout must both belong to the specified manager.');
                $meta = json_decode($payout->meta ?? '{}', true, 512, JSON_THROW_ON_ERROR) ?: [];
                if ($commission->status === 'paid' && (int) $commission->payout_id === $payoutId && $payout->status === 'paid'
                    && (int) ($meta['external_settlement']['commission_id'] ?? 0) === $commissionId) {
                    $this->info('Already reconciled. No changes and no transfer initiated.');
                    return;
                }
                if ($commission->status !== 'pending' || $commission->payout_id !== null
                    || (int) round((float) ($commission->commission_amount ?? $commission->amount) * 100) !== $amount) throw new \RuntimeException('Commission status, amount or payout link changed; nothing reconciled.');
                $selected = array_merge([$payoutId], $duplicates);
                foreach ($selected as $id) {
                    $row = $payouts->firstWhere('id', $id);
                    if (! $row || $row->status !== 'manual_review' || ! empty($row->transfer_reference)
                        || strtoupper((string) $row->currency) !== 'NGN'
                        || (int) round((float) $row->amount * 100) !== $amount) throw new \RuntimeException('Selected payouts must be matching NGN manual-review requests without an initiated transfer.');
                    if (DB::table('deployment_commissions')->where('payout_id', $id)->exists()) throw new \RuntimeException('A selected old payout is linked to a commission. Review before reconciliation.');
                }
                if ($payouts->contains(fn ($p) => in_array($p->status, ['pending', 'processing', 'manual_review'], true) && ! in_array((int) $p->id, $selected, true))) throw new \RuntimeException('Another active payout requires review before reconciliation.');
                $remaining = $commissions->filter(fn ($c) => (int) $c->id !== $commissionId && $c->status === 'pending');
                if ($remaining->contains(fn ($c) => $c->payout_id !== null)) throw new \RuntimeException('Remaining commissions already have payout links; review before reconciliation.');
                $remainingCents = (int) $remaining->sum(fn ($c) => (int) round((float) ($c->commission_amount ?? $c->amount) * 100));
                if ($remainingCents !== $expected) throw new \RuntimeException('Remaining pending balance differs from --expect-available. No changes made.');
                $this->info('Manager #'.$managerId.' '.$user->name.'; record NGN '.number_format($amount / 100, 2).' as previously paid.');
                $this->line('Retain payout #'.$payoutId.' as external payment history; cancel duplicates: '.implode(', ', $duplicates));
                $this->line('Remaining payable: NGN '.number_format($remainingCents / 100, 2).'; commission IDs: '.$remaining->pluck('id')->implode(', '));
                $this->line('Automatic payouts: '.($profile->auto_payout_enabled ? 'enabled' : 'disabled').'; bank profile: '.(! empty($profile->payout_bank_code) && ! empty($profile->payout_account_number) && ! empty($profile->payout_account_name) ? 'configured' : 'incomplete'));
                if (! $this->option('apply')) {
                    $this->info('READ ONLY: nothing changed and no money sent.');
                    return;
                }
                $now = now();
                $settlement = ['commission_id' => $commissionId, 'recorded_by' => (int) $recorder->id,
                    'recorded_at' => $now->toIso8601String(), 'reason' => trim((string) $this->option('reason')),
                    'original_payment_date_unknown' => true];
                $meta['external_settlement'] = $settlement;
                $meta['commission_ids'] = [$commissionId];
                DB::table('deployment_manager_payouts')->where('id', $payoutId)->update([
                    'status' => 'paid', 'gateway' => 'external', 'failure_reason' => null,
                    'approved_by' => $recorder->id, 'approved_at' => $now, 'processed_at' => $now,
                    // Do not invent a historical payment date; recorded_at identifies this reconciliation.
                    'meta' => json_encode($meta, JSON_THROW_ON_ERROR), 'updated_at' => $now,
                ]);
                foreach ($duplicates as $id) {
                    $duplicateMeta = json_decode($payouts->firstWhere('id', $id)->meta ?? '{}', true, 512, JSON_THROW_ON_ERROR) ?: [];
                    $duplicateMeta['external_settlement_duplicate'] = $settlement + ['settlement_payout_id' => $payoutId];
                    DB::table('deployment_manager_payouts')->where('id', $id)->update([
                        'status' => 'cancelled', 'failure_reason' => 'Duplicate request for previously paid commission; reconciled under payout #'.$payoutId,
                        'meta' => json_encode($duplicateMeta, JSON_THROW_ON_ERROR), 'updated_at' => $now,
                    ]);
                }
                DB::table('deployment_commissions')->where('id', $commissionId)->update([
                    'status' => 'paid', 'payout_id' => $payoutId, 'payout_reference' => $payout->payout_reference,
                    'processed_at' => $now, 'updated_at' => $now,
                ]);
                if ((int) round($service->summaryForManager($managerId)['available'] * 100) !== $expected) throw new \RuntimeException('Dashboard balance verification failed; all changes rolled back.');
                $auditPayout = fn ($row) => array_intersect_key((array) $row, array_flip(['id', 'manager_id', 'amount', 'currency', 'status', 'gateway', 'payout_reference', 'approved_by', 'approved_at', 'processed_at', 'paid_at', 'updated_at']));
                ActivityLog::withoutEvents(fn () => ActivityLog::record('Commissions', 'external_payment_recorded',
                    'Recorded previously paid commission #'.$commissionId.' for manager #'.$managerId.'; no money sent.',
                    ['user_id' => $recorder->id, 'properties' => $settlement + ['payout_id' => $payoutId, 'cancelled_duplicates' => $duplicates, 'amount' => $amount / 100, 'remaining' => $expected / 100,
                        'before' => ['commission' => (array) $commission, 'payout' => $auditPayout($payout), 'duplicates' => $payouts->whereIn('id', $duplicates)->map($auditPayout)->values()->all()]]]));
                $this->info('Reconciled. Historical records retained; remaining commission is payable through the existing dashboard. No bank transfer initiated.');
            });
            return self::SUCCESS;
        } catch (\Throwable $error) {
            $this->error($error->getMessage());
            return self::FAILURE;
        }
    }
}
