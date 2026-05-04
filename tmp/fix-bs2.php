<?php
$path = '/mnt/c/Users/victor/Desktop/smat-book/resources/views/Reports/Reports/balance-sheet.blade.php';
$c = file_get_contents($path);
if (!$c) { echo "ERROR: could not read file\n"; exit(1); }

// ── 7. Add vendor credit CSS after overdraft tag CSS ────────────────────────
$c = str_replace(
'.bs-overdraft-tag {
    display: inline-block;
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
    border-radius: 4px;
    font-size: 0.66rem;
    font-weight: 700;
    padding: 1px 5px;
    vertical-align: middle;
    margin-left: 5px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}',
'.bs-overdraft-tag {
    display: inline-block;
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
    border-radius: 4px;
    font-size: 0.66rem;
    font-weight: 700;
    padding: 1px 5px;
    vertical-align: middle;
    margin-left: 5px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
/* Vendor credit badge (AP reclassified to current assets) */
.bs-vendor-credit-tag {
    display: inline-block;
    background: #f0f9ff;
    color: #0369a1;
    border: 1px solid #bae6fd;
    border-radius: 4px;
    font-size: 0.66rem;
    font-weight: 700;
    padding: 1px 5px;
    vertical-align: middle;
    margin-left: 5px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
/* Hidden-account debug panel */
.bs-hidden-debug {
    margin-top: 12px;
    padding: 12px 16px;
    background: #fefce8;
    border: 1px solid #fde68a;
    border-radius: 8px;
    font-size: 0.79rem;
    color: #713f12;
}
.bs-hidden-debug summary {
    cursor: pointer;
    font-weight: 700;
    color: #92400e;
}
.bs-hidden-debug table { width: 100%; margin-top: 8px; border-collapse: collapse; }
.bs-hidden-debug td { padding: 3px 6px; border-bottom: 1px solid #fde68a; }
.bs-hidden-debug td:last-child { text-align: right; font-variant-numeric: tabular-nums; }',
    $c, $count7
);

// ── 8. Add vendor credit badge in current assets name cell ───────────────────
$c = str_replace(
'                                 <tr class="{{ $caGroups->count() > 1 ? \'bs-line bs-line-indented\' : \'bs-line\' }}">
                                    <td>{{ $account->name }}</td>',
'                                 <tr class="{{ $caGroups->count() > 1 ? \'bs-line bs-line-indented\' : \'bs-line\' }}">
                                    <td>
                                        {{ $account->name }}
                                        @if(!empty($account->_vendor_credit))
                                            <span class="bs-vendor-credit-tag">Vendor Credit</span>
                                        @endif
                                    </td>',
    $c, $count8
);

// ── 9. Add debug panel after the imbalanced-entries @endif ───────────────────
$c = str_replace(
'            @endif

        </div>{{-- /.bs-sheet --}}
    </div>{{-- /.bs-page --}}',
'            @endif

            {{-- Hidden-equity debug panel (only visible at ?debug=1) --}}
            @if(request()->boolean(\'debug\') && $hiddenEquityAccounts->isNotEmpty())
                <details class="bs-hidden-debug no-print">
                    <summary>
                        Dev: {{ $hiddenEquityAccounts->count() }} hidden equity account(s) — balance excluded from display but included in equity total
                    </summary>
                    <table>
                        <thead>
                            <tr style="background:#fef9c3;font-weight:700;">
                                <td>Code</td><td>Name</td><td>Balance</td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hiddenEquityAccounts as $ha)
                                <tr>
                                    <td>{{ $ha->code ?? \'—\' }}</td>
                                    <td>{{ $ha->name ?? \'—\' }}</td>
                                    <td>{{ $fmt((float)($ha->balance ?? 0)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p style="margin-top:8px;">
                        Hidden equity balance (added to Total Equity silently):
                        <strong>{{ $fmt($hiddenEquityBalance) }}</strong>
                    </p>
                </details>
            @endif

        </div>{{-- /.bs-sheet --}}
    </div>{{-- /.bs-page --}}',
    $c, $count9
);

echo "Block 7 (vendor credit CSS):     $count7\n";
echo "Block 8 (current assets badge):  $count8\n";
echo "Block 9 (debug panel HTML):      $count9\n";

$counts = [$count7, $count8, $count9];
$allOk  = !in_array(0, $counts, true);

if ($allOk) {
    file_put_contents($path, $c);
    echo "\nAll 3 blocks replaced. Lines: " . substr_count($c, "\n") . "\n";
} else {
    echo "\nNot all blocks matched — file NOT written\n";
    exit(1);
}
