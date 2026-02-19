@extends('layout.mainlayout')
@section('page-title', 'Invoice Template 2')
@section('content')
    @include('Sales.Invoices.partials.template-sample', ['templateTitle' => 'Invoice Template 2'])
@endsection
