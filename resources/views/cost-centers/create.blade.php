@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="mb-1">New Cost Center</h5>
                    <p class="text-muted mb-0">Create a cost center and optionally attach it to a department.</p>
                </div>
                <div>
                    <a href="{{ route('cost-centers.index') }}" class="btn btn-outline-primary">Back to Cost Centers</a>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-9">
                <div class="card">
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                            </div>
                        @endif

                        <form action="{{ route('cost-centers.store') }}" method="POST">
                            @csrf
                            <div class="row g-3">
                                <div class="col-lg-8 col-md-7">
                                    <label class="form-label fw-semibold">Cost Center Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name') }}" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-lg-4 col-md-5">
                                    <label class="form-label fw-semibold">Code</label>
                                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                                        value="{{ old('code') }}" placeholder="e.g. CC-001">
                                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Type</label>
                                    <select name="type" class="form-select @error('type') is-invalid @enderror">
                                        <option value="">-- Select Type --</option>
                                        <option value="operational" @selected(old('type') === 'operational')>Operational</option>
                                        <option value="project" @selected(old('type') === 'project')>Project</option>
                                        <option value="department" @selected(old('type') === 'department')>Department</option>
                                        <option value="branch" @selected(old('type') === 'branch')>Branch</option>
                                        <option value="profit_center" @selected(old('type') === 'profit_center')>Profit Center</option>
                                        <option value="investment_center" @selected(old('type') === 'investment_center')>Investment Center</option>
                                    </select>
                                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                        <label class="form-label fw-semibold mb-0">Department (link)</label>
                                        @if(Route::has('departments.create'))
                                            <a href="{{ route('departments.create') }}" class="btn btn-sm btn-outline-primary" title="Add department">+</a>
                                        @endif
                                    </div>
                                    <select name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                                        <option value="">-- None --</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->name }}</option>
                                        @endforeach
                                    </select>
                                    @if($departments->isEmpty())
                                        <div class="form-text text-warning">No departments exist yet. Use the + button to create one if you want to link this cost center.</div>
                                    @endif
                                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Description</label>
                                    <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1"
                                            @checked(old('is_active', true))>
                                        <label class="form-check-label" for="isActive">Active</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary">Save Cost Center</button>
                                <a href="{{ route('cost-centers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
