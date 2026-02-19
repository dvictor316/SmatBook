<?php $page = 'plans'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid pb-0">

        <div class="subscription-plane-head">
            <ul>
                <li>
                    <a href="{{ route('super_admin.plans.index') }}" class="active">Subscription Plans</a>
                </li>
                <li>
                    <a href="{{ route('super_admin.subscription') }}">Subscribers List</a>
                </li>
            </ul>
        </div>

        @component('components.page-header')
            @slot('title') Subscription Plans @endslot
        @endcomponent

        <div class="super-admin-list-head mb-4">
            <div class="row">
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card w-100">
                        <div class="card-body">
                            <div class="grid-info-item total-plane">
                                <div class="grid-info">
                                    <span>Total Plans</span>
                                    <h4>{{ $plans->count() }}</h4>
                                </div>
                                <div class="grid-head-icon"><i class="fe fe-package"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card w-100">
                        <div class="card-body">
                            <div class="grid-info-item active-plane">
                                <div class="grid-info">
                                    <span>Active Plans</span>
                                    <h4>{{ $plans->where('status', true)->count() }}</h4>
                                </div>
                                <div class="grid-head-icon"><i class="fe fe-check-circle"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            @forelse ($plans as $plan)
                <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3 d-flex">
                    <div class="packages card w-100 {{ $plan->recommended ? 'active-pkg' : '' }}" style="border-radius: 15px; position: relative; overflow: hidden;">
                        <div class="package-header d-flex justify-content-between p-4">
                            <div>
                                <h6 class="text-primary text-uppercase fw-bold" style="letter-spacing: 1px;">{{ $plan->billing_cycle }}</h6>
                                <h4 class="fw-bold">{{ $plan->name }}</h4>
                            </div>
                            <span class="icon-frame bg-light rounded-circle p-3 d-flex align-items-center justify-content-center">
                                <img src="{{ asset('assets/img/icons/price-01.svg') }}" alt="icon" width="25">
                            </span>
                        </div>

                        @if($plan->recommended)
                            <span class="badge bg-warning text-dark position-absolute" style="top: 15px; right: -30px; transform: rotate(45deg); width: 120px; text-align: center;">Best Value</span>
                        @endif

                        <div class="px-4">
                            <h2 class="fw-bold">${{ number_format($plan->price, 2) }} <span class="fs-6 text-muted fw-normal">/{{ $plan->billing_cycle == 'monthly' ? 'mo' : 'yr' }}</span></h2>
                            <p class="text-muted small mt-2">{{ $plan->description }}</p>
                        </div>

                        <div class="features-list px-4 mt-3 flex-grow-1">
                            <h6 class="fw-bold mb-3">What’s included:</h6>
                            <ul class="list-unstyled">
                                @foreach ($plan->features as $feature)
                                    <li class="mb-2 d-flex align-items-start">
                                        <i class="fe fe-check-circle text-success me-2 mt-1"></i>
                                        <span class="small">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="card-footer bg-transparent border-0 d-flex justify-content-center pb-4">
                            <a href="{{ route('super_admin.plans.edit', $plan->id) }}" class="btn btn-outline-primary btn-sm me-2">
                                <i class="fe fe-edit me-1"></i> Edit
                            </a>
                            <form action="{{ route('super_admin.plans.destroy', $plan->id) }}" method="POST" onsubmit="return confirm('Delete this plan?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="fe fe-trash-2 me-1"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <p class="text-muted">No subscription plans found.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection