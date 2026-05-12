@extends('layout.mainlayout')

@section('page-title', 'Create Estimate')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        @component('components.page-header')
            @slot('title')
                Create Estimate
            @endslot
        @endcomponent

        @include('estimates._form', ['action' => route('estimates.store')])
    </div>
</div>
@endsection
