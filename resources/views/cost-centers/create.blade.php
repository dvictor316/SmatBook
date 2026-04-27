@extends('layout.app')

@section('title', 'New Cost Center')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">New Cost Center</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('cost-centers.index') }}">Cost Centers</a></li>
                    <li class="breadcrumb-item active">New</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card">
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <form action="{{ route('cost-centers.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Cost Center Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Code</label>
                                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                                       value="{{ old('code') }}" placeholder="e.g. CC-001">
                                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Type</label>
                                <select name="type" class="form-select @error('type') is-invalid @enderror">
                                    <option value="">-- Select Type --</option>
                                    <option value="profit_center" @selected(old('type') === 'profit_center')>Profit Center</option>
                                    <option value="cost_center" @selected(old('type') === 'cost_center')>Cost Center</option>
                                    <option value="investment_center" @selected(old('type') === 'investment_center')>Investment Center</option>
                                </select>
                                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Parent Cost Center</label>
                                <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                                    <option value="">-- None (Top Level) --</option>
                                    @foreach($costCenters as $cc)
                                        <option value="{{ $cc->id }}" @selected(old('parent_id') == $cc->id)>{{ $cc->name }}</option>
                                    @endforeach
                                </select>
                                @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Budget (optional)</label>
                                <input type="number" name="budget" class="form-control @error('budget') is-invalid @enderror"
                                       value="{{ old('budget') }}" step="0.01" min="0" placeholder="Annual budget">
                                @error('budget')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Department (link)</label>
                                <select name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                                    <option value="">-- None --</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1"
                                           @checked(old('is_active', true))>
                                    <label class="form-check-label" for="isActive">Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Save Cost Center</button>
                            <a href="{{ route('cost-centers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
