# Enterprise sales workspace recovery

An empty Enterprise dashboard can mean a different company is selected or the branch filter excludes historical sales. A newly provisioned private company does not inherit another company's sales. Workspace provisioning does not copy or delete operational records.

First try the Business dashboard with All Branches:

`/workspace/business/dashboard?all_branches=1&branch_scope=all`

If records remain missing, run this read-only command on the production server:

```bash
cd /var/www/smartprobook.com
php artisan workspace:recover-super-admin
```

It lists super-admin account IDs, current primary assignments, related companies, active record counts across all branches, historical sales branches for verified owned companies, and unassigned sales counts. `--user=123` limits the audit to one super-admin account. Candidate discovery includes companies with sales created by that account, but creator IDs alone never establish ownership. Counts exclude soft-deleted sales. Nothing is modified by default.

After identifying the original company and confirming it is the intended business, preview the selection:

```bash
php artisan workspace:recover-super-admin --user=123 --company=456
```

To restore a verified owned company as that account's primary workspace:

```bash
php artisan workspace:recover-super-admin --user=123 --company=456 --apply
```

Replace the example IDs with the IDs from the audit. Application requires explicit user and company IDs, rechecks the super-admin role and consistent owner fields under a transaction, and rejects unowned or conflicting client companies. It changes only the selected super-admin primary assignment and writes an activity entry. It does not move sales, reassign business owners, merge companies, change subscriptions or branch settings, or delete the newer company. An already-correct assignment is unchanged.

Sign out and sign in again, open Business, then select All Branches. Existing sessions may otherwise retain the newer selected workspace. Historical records in a company with unverified ownership or no company require further review; this command does not transfer them automatically.

Future automatic primary-assignment changes record the previous and new company IDs in the workspace activity log. The audit still needs production access or its output to establish the location of an affected account's actual records; passing local tests does not establish production data state.

## Retire the empty generated duplicate

Keep the original business and remove only the automatically created empty Enterprise business from workspace selection. Use IDs from the audit; the example IDs below are placeholders:

```bash
# Preview: 123 is the super-admin account, 456 is the original company to keep,
# and 789 is the newly generated duplicate.
php artisan workspace:recover-super-admin --user=123 --company=456 --retire-empty=789

# Apply the same verified selection.
php artisan workspace:recover-super-admin --user=123 --company=456 --retire-empty=789 --apply
```

The duplicate must match that account's generated `Name Enterprise Business` name, have no custom domain, and belong to the same super admin. Every database table with a `company_id` column is checked, including soft-deleted records; any operational rows, subscriptions, other assigned users, or saved business profile settings block retirement. The generated default branch setting and activity history are preserved. Preview performs no writes.

Application is transactional: the original company becomes the account's primary business, the empty duplicate is marked inactive and retired from the accessible-workspace list, and an activity entry records the operation. No company row or operational record is deleted, so database cascades cannot remove records. The retired company cannot be selected by browser or API workspace resolution, and automatic provisioning reuses the retained original business. Sign out and sign in again after applying.

Do not choose the company IDs from a business name alone. Production cleanup still requires the audit output to confirm which ID is the original and which is the generated duplicate.
