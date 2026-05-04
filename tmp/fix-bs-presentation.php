<?php
/**
 * Balance-sheet presentation refinements:
 * 1. Vendor credit: set _display_name = 'Supplier Advance'
 * 2. Equity: filter out zero-balance Opening Balance Equity accounts
 * 3. Current Assets: suppress group-head rows that duplicate the section header
 * 4. Fixed Assets: suppress group-head rows that duplicate the section header
 * 5. Rename "Retained Earnings (Net Income)" → "Current Year Earnings"
 */

$file = __DIR__ . '/../resources/views/Reports/Reports/balance-sheet.blade.php';
$content = file_get_contents($file);

if ($content === false) {
    die("ERROR: Cannot read blade file\n");
}

$backup = __DIR__ . '/balance-sheet.blade.php.bak';
file_put_contents($backup, $content);
echo "Backup saved to $backup\n";

// ── Change 1: Vendor credit _display_name ────────────────────────────────────
$old1 = '        $vc->balance        = abs($bal);
        $vc->_vendor_credit = true;
        $vendorCreditLines->push($vc);';

$new1 = '        $vc->balance        = abs($bal);
        $vc->_vendor_credit = true;
        $vc->_display_name  = \'Supplier Advance\';
        $vendorCreditLines->push($vc);';

$count1 = 0;
$content = str_replace($old1, $new1, $content, $count1);
echo "Change 1 (vendor credit _display_name): $count1 replacement(s)\n";

// ── Change 2: OBE zero-balance filter ────────────────────────────────────────
$old2 = '$visibleEquity        = $allEquityItems->reject(fn ($a) => $isSystemAccount($a))->values();';

$new2 = '$visibleEquity        = $allEquityItems->reject(fn ($a) => $isSystemAccount($a))
    ->reject(function ($a) {
        // Hide Opening Balance Equity when balance is zero — adds no information
        $isObe = str_contains(strtolower(trim((string) ($a->name ?? \'\'))), \'opening balance equity\');
        return $isObe && abs((float) ($a->balance ?? 0)) < 0.01;
    })->values();';

$count2 = 0;
$content = str_replace($old2, $new2, $content, $count2);
echo "Change 2 (OBE zero-balance filter): $count2 replacement(s)\n";

// ── Change 3a: Current Assets – loop with group-head suppression ──────────────
$old3a = '                        @foreach($caGroups as $group)
                            @if($caGroups->count() > 1)
                                <tr class="bs-group-head">
                                    <td>{{ $group[\'label\'] }}</td>
                                    <td></td>
                                    @if($hasCmp)<td></td>@endif
                                </tr>
                            @endif
                            @foreach($group[\'items\'] as $account)
                                @php $cv = $cmpAmt($account); @endphp
                                <tr class="{{ $caGroups->count() > 1 ? \'bs-line bs-line-indented\' : \'bs-line\' }}">
                                    <td>
                                        {{ $account->name }}
                                        @if(!empty($account->_vendor_credit))
                                            <span class="bs-vendor-credit-tag">Vendor Credit</span>
                                        @endif
                                    </td>';

$new3a = '                        @foreach($caGroups as $group)
                            @php
                            $trivialCaLabels = [\'current assets\', \'current asset\', \'other current assets\', \'current\', \'assets\', \'asset\'];
                            $showCaGroupHead = $caGroups->count() > 1 && !in_array(strtolower(trim($group[\'label\'])), $trivialCaLabels, true);
                            @endphp
                            @if($showCaGroupHead)
                                <tr class="bs-group-head">
                                    <td>{{ $group[\'label\'] }}</td>
                                    <td></td>
                                    @if($hasCmp)<td></td>@endif
                                </tr>
                            @endif
                            @foreach($group[\'items\'] as $account)
                                @php $cv = $cmpAmt($account); @endphp
                                <tr class="{{ $showCaGroupHead ? \'bs-line bs-line-indented\' : \'bs-line\' }}">
                                    <td>
                                        {{ !empty($account->_vendor_credit) ? ($account->_display_name ?? \'Supplier Advance\') : $account->name }}
                                    </td>';

$count3a = 0;
$content = str_replace($old3a, $new3a, $content, $count3a);
echo "Change 3a (current assets group-head + vendor name): $count3a replacement(s)\n";

// ── Change 3b: Current Assets – group sub-total condition ─────────────────────
$old3b = '                            @if($caGroups->count() > 1)
                                <tr class="bs-sub-total">
                                    <td>Total {{ $group[\'label\'] }}</td>
                                    <td class="bs-amt">{{ $fmt($group[\'total\']) }}</td>
                                    @if($hasCmp)<td></td>@endif
                                </tr>
                            @endif
                        @endforeach
                        <tr class="bs-sub-total">
                            <td>Total Current Assets</td>';

$new3b = '                            @if($showCaGroupHead)
                                <tr class="bs-sub-total">
                                    <td>Total {{ $group[\'label\'] }}</td>
                                    <td class="bs-amt">{{ $fmt($group[\'total\']) }}</td>
                                    @if($hasCmp)<td></td>@endif
                                </tr>
                            @endif
                        @endforeach
                        <tr class="bs-sub-total">
                            <td>Total Current Assets</td>';

$count3b = 0;
$content = str_replace($old3b, $new3b, $content, $count3b);
echo "Change 3b (current assets sub-total condition): $count3b replacement(s)\n";

// ── Change 4a: Fixed Assets – loop with group-head suppression ────────────────
$old4a = '                        @foreach($faGroups as $group)
                            @if($faGroups->count() > 1)
                                <tr class="bs-group-head">
                                    <td>{{ $group[\'label\'] }}</td>
                                    <td></td>
                                    @if($hasCmp)<td></td>@endif
                                </tr>
                            @endif
                            @foreach($group[\'items\'] as $account)
                                @php $cv = $cmpAmt($account); @endphp
                                <tr class="{{ $faGroups->count() > 1 ? \'bs-line bs-line-indented\' : \'bs-line\' }}">';

$new4a = '                        @foreach($faGroups as $group)
                            @php
                            $trivialFaLabels = [\'fixed assets\', \'fixed asset\', \'non-current assets\', \'non-current asset\', \'property plant and equipment\', \'ppe\'];
                            $showFaGroupHead = $faGroups->count() > 1 && !in_array(strtolower(trim($group[\'label\'])), $trivialFaLabels, true);
                            @endphp
                            @if($showFaGroupHead)
                                <tr class="bs-group-head">
                                    <td>{{ $group[\'label\'] }}</td>
                                    <td></td>
                                    @if($hasCmp)<td></td>@endif
                                </tr>
                            @endif
                            @foreach($group[\'items\'] as $account)
                                @php $cv = $cmpAmt($account); @endphp
                                <tr class="{{ $showFaGroupHead ? \'bs-line bs-line-indented\' : \'bs-line\' }}">';

$count4a = 0;
$content = str_replace($old4a, $new4a, $content, $count4a);
echo "Change 4a (fixed assets group-head): $count4a replacement(s)\n";

// ── Change 4b: Fixed Assets – group sub-total condition ──────────────────────
$old4b = '                            @if($faGroups->count() > 1)
                                <tr class="bs-sub-total">
                                    <td>Total {{ $group[\'label\'] }}</td>
                                    <td class="bs-amt">{{ $fmt($group[\'total\']) }}</td>
                                    @if($hasCmp)<td></td>@endif
                                </tr>
                            @endif
                        @endforeach
                        <tr class="bs-sub-total">
                            <td>Total Fixed Assets</td>';

$new4b = '                            @if($showFaGroupHead)
                                <tr class="bs-sub-total">
                                    <td>Total {{ $group[\'label\'] }}</td>
                                    <td class="bs-amt">{{ $fmt($group[\'total\']) }}</td>
                                    @if($hasCmp)<td></td>@endif
                                </tr>
                            @endif
                        @endforeach
                        <tr class="bs-sub-total">
                            <td>Total Fixed Assets</td>';

$count4b = 0;
$content = str_replace($old4b, $new4b, $content, $count4b);
echo "Change 4b (fixed assets sub-total condition): $count4b replacement(s)\n";

// ── Change 5: Rename earnings label ──────────────────────────────────────────
$old5 = '                    <tr class="bs-line">
                        <td>Retained Earnings (Net Income)</td>';

$new5 = '                    <tr class="bs-line">
                        <td>Current Year Earnings</td>';

$count5 = 0;
$content = str_replace($old5, $new5, $content, $count5);
echo "Change 5 (rename earnings label): $count5 replacement(s)\n";

// ── Write result ─────────────────────────────────────────────────────────────
$total = $count1 + $count2 + $count3a + $count3b + $count4a + $count4b + $count5;
if ($total < 7) {
    echo "\nWARNING: Only $total/7 replacements succeeded. Check missed ones above.\n";
} else {
    echo "\nAll 7 replacements applied.\n";
}

file_put_contents($file, $content);
echo "File written. Lines: " . substr_count($content, "\n") . "\n";
