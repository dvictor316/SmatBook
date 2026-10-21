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
