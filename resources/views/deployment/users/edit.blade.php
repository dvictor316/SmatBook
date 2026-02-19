@extends('layout.mainlayout')
@section('page-title', 'Edit — ' . $user->name)
@section('breadcrumb')
    <a href="{{ route('deployment.dashboard') }}">Home</a>
    <i class="fas fa-chevron-right"></i>
    <a href="{{ route('deployment.users.index') }}">Users</a>
    <i class="fas fa-chevron-right"></i>
    <a href="{{ route('deployment.users.view', $user->id) }}">{{ $user->name }}</a>
    <i class="fas fa-chevron-right"></i>
    <span>Edit</span>
@endsection
@section('content')
<div class="sb-shell page-content-wrapper">
<div class="container-fluid py-4">
<div class="col-lg-7">
<div class="dm-card">
    <h5 class="fw-bold mb-4" style="color:#1e40af;">Edit User</h5>
    <form action="{{ route('deployment.users.update', $user->id) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3"><label class="form-label fw-semibold" style="font-size:13px;color:#475569;">Name *</label><input type="text" name="name" class="form-control" style="border-radius:8px;border-color:#e2e8f0;" value="{{ old('name', $user->name) }}" required>@error('name')<small class="text-danger">{{ $message }}</small>@enderror</div>
        <div class="mb-3"><label class="form-label fw-semibold" style="font-size:13px;color:#475569;">Email *</label><input type="email" name="email" class="form-control" style="border-radius:8px;border-color:#e2e8f0;" value="{{ old('email', $user->email) }}" required>@error('email')<small class="text-danger">{{ $message }}</small>@enderror</div>
        <div class="row">
            <div class="col-6 mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;color:#475569;">Company</label>
                <select name="company_id" class="form-select" style="border-radius:8px;border-color:#e2e8f0;" disabled>
                    @foreach($companies as $c)<option value="{{ $c->id }}" {{ $user->company_id==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
                </select>
                <small class="text-muted">Company cannot be changed after creation.</small>
            </div>
            <div class="col-6 mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;color:#475569;">Role *</label>
                <select name="role" class="form-select" style="border-radius:8px;border-color:#e2e8f0;" required>
                    @foreach($roles as $r)<option value="{{ $r }}" {{ old('role', $user->role)===$r?'selected':'' }}>{{ ucfirst($r) }}</option>@endforeach
                </select>
            </div>
        </div>
        <button type="submit" class="btn me-2" style="background:#1e40af;color:#fff;border:none;border-radius:8px;font-weight:600;padding:10px 24px;"><i class="fas fa-save me-1"></i>Save</button>
        <a href="{{ route('deployment.users.view', $user->id) }}" class="btn btn-outline-secondary" style="border-radius:8px;">Cancel</a>
    </form>
</div>
</div>
</div>
</div>
@endsection
