<?php
$path = '/mnt/c/Users/victor/Desktop/smat-book/resources/views/Reports/Reports/balance-sheet.blade.php';
$c = file_get_contents($path);
if (!$c) { echo "ERROR: could not read file\n"; exit(1); }

// ── 1. Fix $isSystemAccount ─────────────────────────────────────────────────
$c = str_replace(
'$systemCodes = [\'SYS-BS-RECON\', \'SYS-OPENING-EQUITY\', \'SYS-CUST-AR\', \'SYS-SUPP-AP\', \'SYS-INV\'];

$isSystemAccount = function ($account) use ($systemCodes): bool {
    $name = strtolower(trim((string) ($account->name ?? \'\')));
    $code = strtoupper(trim((string) ($account->code ?? \'\')));
    if (in_array($code, $systemCodes, true)) return true;
    $patterns = [
        \'opening balance equity\',
        \'balance sheet reconciliation\',
        \'bank reconciliation suspense\',
        \'reconciliation reserve\',
        \'reconciliation suspense\',
    ];
    foreach ($patterns as $p) {
        if (str_contains($name, $p)) return true;
    }
    return false;
};',
'// Only pure plugging/reconciliation entries are hidden from line-item display.
// SYS-CUST-AR (AR), SYS-SUPP-AP (AP), SYS-INV (Inventory), and
// SYS-OPENING-EQUITY (Opening Balance Equity) are legitimate CoA accounts —
// excluding them drops their balances from totals and breaks Assets = L + E.
$systemHiddenCodes = [\'SYS-BS-RECON\'];

$isSystemAccount = function ($account) use ($systemHiddenCodes): bool {
    $name = strtolower(trim((string) ($account->name ?? \'\')));
    $code = strtoupper(trim((string) ($account->code ?? \'\')));
    if (in_array($code, $systemHiddenCodes, true)) return true;
    // Do NOT hide "Opening Balance Equity" — it is a real account.
    $patterns = [
        \'balance sheet reconciliation\',
        \'bank reconciliation suspense\',
        \'reconciliation reserve\',
        \'reconciliation suspense\',
    ];
    foreach ($patterns as $p) {
        if (str_contains($name, $p)) return true;
    }
    return false;
};',
    $c, $count1
);

// ── 2. Add vendor credit reclassification AFTER overdraft block ──────────────
$c = str_replace(
'if ($overdraftLines->isNotEmpty()) {
    $currentLiabilityLines = $currentLiabilityLines->concat($overdraftLines);
}

/* ─────────────────────────────────────────────────────────────────
 *  PROCESS EQUITY',
'if ($overdraftLines->isNotEmpty()) {
    $currentLiabilityLines = $currentLiabilityLines->concat($overdraftLines);
}

/* ─────────────────────────────────────────────────────────────────
 *  VENDOR CREDIT RECLASSIFICATION
 *  A payable account with a debit balance (balance < 0 in credit-normal
 *  convention) represents a vendor overpayment or supplier prepayment.
 *  Economically it is an asset, not a liability. Reclassify to Current
 *  Assets to match GAAP presentation.
 * ──────────────────────────────────────────────────────────────── */
$vendorCreditLines = collect();

$currentLiabilityLines = $currentLiabilityLines->filter(function ($account) use (&$vendorCreditLines) {
    $bal  = (float) ($account->balance ?? 0);
    $name = strtolower(trim((string) ($account->name ?? \'\')));
    if ($bal < -0.005 && (str_contains($name, \'payable\') || str_contains($name, \'accounts pay\'))) {
        $vc = (object) (method_exists($account, \'toArray\') ? $account->toArray() : (array) $account);
        $vc->balance        = abs($bal);
        $vc->_vendor_credit = true;
        $vendorCreditLines->push($vc);
        return false;
    }
    return true;
})->values();

if ($vendorCreditLines->isNotEmpty()) {
    $processedCurrentAssets = $processedCurrentAssets->concat($vendorCreditLines);
}

/* ─────────────────────────────────────────────────────────────────
 *  PROCESS EQUITY',
    $c, $count2
);

// ── 3. Capture hiddenEquityBalance ───────────────────────────────────────────
$c = str_replace(
'/* ─────────────────────────────────────────────────────────────────
 *  PROCESS EQUITY
 * ──────────────────────────────────────────────────────────────── */
$visibleEquity    = collect($equity ?? [])->reject(fn ($a) => $isSystemAccount($a))->values();
$displayNetIncome = (float) ($netIncome ?? $retainedEarnings ?? 0);',
'/* ─────────────────────────────────────────────────────────────────
 *  PROCESS EQUITY
 *  Hidden accounts (plugging entries) are excluded from line rendering
 *  but their balances are captured in $hiddenEquityBalance and added to
 *  equity totals so the accounting equation is never broken by filtering.
 * ──────────────────────────────────────────────────────────────── */
$allEquityItems       = collect($equity ?? []);
$visibleEquity        = $allEquityItems->reject(fn ($a) => $isSystemAccount($a))->values();
$hiddenEquityAccounts = $allEquityItems->filter(fn ($a) => $isSystemAccount($a))->values();
$hiddenEquityBalance  = $hiddenEquityAccounts->sum(fn ($a) => (float) ($a->balance ?? 0));
$displayNetIncome     = (float) ($netIncome ?? $retainedEarnings ?? 0);',
    $c, $count3
);

// ── 4. Add hidden equity to visTotalEquity ───────────────────────────────────
$c = str_replace(
'$visTotalEquityAccounts = $visibleEquity->sum(fn ($a) => (float) ($a->balance ?? 0));
$visTotalEquity         = $visTotalEquityAccounts + $displayNetIncome;',
'$visTotalEquityAccounts = $visibleEquity->sum(fn ($a) => (float) ($a->balance ?? 0));
// Include hidden plugging-entry balance so the accounting equation holds.
$visTotalEquity         = $visTotalEquityAccounts + $displayNetIncome + $hiddenEquityBalance;',
    $c, $count4
);

// ── 5. Fix cash-basis equity recalculation ───────────────────────────────────
$c = str_replace(
'    $visTotalEquity        = $visTotalEquityAccounts + $displayNetIncome;
    $visTotalLiabEquity    = $visTotalLiabilities + $visTotalEquity;
    $equationDiff          = round($visTotalAssets - $visTotalLiabEquity, 2);
    $isBalanced            = abs($equationDiff) < 0.01;
}',
'    $visTotalEquity        = $visTotalEquityAccounts + $displayNetIncome + $hiddenEquityBalance;
    $visTotalLiabEquity    = $visTotalLiabilities + $visTotalEquity;
    $equationDiff          = round($visTotalAssets - $visTotalLiabEquity, 2);
    $isBalanced            = abs($equationDiff) < 0.01;
}',
    $c, $count5
);

// ── 6. Fix comparison equity totals ──────────────────────────────────────────
$c = str_replace(
'    $cmpEquityVis = collect($compareData[\'equity\'] ?? [])
        ->reject(fn ($a) => $isSystemAccount($a))
        ->values();
    $cmpDisplayNetIncome = (float) ($compareData[\'netIncome\'] ?? 0);

    $cmpTotalCurrentAssets = $cmpCurrentAssetsVis->sum(fn ($a) => (float) ($a->balance ?? 0));
    $cmpTotalFixedAssets   = $cmpFixedAssetsVis->sum(fn ($a) => (float) ($a->balance ?? 0));
    $cmpTotalAssets        = $cmpTotalCurrentAssets + $cmpTotalFixedAssets;
    $cmpTotalCurrentLiab   = $cmpCurrentLiabVis->sum(fn ($a) => (float) ($a->balance ?? 0));
    $cmpTotalLongTermLiab  = $cmpLongTermLiabVis->sum(fn ($a) => (float) ($a->balance ?? 0));
    $cmpTotalLiabilities   = $cmpTotalCurrentLiab + $cmpTotalLongTermLiab;
    $cmpTotalEquityAcc     = $cmpEquityVis->sum(fn ($a) => (float) ($a->balance ?? 0));
    $cmpTotalEquity        = $cmpTotalEquityAcc + $cmpDisplayNetIncome;
    $cmpTotalLiabEquity    = $cmpTotalLiabilities + $cmpTotalEquity;',
'    $cmpAllEquity           = collect($compareData[\'equity\'] ?? []);
    $cmpEquityVis           = $cmpAllEquity->reject(fn ($a) => $isSystemAccount($a))->values();
    $cmpHiddenEquityBalance = $cmpAllEquity->filter(fn ($a) => $isSystemAccount($a))
                                ->sum(fn ($a) => (float) ($a->balance ?? 0));
    $cmpDisplayNetIncome    = (float) ($compareData[\'netIncome\'] ?? 0);

    $cmpTotalCurrentAssets = $cmpCurrentAssetsVis->sum(fn ($a) => (float) ($a->balance ?? 0));
    $cmpTotalFixedAssets   = $cmpFixedAssetsVis->sum(fn ($a) => (float) ($a->balance ?? 0));
    $cmpTotalAssets        = $cmpTotalCurrentAssets + $cmpTotalFixedAssets;
    $cmpTotalCurrentLiab   = $cmpCurrentLiabVis->sum(fn ($a) => (float) ($a->balance ?? 0));
    $cmpTotalLongTermLiab  = $cmpLongTermLiabVis->sum(fn ($a) => (float) ($a->balance ?? 0));
    $cmpTotalLiabilities   = $cmpTotalCurrentLiab + $cmpTotalLongTermLiab;
    $cmpTotalEquityAcc     = $cmpEquityVis->sum(fn ($a) => (float) ($a->balance ?? 0));
    $cmpTotalEquity        = $cmpTotalEquityAcc + $cmpDisplayNetIncome + $cmpHiddenEquityBalance;
    $cmpTotalLiabEquity    = $cmpTotalLiabilities + $cmpTotalEquity;',
    $c, $count6
);

$counts = [$count1, $count2, $count3, $count4, $count5, $count6];
$allOk  = !in_array(0, $counts, true);

echo "Block 1 (isSystemAccount):       $count1\n";
echo "Block 2 (vendor credits):        $count2\n";
echo "Block 3 (hiddenEquityBalance):   $count3\n";
echo "Block 4 (visTotalEquity):        $count4\n";
echo "Block 5 (cash-basis equity):     $count5\n";
echo "Block 6 (comparison equity):     $count6\n";

if ($allOk) {
    file_put_contents($path, $c);
    echo "\nAll 6 blocks replaced. Lines: " . substr_count($c, "\n") . "\n";
} else {
    echo "\nNot all blocks matched — file NOT written\n";
    exit(1);
}
