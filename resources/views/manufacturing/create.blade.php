@extends('layout.app')

@section('title', 'Create Manufacturing Order')

@section('content')
<div class="content container-fluid">
    <div class="page-header"><h3 class="page-title">Create Manufacturing Order</h3></div>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('manufacturing.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">BOM</label>
                    <select name="bom_id" class="form-select" required>
                        <option value="">Select BOM</option>
                        @foreach($boms as $bom)
                            <option value="{{ $bom->id }}">{{ $bom->bom_number }} - {{ $bom->product->name ?? '—' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Quantity</label>
                    <input type="number" step="0.0001" min="0.0001" name="quantity_to_produce" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Planned Start</label>
                    <input type="date" name="planned_start_date" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Planned End</label>
                    <input type="date" name="planned_end_date" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Create Order</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
