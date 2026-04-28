@extends('layout.app')

@section('title', 'Create Milestone')

@section('content')
<div class="page-wrapper">
<div class="content container-fluid">
    <div class="page-header"><h3 class="page-title">Create Milestone</h3></div>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('milestones.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Project</label>
                    <select name="project_id" class="form-select" required>
                        <option value="">Select project</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select">
                        <option value="">Optional customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Milestone Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Billing Amount</label>
                    <input type="number" step="0.01" min="0" name="billing_amount" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Billing Type</label>
                    <select name="billing_type" class="form-select" required>
                        @foreach(['fixed', 'percentage_of_contract', 'on_completion'] as $type)
                            <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Percentage</label>
                    <input type="number" step="0.01" min="0" max="100" name="percentage" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4"></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Save Milestone</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
