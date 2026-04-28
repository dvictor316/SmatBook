<?php

namespace App\Http\Controllers;

use App\Models\AssetMaintenanceLog;
use App\Models\FixedAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AssetMaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $query     = AssetMaintenanceLog::forCompany($companyId)->with('asset');

        if ($assetId = $request->query('asset_id')) {
            $query->where('fixed_asset_id', $assetId);
        }

        $logs = $query->latest('maintenance_date')->paginate(25);

        $nameColumn = Schema::hasColumn('fixed_assets', 'name') ? 'name' : 'asset_name';
        $assetColumns = ['id', $nameColumn];

        if (Schema::hasColumn('fixed_assets', 'asset_code')) {
            $assetColumns[] = 'asset_code';
        }

        $assets = FixedAsset::where('company_id', $companyId)
            ->orderBy($nameColumn)
            ->get($assetColumns);

        return view('assets.maintenance.index', compact('logs', 'assets'));
    }

    public function create(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $nameColumn = Schema::hasColumn('fixed_assets', 'name') ? 'name' : 'asset_name';
        $assets    = FixedAsset::where('company_id', $companyId)->orderBy($nameColumn)->get();
        $assetId   = $request->query('asset_id');
        return view('assets.maintenance.create', compact('assets', 'assetId'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'fixed_asset_id'      => 'required|exists:fixed_assets,id',
            'maintenance_type'   => 'required|in:preventive,corrective,inspection,upgrade,overhaul',
            'maintenance_date'   => 'required|date',
            'next_maintenance_date' => 'nullable|date|after:maintenance_date',
            'performed_by'       => 'nullable|string|max:255',
            'vendor_name'        => 'nullable|string|max:255',
            'cost'               => 'nullable|numeric|min:0',
            'description'        => 'required|string',
            'findings'           => 'nullable|string',
            'parts_replaced'     => 'nullable|string',
            'status'             => 'required|in:scheduled,in_progress,completed,cancelled',
        ]);

        // Verify asset belongs to company
        $asset = FixedAsset::where('company_id', $companyId)->findOrFail($data['fixed_asset_id']);

        $branch = $this->getActiveBranchContext();
        $data['company_id']  = $companyId;
        $data['branch_id']   = $branch['id'];
        $data['branch_name'] = $branch['name'];
        $data['created_by']  = Auth::id();

        AssetMaintenanceLog::create($data);

        // Update next maintenance date on asset
        if ($data['next_maintenance_date'] ?? null) {
            $asset->update(['maintenance_schedule' => $data['next_maintenance_date']]);
        }

        return redirect()->route('assets.maintenance.index', ['asset_id' => $data['fixed_asset_id']])
            ->with('success', 'Maintenance log created.');
    }

    /**
     * Get the active branch context (id, name) from session.
     *
     * @return array
     */
    private function getActiveBranchContext(): array
    {
        return [
            'id' => session('active_branch_id', Auth::user()->branch_id ?? null),
            'name' => session('active_branch_name', null),
        ];
    }
    public function show(AssetMaintenanceLog $maintenanceLog)
    {
        $this->authorizeAssetMaintenanceAccess($maintenanceLog);
        $maintenanceLog->load('asset');
        return view('assets.maintenance.show', compact('maintenanceLog'));
    }

    public function edit(AssetMaintenanceLog $maintenanceLog)
    {
        $this->authorizeAssetMaintenanceAccess($maintenanceLog);
        $companyId = Auth::user()->company_id;
        $nameColumn = Schema::hasColumn('fixed_assets', 'name') ? 'name' : 'asset_name';
        $assets    = FixedAsset::where('company_id', $companyId)->orderBy($nameColumn)->get();
        return view('assets.maintenance.edit', compact('maintenanceLog', 'assets'));
    }

    public function update(Request $request, AssetMaintenanceLog $maintenanceLog)
    {
        $this->authorizeAssetMaintenanceAccess($maintenanceLog);

        $data = $request->validate([
            'maintenance_date'      => 'required|date',
            'next_maintenance_date' => 'nullable|date|after:maintenance_date',
            'performed_by'          => 'nullable|string|max:255',
            'vendor_name'           => 'nullable|string|max:255',
            'cost'                  => 'nullable|numeric|min:0',
            'description'           => 'required|string',
            'findings'              => 'nullable|string',
            'parts_replaced'        => 'nullable|string',
            'status'                => 'required|in:scheduled,in_progress,completed,cancelled',
        ]);

        $maintenanceLog->update($data);

        return redirect()->route('assets.maintenance.index')
            ->with('success', 'Maintenance log updated.');
    }

    public function destroy(AssetMaintenanceLog $maintenanceLog)
    {
        $this->authorizeAssetMaintenanceAccess($maintenanceLog);
        $maintenanceLog->delete();
        return back()->with('success', 'Maintenance log deleted.');
    }

    private function authorizeAssetMaintenanceAccess(AssetMaintenanceLog $log): void
    {
        abort_unless($log->company_id === Auth::user()->company_id, 403);
    }
}
