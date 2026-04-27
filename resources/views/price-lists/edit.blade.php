@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="content-page-header">
                <h5 class="mb-1">Edit Price List</h5>
                <p class="text-muted mb-0">Update the main settings for this pricing list.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('price-lists.update', $priceList) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $priceList->name) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Valid From</label>
                            <input type="date" name="valid_from" class="form-control" value="{{ old('valid_from', $priceList->valid_from?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Valid To</label>
                            <input type="date" name="valid_to" class="form-control" value="{{ old('valid_to', $priceList->valid_to?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="4">{{ old('notes', $priceList->notes) }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="price_list_active" @checked(old('is_active', $priceList->is_active))>
                                <label class="form-check-label" for="price_list_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Update Price List</button>
                        <a href="{{ route('price-lists.show', $priceList) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
