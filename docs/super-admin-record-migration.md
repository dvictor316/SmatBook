# Move confirmed Enterprise records to the private workspace

This is separate from changing a company owner or restoring a primary workspace link. The operator has confirmed that user #4's Enterprise sales/products currently recorded under company #1 should belong to private company #157. Company #1 is still associated with user #5 and cashiers #84/#85. Those identity links and company #1 must not be deleted or reassigned by this recovery.

Deploy the migration code, then preview the exact selection on the production server:

```bash
cd /var/www/smartprobook.com
php artisan workspace:migrate-super-admin-records --user=4 --from=1 --to=157 --expect-sales=26 --expect-products=211
```

Preview makes no persistent changes. It starts with sales, products and purchases explicitly created by the confirmed super admin in the specified source company. It follows declared/conventional document, inventory, customer, supplier, category and accounting relationships, including supported polymorphic ledger sources. Connected rows without creator IDs can be included; unrelated unattributed customers/records remain in the source. Shared/cross-company relationships, another creator, missing parents, unknown model source types, unstable IDs and unlinked accounting rows block the entire migration. Counts include deleted rows to retain historical relationships.

If the preview is clear, apply during a brief maintenance window:

```bash
php artisan down
php artisan workspace:migrate-super-admin-records --user=4 --from=1 --to=157 --expect-sales=26 --expect-products=211 --apply
php artisan up
```

Always run `php artisan up` after the migration command, including if it reports a failure. Application refuses to run outside maintenance mode in production. The command re-plans under transaction locks, checks the expected sales/product counts, locks/rechecks every selected row, and saves a private before-change JSON backup before writing. Backups are in `storage/app/private/workspace-migrations/` with file mode `0600`. Keep the printed backup path; do not publish or upload its contents. It contains record before-images and the previous primary assignment and destination branch settings for operator-assisted rollback if required.

Document updates change company IDs and private lookup references only. Shared units and categories are copied to the destination; their original rows and other businesses’ references remain unchanged. Lookup copies never expand the selection to their other products/documents. The private backup includes the allocated lookup IDs and original reference values. Existing record IDs, amounts, stock quantities, document relationships, dates and history remain intact; model observers are bypassed to avoid reposting inventory or duplicating accounting entries. The selected user's primary assignment becomes #157. Original branch IDs are preserved in the destination configuration. A destination-only default scope makes recovered super-admin logins start at All Branches, so legacy branchless records are visible; explicitly choosing a branch still works. Existing linked users, the source company's ownership, subscriptions, unrelated records and other companies are left unchanged. A transaction failure rolls all database changes back; the protected backup remains available. Re-running after successful movement finds no source roots and makes no additional changes.

Sign out and sign in, then open:

`https://smartprobook.com/workspace/business/dashboard?all_branches=1&branch_scope=all`

The navbar omits the Active Business pill when only one accessible workspace exists. Add another business remains available from the profile menu. Branch switching remains available. The old company is not deleted globally because it still has other linked accounts.

If preview reports `REVIEW`, stop and inspect that output; do not manually bulk-update all company IDs, change the source company owner, or delete company #1. A clear local regression suite establishes code behavior, not that the live dataset has been migrated.

## Legacy records found during this deployment

The first production preview was blocked without changing any records. It incorrectly expanded global unit/category references into other businesses. The corrected traversal treats those tables as lookup-only parents and privately copies the selected lookups. Hotel folio source labels using the four known posting controllers are audit metadata; real folio, stay, reservation and property links are followed separately. Unknown model types and actual cross-company document links still block migration.

Production also reported pre-existing missing related parents on transactions #9 and #10. To retain those historical entries exactly as recorded (without inventing parents or changing money), repeat the preview with `--preserve-missing-ledger=9,10`. The output names each missing table/parent ID as a NOTE. Use that exact option on apply only when accepting those existing historical gaps. This option never allows an existing foreign-company parent, missing normal foreign key, unknown model type or any other transaction ID. Omitting it retains the default blocking behavior. Review all other REVIEW errors before applying.
