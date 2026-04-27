@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header">
                <h5 class="mb-1">Edit RFQ {{ $rfq->rfq_number }}</h5>
                <p class="text-muted mb-0">Update draft RFQ header details before sending to suppliers.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('rfq.update', $rfq) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $rfq->title) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Required Date</label>
                            <input type="date" name="required_date" class="form-control" value="{{ old('required_date', $rfq->required_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="4">{{ old('notes', $rfq->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Update RFQ</button>
                        <a href="{{ route('rfq.show', $rfq) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
