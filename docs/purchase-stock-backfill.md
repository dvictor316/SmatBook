# Purchase stock backfill

The command `inventory:backfill-purchase-stock` previews stock corrections for a completed receipt creation day. It defaults to yesterday in `Africa/Lagos` and businesses registered on that day or the preceding day. The date window is converted to the application's storage timezone; its end is exclusive.

For the requested 20 October 2026 correction:

```bash
php artisan inventory:backfill-purchase-stock --date=2026-10-20 --registered-from=2026-10-19 --registered-to=2026-10-20 --dry-run
```

The preview lists business IDs, purchase references, stock units, actions and supporting evidence. Review the business name and proposed quantities. To apply to the intended client, replace `123` with its company ID:

```bash
php artisan inventory:backfill-purchase-stock --date=2026-10-20 --registered-from=2026-10-19 --registered-to=2026-10-20 --company=123 --apply
```

Without `--company`, application is allowed only when exactly one business has eligible receipts. Multiple matches are rejected before any writes. Omitting `--apply` always previews. No new migrations are required on the current application schema.

- `POSTED`: a stock-in matches the receipt quantity and its line creation timestamp within two seconds; do not add it again.
- `WOULD ADD` / `ADDED`: an unmatched receipt has a consistent recorded opening balance and movement history. Add only its stored stock units, preserving subsequent transactions.
- `WOULD SYNC BRANCH` / `BRANCH SYNCED`: company stock is already posted, but a branch credit is missing. The correction cannot exceed that day's matched receipts; company stock is not increased.
- `WOULD SYNC` / `SYNCED`: align the legacy quantity field to canonical product stock without adding receipt units.
- `DONE`: that receipt item already has a backfill marker; no additional credit.
- `REVIEW`: no automatic receipt credit. Examples include inconsistent balances, unallocated manual stock-ins, missing ownership, unknown unit conversion, edited lines, returns or excessive precision.

Only actual inventory purchases are eligible. Purchase orders, asset purchases and synthetic stock-history purchases are excluded. An edited receipt line created outside the selected day is skipped. Matching is conservative and relies on the completeness of the recorded movement history; the preview is not a physical stock count.

Writes use a transaction per product, lock the source purchases and product, and record item-specific history markers with before/after balances. A branch-only correction records a zero-quantity audit entry. Stock updates bypass the product observer to avoid creating synthetic purchase duplicates. Re-running the command will not re-credit marked receipts. Exit code 1 means a selection/error occurred or some rows require review; eligible products processed earlier may already have committed, as shown in their audit entries.

Verification uses isolated SQLite fixtures:

```bash
php artisan test --compact tests/Feature/PurchaseStockBackfillTest.php
```
