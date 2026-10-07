<li class="{{ request()->routeIs('livestock.dashboard') ? 'active' : '' }}"><a href="{{ route('livestock.dashboard') }}"><i class="fe fe-activity"></i><span>Layer Farm Centre</span></a></li>
@if(Route::has('finance.fixed-assets.index'))<li><a href="{{ route('finance.fixed-assets.index') }}"><i class="fe fe-trending-down"></i><span>Asset Depreciation</span></a></li>@endif
@if(Route::has('expenses.index'))<li><a href="{{ route('expenses.index') }}"><i class="fe fe-file-text"></i><span>Farm Expense Ledger</span></a></li>@endif
@if(Route::has('reports.profit-loss'))<li><a href="{{ route('reports.profit-loss', ['module'=>'livestock']) }}"><i class="fe fe-bar-chart-2"></i><span>Profit & Loss</span></a></li>@endif
