@extends('layout.mainlayout')
@section('page-title', 'Invoice Template 5')
@section('content')
    @include('Sales.Invoices.partials.template-sample', ['templateTitle' => 'Invoice Template 5'])
@endsection
