<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\FinancialDataReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class FinancialResetController extends Controller
{
    /**
     * Show the financial reset confirmation page.
     */
    public function index()
    {
        $this->authoriseSuperAdmin();

        $companyId = $this->activeCompanyId();
        $preview = app(FinancialDataReset::class)->preview($companyId);

        $grandTotal = array_sum($preview);

        return view('Finance.reset', compact('preview', 'grandTotal'));
    }

    /**
     * Execute the financial reset after double confirmation.
     */
    public function execute(Request $request)
    {
        $this->authoriseSuperAdmin();

        $request->validate([
            'confirmation_phrase' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Gate 1 – exact phrase
        if ($request->confirmation_phrase !== 'RESET FINANCIAL DATA') {
            return response()->json([
                'success' => false,
                'message' => 'Confirmation phrase does not match. Type exactly: RESET FINANCIAL DATA',
            ], 422);
        }

        // Gate 2 – re-enter password
        if (! Hash::check($request->password, Auth::user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Your current password is incorrect.',
            ], 422);
        }

        $performer = Auth::user();
        $companyId = $this->activeCompanyId();
        $deletedLog = [];
        $errors = [];

        try {
            $deletedLog = app(FinancialDataReset::class)->execute($companyId);

            $totalRows = array_sum(array_filter($deletedLog, 'is_int'));

            // ── Step 4: Log the action ──
            try {
                ActivityLog::record(
                    'FinancialReset',
                    'reset',
                    "Super-admin executed full financial reset for company #{$companyId}. {$totalRows} total rows removed/zeroed.",
                    [
                        'performed_by' => $performer->id,
                        'performer_name' => $performer->name ?? $performer->email,
                        'company_id' => $companyId,
                        'executed_at' => now()->toDateTimeString(),
                        'deleted_counts' => $deletedLog,
                        'errors' => $errors,
                    ]
                );
            } catch (\Throwable $logEx) {
                Log::warning('FinancialReset: could not write ActivityLog — '.$logEx->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Financial reset completed successfully.',
                'log' => $deletedLog,
                'total_rows' => $totalRows,
                'errors' => $errors,
            ]);

        } catch (\Throwable $e) {

            Log::error('FinancialReset: rolled back — '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Reset failed and was rolled back: '.$e->getMessage(),
            ], 500);
        }
    }

    // ──────────────────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────────────────

    private function activeCompanyId(): int
    {
        $companyId = (int) (session('current_tenant_id') ?? Auth::user()?->company_id ?? 0);
        abort_unless($companyId > 0 && DB::table('companies')->where('id', $companyId)->exists(), 422,
            'Select a valid business before resetting financial data.');

        return $companyId;
    }

    private function authoriseSuperAdmin(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->isSuperAdmin()) {
            abort(403, 'Super-admin access required.');
        }
    }
}
