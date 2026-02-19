@extends('layout.mainlayout')
@section('page-title', 'Invoice Template 1')
@section('content')
    @include('Sales.Invoices.partials.template-sample', ['templateTitle' => 'Invoice Template 1'])
@endsection
