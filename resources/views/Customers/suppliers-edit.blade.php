<?php $page = 'suppliers'; ?>
@extends('layout.mainlayout')
@section('content')
    @include('Customers.partials.supplier-form', ['supplier' => $supplier])
@endsection
