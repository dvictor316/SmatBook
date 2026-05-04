<?php
$path = '/mnt/c/Users/victor/Desktop/smat-book/resources/views/Reports/Reports/balance-sheet.blade.php';
$c = file_get_contents($path);
if (!$c) { echo "ERROR: could not read file\n"; exit(1); }

// Apply CSS blocks first (same as fix-bs2.php but only the CSS ones that haven't applied)
// Check if vendor credit CSS is already there:
$alreadyHasCss = strpos($c, '.bs-vendor-credit-tag') !== false;
$alreadyHasDebugPanel = strpos($c, 'bs-hidden-debug') !== false;
echo "Vendor credit CSS already applied: " . ($alreadyHasCss ? 'YES' : 'NO') . "\n";
echo "Debug panel CSS already applied:   " . ($alreadyHasDebugPanel ? 'YES' : 'NO') . "\n";

if (!$alreadyHasCss) {
    // Apply CSS block from fix-bs2.php 
    echo "WARNING: CSS not applied - run fix-bs2.php first\n";
    exit(1);
}

// ── 8. Add vendor credit badge (corrected: 32 spaces before <tr, 36 before <td>) ─
$old8 = '                                <tr class="{{ $caGroups->count() > 1 ? \'bs-line bs-line-indented\' : \'bs-line\' }}">
                                    <td>{{ $account->name }}</td>';

$new8 = '                                <tr class="{{ $caGroups->count() > 1 ? \'bs-line bs-line-indented\' : \'bs-line\' }}">
                                    <td>
                                        {{ $account->name }}
                                        @if(!empty($account->_vendor_credit))
                                            <span class="bs-vendor-credit-tag">Vendor Credit</span>
                                        @endif
                                    </td>';

$c = str_replace($old8, $new8, $c, $count8);

echo "Block 8 (current assets badge):  $count8\n";

if ($count8 === 1) {
    file_put_contents($path, $c);
    echo "Done. Lines: " . substr_count($c, "\n") . "\n";
} else {
    echo "Block 8 not matched — file NOT written\n";
    // Show what it finds near that area
    $pos = strpos($c, 'bs-line bs-line-indented');
    if ($pos !== false) {
        echo "Context around 'bs-line-indented' (in HTML section):\n";
        // Find all occurrences
        $offset = 0;
        while (($pos = strpos($c, 'bs-line bs-line-indented', $offset)) !== false) {
            echo "--- at pos $pos ---\n";
            echo substr($c, max(0, $pos-5), 200) . "\n";
            $offset = $pos + 1;
        }
    }
    exit(1);
}
