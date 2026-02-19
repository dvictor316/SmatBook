@extends('layout.mainlayout')
@section('page-title', 'Invoice Template 4')
@section('content')
    @include('Sales.Invoices.partials.template-sample', ['templateTitle' => 'Invoice Template 4'])
@endsection
