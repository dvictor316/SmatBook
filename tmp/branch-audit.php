<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$cols = Schema::getColumnListing('transactions');
echo "Transactions columns:\n";
echo implode(', ', $cols) . "\n\n";

echo "branch_id:   " . (in_array('branch_id',   $cols) ? 'YES' : 'NO') . "\n";
echo "branch_name: " . (in_array('branch_name', $cols) ? 'YES' : 'NO') . "\n\n";

$total = DB::table('transactions')->count();
$withBranchId = DB::table('transactions')->whereNotNull('branch_id')->where('branch_id','!=','')->count();
echo "Total transactions:          $total\n";
echo "With branch_id set:          $withBranchId\n";
echo "Without branch_id (NULL/''):  " . ($total - $withBranchId) . "\n\n";

// Show distinct branch_ids stored
$branchIds = DB::table('transactions')->whereNotNull('branch_id')->where('branch_id','!=','')
    ->selectRaw('branch_id, COUNT(*) as cnt')->groupBy('branch_id')->get();
echo "Distinct branch_ids in transactions:\n";
foreach ($branchIds as $row) {
    echo "  [{$row->branch_id}] => {$row->cnt} rows\n";
}

// Also check accounts
$aCols = Schema::getColumnListing('accounts');
echo "\nAccounts columns: " . implode(', ', $aCols) . "\n";
$aTotal = DB::table('accounts')->count();
$aWithBranch = DB::table('accounts')->whereNotNull('branch_id')->where('branch_id','!=','')->count();
echo "Total accounts:       $aTotal\n";
echo "With branch_id set:   $aWithBranch\n";
$aDistinct = DB::table('accounts')->whereNotNull('branch_id')->where('branch_id','!=','')
    ->selectRaw('branch_id, COUNT(*) as cnt')->groupBy('branch_id')->get();
echo "Distinct branch_ids in accounts:\n";
foreach ($aDistinct as $row) {
    echo "  [{$row->branch_id}] => {$row->cnt} rows\n";
}

// Check customers and suppliers branch_id
foreach (['customers','suppliers','products'] as $tbl) {
    if (!Schema::hasTable($tbl)) continue;
    $c = Schema::getColumnListing($tbl);
    $hasBranch = in_array('branch_id',$c);
    echo "\n$tbl has branch_id: " . ($hasBranch ? 'YES' : 'NO') . "\n";
    if ($hasBranch) {
        $cnt = DB::table($tbl)->whereNotNull('branch_id')->where('branch_id','!=','')->count();
        echo "  With branch_id set: $cnt / " . DB::table($tbl)->count() . "\n";
    }
}
