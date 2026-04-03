<?php $page = 'suppliers'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            @component('components.page-header')
                @slot('title')
                    Add Supplier
                @endslot
            @endcomponent

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('suppliers.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Supplier Name</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact" class="form-control" value="{{ old('contact') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="3">{{ old('address') }}</textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Opening Balance</label>
                                <input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ old('opening_balance') }}" placeholder="0.00">
                                <small class="text-muted">Amount owed to this supplier at setup.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Opening Balance Date</label>
                                <input type="date" name="opening_balance_date" class="form-control" value="{{ old('opening_balance_date') }}">
                            </div>
                        </div>
                        <div class="text-end">
                            <a href="{{ route('suppliers.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Supplier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
