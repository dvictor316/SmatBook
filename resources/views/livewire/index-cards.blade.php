<div class="row">

    <div class="col-xl-3 col-sm-6 col-12">
        <div class="card">
            <div class="card-body">
                <div class="dash-widget-header">
                    <span class="dash-widget-icon bg-primary">
                        <i class="fas fa-building"></i>
                    </span>
                    <div class="dash-count">
                        <div class="dash-title">Total Companies</div>
                        <div class="dash-counts">
                            <p>{{ $totalCompanies }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6 col-12">
        <div class="card">
            <div class="card-body">
                <div class="dash-widget-header">
                    <span class="dash-widget-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </span>
                    <div class="dash-count">
                        <div class="dash-title">Active Companies</div>
                        <div class="dash-counts">
                            <p>{{ $activeCompanies }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6 col-12">
        <div class="card">
            <div class="card-body">
                <div class="dash-widget-header">
                    <span class="dash-widget-icon bg-warning">
                        <i class="fas fa-times-circle"></i>
                    </span>
                    <div class="dash-count">
                        <div class="dash-title">Inactive</div>
                        <div class="dash-counts">
                            <p>{{ $inactiveCompanies }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6 col-12">
        <div class="card">
            <div class="card-body">
                <div class="dash-widget-header">
                    <span class="dash-widget-icon bg-info">
                        <i class="fas fa-user-plus"></i>
                    </span>
                    <div class="dash-count">
                        <div class="dash-title">New Today</div>
                        <div class="dash-counts">
                            <p>{{ $newCompaniesToday }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
