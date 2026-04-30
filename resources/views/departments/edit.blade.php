@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="mb-1">Edit Department</h5>
                    <p class="text-muted mb-0">Update the department profile and reporting structure.</p>
                </div>
                <div>
                    <a href="{{ route('departments.index') }}" class="btn btn-outline-primary">Back to Departments</a>
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

                        <form action="{{ route('departments.update', $department) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row g-3">
                                <div class="col-lg-8 col-md-7">
                                    <label class="form-label fw-semibold">Department Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name', $department->name) }}" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-lg-4 col-md-5">
                                    <label class="form-label fw-semibold">Code</label>
                                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                                        value="{{ old('code', $department->code) }}" placeholder="e.g. FIN">
                                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Parent Department</label>
                                    <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                                        <option value="">-- None (Top Level) --</option>
                                        @foreach($departments as $dept)
                                            @if($dept->id !== $department->id)
                                                <option value="{{ $dept->id }}" @selected(old('parent_id', $department->parent_id) == $dept->id)>{{ $dept->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @if($departments->isEmpty())
                                        <small class="text-muted d-block mt-2">No other active departments are available in this branch yet, so this department cannot be assigned a parent right now.</small>
                                    @endif
                                    @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Head / Manager</label>
                                    <select name="head_employee_id" class="form-select @error('head_employee_id') is-invalid @enderror">
                                        <option value="">-- None --</option>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}" @selected(old('head_employee_id', $department->head_employee_id) == $emp->id)>{{ $emp->name }}</option>
                                        @endforeach
                                    </select>
                                    @if($employees->isEmpty())
                                        <small class="text-muted d-block mt-2">No employees are available in this workspace yet, so no head can be assigned for now.</small>
                                    @endif
                                    @error('head_employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Description</label>
                                    <textarea name="description" class="form-control" rows="4">{{ old('description', $department->description) }}</textarea>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1"
                                            @checked(old('is_active', $department->is_active))>
                                        <label class="form-check-label" for="isActive">Active</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary">Update Department</button>
                                <a href="{{ route('departments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
