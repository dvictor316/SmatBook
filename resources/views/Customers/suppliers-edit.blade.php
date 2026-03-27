<?php $page = 'suppliers'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            @component('components.page-header')
                @slot('title')
                    Edit Supplier
                @endslot
            @endcomponent

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('suppliers.update', $supplier->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Supplier Name</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $supplier->name ?? $supplier->supplier_name ?? $supplier->company_name) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact" class="form-control" value="{{ old('contact', $supplier->contact ?? '') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $supplier->email ?? '') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $supplier->phone ?? '') }}">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="3">{{ old('address', $supplier->address ?? '') }}</textarea>
                            </div>
                        </div>
                        <div class="text-end">
                            <a href="{{ route('suppliers.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Supplier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
