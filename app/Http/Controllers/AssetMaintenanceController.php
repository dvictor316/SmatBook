<?php

namespace App\Http\Controllers;

use App\Models\AssetMaintenanceLog;
use App\Models\FixedAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssetMaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $query     = AssetMaintenanceLog::forCompany($companyId)->with('asset');

        if ($assetId = $request->query('asset_id')) {
            $query->where('asset_id', $assetId);
        }

        $logs = $query->latest('maintenance_date')->paginate(25);

        $assets = FixedAsset::where('company_id', $companyId)
            ->orderBy('asset_name')
            ->get(['id', 'asset_name', 'asset_code']);

        return view('assets.maintenance.index', compact('logs', 'assets'));
    }

    public function create(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $assets    = FixedAsset::where('company_id', $companyId)->orderBy('asset_name')->get();
        $assetId   = $request->query('asset_id');
        return view('assets.maintenance.create', compact('assets', 'assetId'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'asset_id'           => 'required|exists:fixed_assets,id',
            'maintenance_type'   => 'required|in:preventive,corrective,inspection,upgrade,overhaul',
            'maintenance_date'   => 'required|date',
            'next_maintenance_date' => 'nullable|date|after:maintenance_date',
            'performed_by'       => 'nullable|string|max:255',
            'vendor'             => 'nullable|string|max:255',
            'cost'               => 'nullable|numeric|min:0',
            'downtime_hours'     => 'nullable|numeric|min:0',
            'description'        => 'required|string',
            'findings'           => 'nullable|string',
            'actions_taken'      => 'nullable|string',
            'status'             => 'required|in:scheduled,in_progress,completed,cancelled',
        ]);

        // Verify asset belongs to company
        $asset = FixedAsset::where('company_id', $companyId)->findOrFail($data['asset_id']);

        $data['company_id']  = $companyId;
        $data['branch_id']   = Auth::user()->branch_id;
        $data['created_by']  = Auth::id();

        AssetMaintenanceLog::create($data);

        // Update next maintenance date on asset
        if ($data['next_maintenance_date'] ?? null) {
            $asset->update(['maintenance_schedule' => $data['next_maintenance_date']]);
        }

        return redirect()->route('assets.maintenance.index', ['asset_id' => $data['asset_id']])
            ->with('success', 'Maintenance log created.');
    }

    public function show(AssetMaintenanceLog $maintenanceLog)
    {
        $this->authorize($maintenanceLog);
        $maintenanceLog->load('asset');
        return view('assets.maintenance.show', compact('maintenanceLog'));
    }

    public function edit(AssetMaintenanceLog $maintenanceLog)
    {
        $this->authorize($maintenanceLog);
        $companyId = Auth::user()->company_id;
        $assets    = FixedAsset::where('company_id', $companyId)->orderBy('asset_name')->get();
        return view('assets.maintenance.edit', compact('maintenanceLog', 'assets'));
    }

    public function update(Request $request, AssetMaintenanceLog $maintenanceLog)
    {
        $this->authorize($maintenanceLog);

        $data = $request->validate([
            'maintenance_date'      => 'required|date',
            'next_maintenance_date' => 'nullable|date|after:maintenance_date',
            'performed_by'          => 'nullable|string|max:255',
            'vendor'                => 'nullable|string|max:255',
            'cost'                  => 'nullable|numeric|min:0',
            'downtime_hours'        => 'nullable|numeric|min:0',
            'description'           => 'required|string',
            'findings'              => 'nullable|string',
            'actions_taken'         => 'nullable|string',
            'status'                => 'required|in:scheduled,in_progress,completed,cancelled',
        ]);

        $maintenanceLog->update($data);

        return redirect()->route('assets.maintenance.index')
            ->with('success', 'Maintenance log updated.');
    }

    public function destroy(AssetMaintenanceLog $maintenanceLog)
    {
        $this->authorize($maintenanceLog);
        $maintenanceLog->delete();
        return back()->with('success', 'Maintenance log deleted.');
    }

    private function authorize(AssetMaintenanceLog $log): void
    {
        abort_unless($log->company_id === Auth::user()->company_id, 403);
    }
}
