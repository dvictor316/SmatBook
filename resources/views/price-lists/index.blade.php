@extends('layout.app')

@section('title', 'Price Lists')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Price Lists</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Price Lists</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('price-lists.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i> New Price List
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th><th>Currency</th><th>Type</th><th>Discount %</th><th>Valid From</th><th>Valid To</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($priceLists as $list)
                            <tr>
                                <td>{{ $list->name }}</td>
                                <td>{{ $list->currency }}</td>
                                <td>{{ ucfirst($list->list_type ?? 'standard') }}</td>
                                <td>{{ $list->discount_percentage ? $list->discount_percentage . '%' : '—' }}</td>
                                <td>{{ $list->valid_from ? $list->valid_from->format('d M Y') : '—' }}</td>
                                <td>{{ $list->valid_to ? $list->valid_to->format('d M Y') : '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $list->is_active ? 'success' : 'secondary' }}">{{ $list->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('price-lists.show', $list) }}" class="btn btn-sm btn-outline-primary me-1">View</a>
                                    <a href="{{ route('price-lists.edit', $list) }}" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                    <form action="{{ route('price-lists.destroy', $list) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this price list?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">No price lists found. <a href="{{ route('price-lists.create') }}">Create one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($priceLists->hasPages())
            <div class="card-footer">{{ $priceLists->links() }}</div>
        @endif
    </div>
</div>
@endsection
