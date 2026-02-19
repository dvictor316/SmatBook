{{-- FIX: This single wrapping div is CRITICAL for Livewire to work and prevents the error you are seeing --}}
<div>

    <div class="col-md-6 col-sm-6">
        {{-- Your original logic to change card styling based on route (optional, but kept here) --}}
        @if (Route::is(['index']))
            <div class="card mb-0">
        @endif
        @if (!Route::is(['index']))
            <div class="card">
        @endif

        <div class="card-header">
            <div class="row align-center">
                <div class="col">
                    <h5 class="card-title">Recent Estimates</h5>
                </div>
                <div class="col-auto">
                    {{-- Updated link to point to an actual route name if available --}}
                    <a href="{{ url('invoice-details') }}" class="btn-right btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3">
                {{-- These progress bars are using static widths in your provided HTML (39%, 35%, 26%).
                     If you want these dynamic, you'd calculate the percentages in your PHP component.
                     We will keep them static as they were in your source code. --}}
                <div class="progress progress-md rounded-pill mb-3">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 39%" aria-valuenow="39"
                        aria-valuemin="0" aria-valuemax="100"></div>
                    <div class="progress-bar bg-danger" role="progressbar" style="width: 35%" aria-valuenow="35"
                        aria-valuemin="0" aria-valuemax="100"></div>
                    <div class="progress-bar bg-warning" role="progressbar" style="width: 26%" aria-valuenow="26"
                        aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="row">
                    <div class="col-auto">
                        <i class="fas fa-circle text-success me-1"></i> Sent ({{ $sent }})
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-circle text-warning me-1"></i> Draft ({{ $draft }})
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-circle text-danger me-1"></i> Expired ({{ $expired }})
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Customer</th>
                            <th>Expiry Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- FIX: Removed JSON code. Now looping through $estimates from your Livewire PHP component --}}
                        @foreach ($estimates as $estimate)
                            <tr>
                                <td>
                                    <h2 class="table-avatar">
                                        {{-- FIX: Using dynamic data from the database relationships --}}
                                        <a href="{{ url('customer-details', $estimate->customer?->id) }}">
                                            {{-- Assuming you have a default avatar image if one isn't in the DB --}}
                                            <img class="avatar avatar-sm me-2 avatar-img rounded-circle"
                                                src="{{ URL::asset('/assets/img/profiles/avatar-01.jpg') }}"
                                                alt="User Image"> 
                                            {{ $estimate->customer?->name ?? 'N/A' }}
                                        </a>
                                    </h2>
                                </td>
                                <td>{{ $estimate->expiry_date->format('d M Y') }}</td>
                                <td>{{ $currencySymbol }}{{ number_format($estimate->amount, 2) }}</td>
                                <td><span class="badge bg-inverse-{{ Str::slug($estimate->status) ?? 'info' }}">
                                        {{ ucfirst($estimate->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    {{-- Actions Menu (using static links for brevity) --}}
                                    <div class="dropdown dropdown-action">
                                        <a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-ellipsis-h"></i></a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="{{ url('edit-invoice') }}"><i class="far fa-edit me-2"></i>Edit</a>
                                            {{-- ... other actions ... --}}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        </div> {{-- Closes the conditional card div --}}
    </div>
    
</div>
{{-- FIX: This single wrapping div is CRITICAL for Livewire to work --}}

