@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <h3>Hotel Setup — Property Information</h3>
        </div>

        <form method="POST" action="{{ route('hotel.setup.storeStep1') }}">
            @csrf
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <label>Name</label>
                                <input name="name" class="form-control" value="{{ old('name', $property->name ?? '') }}" required>
                            </div>
                            <div class="mb-3">
                                <label>Code</label>
                                <input name="code" class="form-control" value="{{ old('code', $property->code ?? '') }}">
                            </div>
                            <div class="mb-3">
                                <label>Address</label>
                                <textarea name="address" class="form-control">{{ old('address', $property->address ?? '') }}</textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-4"><label>City</label><input name="city" class="form-control" value="{{ old('city', $property->city ?? '') }}"></div>
                                <div class="col-md-4"><label>State</label><input name="state" class="form-control" value="{{ old('state', $property->state ?? '') }}"></div>
                                <div class="col-md-4"><label>Country</label><input name="country" class="form-control" value="{{ old('country', $property->country ?? '') }}"></div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-4"><label>Phone</label><input name="phone" class="form-control" value="{{ old('phone', $property->phone ?? '') }}"></div>
                                <div class="col-md-4"><label>Email</label><input name="email" class="form-control" value="{{ old('email', $property->email ?? '') }}"></div>
                                <div class="col-md-4"><label>Currency</label><input name="currency_code" class="form-control" value="{{ old('currency_code', $property->currency_code ?? '') }}"></div>
                            </div>
                            <div class="mt-3 text-end">
                                <button class="btn btn-primary">Save &amp; Continue</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
