# Record a confirmed previous external commission payment

The owner confirmed that manager user #37 (OGBODO THOMAS, state_manager) received the earlier NGN 68,250 commission before the last deployment. User #54 (Thomas Ogbodo, admin) is a different account and must not be modified. Production showed commission #2 (68,250) and #3 (35,000), both pending and unlinked; manual-review payouts #1 and #2 for 68,250; and failed payouts #3–#5 for 103,250.

Preview without writing or transferring funds:

```bash
cd /var/www/smartprobook.com
runuser -u www-data -- php artisan commissions:record-external-payment --manager=37 --commission=2 --payout=1 --duplicate=2 --amount=68250 --expect-available=35000 --recorded-by=4 --reason="Owner confirms earlier commission was paid externally before last deployment"
```

Append `--apply` to persist the same plan. The command locks the manager profile and its commission/payout rows, refuses changed amounts, other active requests, initiated transfers, conflicting commission links or an unexpected remaining balance, and verifies the resulting dashboard availability inside the transaction. It marks commission #2 paid, uses existing payout #1 to record the external settlement, and cancels duplicate #2. The original payment date was not supplied, so it is not invented: `paid_at` stays unchanged; reconciliation time, recorder, reason and original financial fields are retained in the audit log and payout metadata. No records are deleted, no transfer API is called, automatic/bank settings are preserved, and other accounts are untouched. Failures roll back all database changes. Repeating an already completed reconciliation makes no further changes.

After successful reconciliation, the ordinary dashboard payout request or this existing command can process the remaining eligible balance:

```bash
runuser -u www-data -- php artisan agents:process-payouts --manager=37
```

This second command can initiate a real bank transfer. It requires an enabled automatic payout profile, configured bank details, adequate gateway funding and the normal minimum threshold. It processes eligible commissions at execution time, so use it immediately after reconciliation for the expected NGN 35,000 balance. Do not run it if reconciliation fails. The existing scheduler handles later eligible deployment commissions using the saved automatic payout settings. Gateway status/webhooks, rather than CLI creation counts, confirm the payment outcome. Refresh Thomas's dashboard to verify paid/processing/available status.
