@if(Route::has('advance-payments.customers'))
    <li><a href="{{ route('advance-payments.customers') }}">Customer Advances</a></li>
@endif
@if(Route::has('advance-payments.suppliers'))
    <li><a href="{{ route('advance-payments.suppliers') }}">Supplier Advances</a></li>
@endif
