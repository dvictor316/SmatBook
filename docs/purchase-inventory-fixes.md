# Purchase bills and inventory fixes

- Inventory name/SKU search now waits 1.2 seconds after the last keystroke. Enter or the search button still submits immediately. Input composition does not trigger premature navigation.
- Purchase bills have a Sales Price field labelled with its stock unit. Saving an edited value updates the product's retail price and base-unit price inside the purchase transaction. Purchase totals, purchase rates, wholesale prices, and separately configured pack selling prices are preserved. Business owners and users with inventory product-edit permission can change it; explicit permission overrides are respected. Blank or untouched fields leave prices unchanged.
- Add Supplier opens a form inside the bill. A successful save selects the new supplier and closes the form; failed validation displays errors while keeping the bill's products, quantities, prices, dates, notes, and selected files in place. The normal supplier page retains its existing redirect behavior.
- Inventory selection counts use the actual selected IDs, with correct select-all and deselect behavior. Deleted IDs are removed after successful single or bulk deletion. Selection storage is separated by user, business, and branch. The new storage version clears previous stale selections, and Clear selection resets the current set.
- The Excel template adds Purchase Unit and Stock Units per Purchase Unit in columns Q–R. Original columns retain their positions. Carton/roll conversions can be inferred from packaging; other purchase packs require a conversion of at least one stock unit. Cost and sales columns remain per stock unit; imported purchase-pack costs are converted for purchase bills. Earlier templates remain supported.

No migrations, dependency updates, or frontend builds are required.

## Publish from Ubuntu

```bash
cd ~/smat-book &&
git add app/Http/Controllers/ProductController.php app/Http/Controllers/PurchaseController.php app/Http/Controllers/SupplierController.php resources/views/Inventory/Products/index.blade.php resources/views/Purchases/add-purchases.blade.php tests/Feature/ProductBulkImportTest.php tests/Feature/PurchaseStockPostingTest.php docs/purchase-inventory-fixes.md &&
git commit -m "Fix inventory search and selection, bill pricing and supplier creation" &&
git push origin main
```

## Update the server through PuTTY

```bash
cd /var/www/smartprobook.com &&
umask 022 &&
git pull --ff-only origin main &&
for f in app/Http/Controllers/ProductController.php app/Http/Controllers/PurchaseController.php app/Http/Controllers/SupplierController.php resources/views/Inventory/Products/index.blade.php resources/views/Purchases/add-purchases.blade.php; do
    runuser -u www-data -- test -r "$f" || { echo "UNREADABLE: $f"; exit 1; }
done &&
runuser -u www-data -- php artisan optimize:clear &&
runuser -u www-data -- php artisan view:cache
```

Refresh the browser with Ctrl+Shift+R and download a fresh Excel template to see the new purchase-unit columns.
