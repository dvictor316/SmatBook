@extends('layout.mainlayout')
@section('page-title', 'Invoice Template 3')
@section('content')
    @include('Sales.Invoices.partials.template-sample', ['templateTitle' => 'Invoice Template 3'])
@endsection
