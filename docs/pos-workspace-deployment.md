# POS workspace update

The selling screen uses a wide product catalogue, grid/list views, category filters, and a cart on the right. Checkout opens a dedicated screen at `/pos#checkout`, retaining the live cart when returning to products. Product pack sizes, price lists, discounts, tax, customer wallets, payment accounts, suspended sales, offline submission, and receipt formats use the existing handlers.

Search shares one matcher between the shelf and product picker. Exact SKU and barcode matches rank first, including alternate barcodes and leading zeros. Multiword name prefixes can appear in any order. Category filters remain active; a previously selected product cannot leak into unrelated results. Numeric fragments do not match the middle of barcodes.

The update also fixes an existing JavaScript syntax error in the legacy discount handler and preserves the configured cash account when resetting for the next sale.

## Verification

- Browser checked at 1366 × 768 and 390 × 844: product selection, editor, add to cart, checkout/back navigation, customer controls, bank fields, actual filtered product visibility, and layout without horizontal page overflow.
- Simulated successful submission: correct cart total and cash account, receipt URL, empty-cart reset, return to selling, and cash account retained for the next sale. This check does not create a real sale.
- Rendered inline JavaScript and both new scripts pass syntax checks. Browser reports no JavaScript exceptions.
- Search and offline authentication JavaScript checks pass.
- Targeted Laravel suite: 20 passed, 2 failed; the new workspace test passes with 9 assertions. Both failures were reproduced using the original POS template from Git: `PosPaymentFormTest` expects a removed separate card-account wrapper, and `PosSalesBankFilterTest` expects a 422 response for an unavailable account but receives 200. Those existing failures remain unresolved.

## Publish from Ubuntu

Only stage the POS files below; unrelated local changes are excluded.

```bash
cd /home/victor/smat-book
git add resources/views/pos/index.blade.php public/assets/css/pos-workspace.css public/js/pos-product-search.js public/js/pos-workspace.js tests/Feature/Sales/PosWorkspaceTest.php tests/js/pos-product-search.test.cjs docs/pos-workspace-deployment.md
git commit -m "Redesign POS catalogue and dedicated checkout workspace"
git push origin main
```

## Update the Ubuntu server through PuTTY

The server path below follows the deployment path already documented in this repository. Use the account that owns the application checkout.

```bash
cd /var/www/smartprobook.com
git pull --ff-only origin main
php artisan optimize:clear
php artisan view:cache
```

This update requires no migration, dependency installation, or frontend build. Refresh the POS with Ctrl+Shift+R after deployment. The new POS assets include modification-time version URLs.
