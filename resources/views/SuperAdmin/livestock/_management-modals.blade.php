@php
    $activeFlocks = $management['flocks']->where('status', 'active');
    $farmOptions = $management['farms'];
    $capitalCategories = \App\Http\Controllers\Livestock\LivestockDashboardController::CAPITAL_CATEGORIES;
    $workingCategories = \App\Http\Controllers\Livestock\LivestockDashboardController::WORKING_CATEGORIES;
    $opexCategories = \App\Http\Controllers\Livestock\LivestockDashboardController::OPEX_CATEGORIES;
    $revenueSources = \App\Http\Controllers\Livestock\LivestockDashboardController::REVENUE_SOURCES;
@endphp

<div class="modal fade" id="addFarmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><form method="POST" action="{{ route('super_admin.livestock.farms.store') }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Create Layer Farm</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><input type="hidden" name="company_id" value="{{ $selectedCompanyId }}"><div class="row g-3">
            <div class="col-md-6"><label class="form-label">Farm name</label><input name="name" class="form-control" required maxlength="191"></div>
            <div class="col-md-6"><label class="form-label">Farm code</label><input name="code" class="form-control" required maxlength="40"></div>
            <div class="col-md-4"><label class="form-label">Bird capacity</label><input name="bird_capacity" type="number" min="1" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Eggs per crate</label><input name="eggs_per_crate" type="number" min="1" max="100" value="30" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Location</label><input name="location" class="form-control" maxlength="255"></div>
        </div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create Farm</button></div>
    </form></div>
</div>

<div class="modal fade" id="addFlockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><form method="POST" action="{{ route('super_admin.livestock.flocks.store') }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Open Flock Cycle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div class="row g-3">
            <div class="col-md-6"><label class="form-label">Farm</label><select name="farm_id" class="form-select" required>@foreach($farmOptions as $farm)<option value="{{ $farm->id }}">{{ $farm->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Batch code</label><input name="batch_code" class="form-control" required maxlength="60"></div>
            <div class="col-md-4"><label class="form-label">Breed</label><input name="breed" class="form-control" placeholder="Isa Brown"></div>
            <div class="col-md-4"><label class="form-label">Placement date</label><input name="placement_date" type="date" value="{{ now()->toDateString() }}" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Age at placement (weeks)</label><input name="age_at_placement_weeks" type="number" min="0" value="16" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Opening birds</label><input name="opening_birds" type="number" min="1" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Cost per bird</label><input name="cost_per_bird" type="number" min="0" step="0.01" value="0" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Supplier</label><input name="supplier" class="form-control"></div>
            <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
        </div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success">Open Flock</button></div>
    </form></div>
</div>

<div class="modal fade" id="addInvestmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><form method="POST" action="{{ route('super_admin.livestock.investments.store') }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Record Capital or Working Investment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div class="row g-3">
            <div class="col-md-6"><label class="form-label">Farm</label><select name="farm_id" class="form-select" required>@foreach($farmOptions as $farm)<option value="{{ $farm->id }}">{{ $farm->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Flock (optional)</label><select name="flock_id" class="form-select"><option value="">General farm investment</option>@foreach($management['flocks'] as $flock)<option value="{{ $flock->id }}">{{ $flock->batch_code }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Cost class</label><select name="cost_class" id="saInvestmentClass" class="form-select" required><option value="capital">Capital expenditure</option><option value="working_capital">Working capital</option></select></div>
            <div class="col-md-6"><label class="form-label">Category</label><select name="category" id="saInvestmentCategory" class="form-select" required>@foreach($capitalCategories as $category)<option value="{{ $category }}" data-class="capital">{{ \Illuminate\Support\Str::headline($category) }}</option>@endforeach @foreach($workingCategories as $category)<option value="{{ $category }}" data-class="working_capital">{{ \Illuminate\Support\Str::headline($category) }}</option>@endforeach</select></div>
            <div class="col-md-8"><label class="form-label">Description</label><input name="description" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Cost date</label><input name="cost_date" type="date" value="{{ now()->toDateString() }}" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Cost</label><input name="cost" type="number" min="0.01" step="0.01" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Salvage value</label><input name="salvage_value" type="number" min="0" step="0.01" value="0" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Useful life (months)</label><input name="useful_life_months" type="number" min="1" max="1200" value="60" class="form-control" required></div>
        </div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-warning">Record Investment</button></div>
    </form></div>
</div>

<div class="modal fade" id="addOpexModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><form method="POST" action="{{ route('super_admin.livestock.opex.store') }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Record Operating Expense</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div class="row g-3">
            <div class="col-md-6"><label class="form-label">Farm</label><select name="farm_id" class="form-select" required>@foreach($farmOptions as $farm)<option value="{{ $farm->id }}">{{ $farm->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Flock (optional)</label><select name="flock_id" class="form-select"><option value="">General farm cost</option>@foreach($management['flocks'] as $flock)<option value="{{ $flock->id }}">{{ $flock->batch_code }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Date</label><input name="expense_date" type="date" value="{{ now()->toDateString() }}" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Category</label><select name="category" class="form-select" required>@foreach($opexCategories as $category)<option value="{{ $category }}">{{ \Illuminate\Support\Str::headline($category) }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Frequency</label><select name="frequency" class="form-select" required><option>daily</option><option>weekly</option><option>monthly</option><option>occasional</option></select></div>
            <div class="col-md-6"><label class="form-label">Description</label><input name="description" class="form-control"></div><div class="col-md-6"><label class="form-label">Vendor</label><input name="vendor" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Quantity</label><input name="quantity" type="number" min="0.001" step="0.001" value="1" class="form-control" required></div><div class="col-md-3"><label class="form-label">Unit</label><input name="unit" class="form-control" placeholder="bag, dose, month"></div><div class="col-md-3"><label class="form-label">Unit cost</label><input name="unit_cost" type="number" min="0" step="0.01" class="form-control" required></div><div class="col-md-3"><label class="form-label">Amount override</label><input name="amount" type="number" min="0.01" step="0.01" class="form-control"></div>
        </div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Record OPEX</button></div>
    </form></div>
</div>

<div class="modal fade" id="addRevenueModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><form method="POST" action="{{ route('super_admin.livestock.revenue.store') }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Record Farm Revenue</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div class="row g-3">
            <div class="col-md-6"><label class="form-label">Farm</label><select name="farm_id" class="form-select" required>@foreach($farmOptions as $farm)<option value="{{ $farm->id }}">{{ $farm->name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Flock (optional)</label><select name="flock_id" class="form-select"><option value="">General farm revenue</option>@foreach($management['flocks'] as $flock)<option value="{{ $flock->id }}">{{ $flock->batch_code }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Date</label><input name="revenue_date" type="date" value="{{ now()->toDateString() }}" class="form-control" required></div><div class="col-md-4"><label class="form-label">Source</label><select name="source" class="form-select" required>@foreach($revenueSources as $source)<option value="{{ $source }}">{{ \Illuminate\Support\Str::headline($source) }}</option>@endforeach</select></div><div class="col-md-4"><label class="form-label">Customer</label><input name="customer" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Description</label><input name="description" class="form-control"></div><div class="col-md-6"><label class="form-label">Reference</label><input name="reference" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Quantity</label><input name="quantity" type="number" min="0.001" step="0.001" value="1" class="form-control" required></div><div class="col-md-3"><label class="form-label">Unit</label><input name="unit" class="form-control" placeholder="crate, bag, bird"></div><div class="col-md-3"><label class="form-label">Unit price</label><input name="unit_price" type="number" min="0" step="0.01" class="form-control" required></div><div class="col-md-3"><label class="form-label">Amount override</label><input name="amount" type="number" min="0.01" step="0.01" class="form-control"></div>
        </div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-info text-white">Record Revenue</button></div>
    </form></div>
</div>

<div class="modal fade" id="addProductionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered"><form method="POST" action="{{ route('super_admin.livestock.production.store') }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Record Daily Production</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div class="row g-3">
            <div class="col-md-4"><label class="form-label">Farm</label><select name="farm_id" class="form-select" required>@foreach($farmOptions as $farm)<option value="{{ $farm->id }}">{{ $farm->name }}</option>@endforeach</select></div><div class="col-md-4"><label class="form-label">Active flock</label><select name="flock_id" class="form-select" required>@foreach($activeFlocks as $flock)<option value="{{ $flock->id }}">{{ $flock->batch_code }} ({{ number_format($flock->current_birds) }} birds)</option>@endforeach</select></div><div class="col-md-4"><label class="form-label">Production date</label><input name="production_date" type="date" value="{{ now()->toDateString() }}" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Opening birds</label><input name="opening_birds" type="number" min="0" class="form-control" required></div><div class="col-md-3"><label class="form-label">Mortality</label><input name="mortality" type="number" min="0" value="0" class="form-control"></div><div class="col-md-3"><label class="form-label">Culled</label><input name="culled" type="number" min="0" value="0" class="form-control"></div><div class="col-md-3"><label class="form-label">Damaged eggs</label><input name="damaged_eggs" type="number" min="0" value="0" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Egg crates</label><input name="egg_crates" type="number" min="0" value="0" class="form-control"></div><div class="col-md-3"><label class="form-label">Loose eggs</label><input name="loose_eggs" type="number" min="0" value="0" class="form-control"></div><div class="col-md-3"><label class="form-label">Feed used (kg)</label><input name="feed_kg" type="number" min="0" step="0.001" value="0" class="form-control"></div><div class="col-md-3"><label class="form-label">Water (litres)</label><input name="water_litres" type="number" min="0" step="0.001" value="0" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Medication</label><input name="medication" class="form-control"></div><div class="col-md-6"><label class="form-label">Vaccination</label><input name="vaccination" class="form-control"></div>
        </div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-dark">Record Production</button></div>
    </form></div>
</div>

<div class="modal fade" id="addInventoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><form method="POST" action="{{ route('super_admin.livestock.inventory.store') }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Record Feed or Egg Inventory</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div class="row g-3">
            <div class="col-md-6"><label class="form-label">Farm</label><select name="farm_id" class="form-select" required>@foreach($farmOptions as $farm)<option value="{{ $farm->id }}">{{ $farm->name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Flock (optional)</label><select name="flock_id" class="form-select"><option value="">General inventory</option>@foreach($management['flocks'] as $flock)<option value="{{ $flock->id }}">{{ $flock->batch_code }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Date</label><input name="movement_date" type="date" value="{{ now()->toDateString() }}" class="form-control" required></div><div class="col-md-4"><label class="form-label">Item</label><select name="item_type" class="form-select" required><option value="feed">Feed (kg)</option><option value="eggs">Eggs (units)</option></select></div><div class="col-md-4"><label class="form-label">Movement</label><select name="movement_type" class="form-select" required><option value="opening">Opening balance</option><option value="receipt">Receipt</option><option value="adjustment_in">Adjustment in</option><option value="adjustment_out">Adjustment out</option><option value="wastage">Wastage</option></select></div>
            <div class="col-md-4"><label class="form-label">Quantity</label><input name="quantity" type="number" min="0.001" step="0.001" class="form-control" required></div><div class="col-md-4"><label class="form-label">Unit cost</label><input name="unit_cost" type="number" min="0" step="0.0001" value="0" class="form-control"></div><div class="col-md-4"><label class="form-label">Reference</label><input name="reference" class="form-control"></div>
            <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
        </div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-secondary">Record Movement</button></div>
    </form></div>
</div>

@foreach($farmOptions as $farm)
<div class="modal fade" id="editFarm{{ $farm->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><form method="POST" action="{{ route('super_admin.livestock.farms.update', $farm->id) }}" class="modal-content">@csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">Edit {{ $farm->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div class="row g-3"><div class="col-md-6"><label class="form-label">Farm name</label><input name="name" value="{{ $farm->name }}" class="form-control" required></div><div class="col-md-6"><label class="form-label">Code</label><input name="code" value="{{ $farm->code }}" class="form-control" required></div><div class="col-md-4"><label class="form-label">Bird capacity</label><input name="bird_capacity" type="number" min="1" value="{{ $farm->bird_capacity }}" class="form-control" required></div><div class="col-md-4"><label class="form-label">Eggs per crate</label><input name="eggs_per_crate" type="number" min="1" max="100" value="{{ $farm->eggs_per_crate }}" class="form-control" required></div><div class="col-md-4"><label class="form-label">Location</label><input name="location" value="{{ $farm->location }}" class="form-control"></div><div class="col-12"><div class="form-check"><input type="hidden" name="is_active" value="0"><input name="is_active" value="1" type="checkbox" class="form-check-input" id="farmActive{{ $farm->id }}" @checked($farm->is_active)><label class="form-check-label" for="farmActive{{ $farm->id }}">Farm is active</label></div></div></div></div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save Farm</button></div>
    </form></div>
</div>
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function () {
    const costClass = document.getElementById('saInvestmentClass');
    const category = document.getElementById('saInvestmentCategory');
    const syncCategories = () => {
        if (!costClass || !category) return;
        let first = null;
        Array.from(category.options).forEach((option) => {
            option.hidden = option.dataset.class !== costClass.value;
            option.disabled = option.hidden;
            if (!option.hidden && !first) first = option;
        });
        if (category.selectedOptions[0]?.disabled && first) first.selected = true;
    };
    costClass?.addEventListener('change', syncCategories);
    syncCategories();
});
</script>
