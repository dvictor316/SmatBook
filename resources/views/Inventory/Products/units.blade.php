<?php $page = 'units'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title') Units of Measure @endslot
        @endcomponent

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <strong>Please fix:</strong> {{ $errors->first() }}
            </div>
        @endif

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="fw-bold mb-1">Create Unit</h5>
                        <p class="text-muted small">Add a unit used for stock, sales, or bulk purchases.</p>
                        <form method="POST" action="{{ route('units.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Kilogram" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Symbol</label>
                                <input type="text" name="symbol" class="form-control" placeholder="kg" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Save Unit</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card-table">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-center table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Symbol</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($units as $unit)
                                        <tr>
                                            <td>{{ $unit->id }}</td>
                                            <td class="fw-semibold">{{ $unit->name }}</td>
                                            <td><span class="badge bg-soft-info text-info">{{ $unit->symbol }}</span></td>
                                            <td>
                                                <span class="badge {{ $unit->status === 'active' ? 'bg-success-light text-success' : 'bg-danger-light text-danger' }}">
                                                    {{ ucfirst($unit->status) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-white border" data-bs-toggle="modal" data-bs-target="#editUnit{{ $unit->id }}">
                                                    Edit
                                                </button>
                                                <form method="POST" action="{{ route('units.toggle', $unit->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-sm btn-white border" type="submit">
                                                        {{ $unit->status === 'active' ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('units.destroy', $unit->id) }}" class="d-inline" onsubmit="return confirm('Delete this unit? Deactivate is safer if it has history.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                                </form>
                                            </td>
                                        </tr>

                                        <div class="modal fade" id="editUnit{{ $unit->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <form method="POST" action="{{ route('units.update', $unit->id) }}" class="modal-content">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Unit</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Name</label>
                                                            <input type="text" name="name" class="form-control" value="{{ $unit->name }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Symbol</label>
                                                            <input type="text" name="symbol" class="form-control" value="{{ $unit->symbol }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Status</label>
                                                            <select name="status" class="form-select" required>
                                                                <option value="active" @selected($unit->status === 'active')>Active</option>
                                                                <option value="inactive" @selected($unit->status === 'inactive')>Inactive</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Update Unit</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No units found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
