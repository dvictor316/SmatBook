<?php $page = 'membership-addons'; ?>
@extends('layout.mainlayout')
@section('content')
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="card mb-0">
                <div class="card-body">
                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="content-page-header">
                            <h5>Membership Addons</h5>
                        </div>
                    </div>
                    <!-- /Page Header -->

                    {{-- Determine if we are creating or editing --}}
                    @php
                        // Assume $addon is passed from the controller if editing, otherwise it's null
                        $isEditing = isset($addon) && $addon;
                        $actionUrl = $isEditing ? url('/superadmin/addons/' . $addon->id) : url('/superadmin/addons');
                        $method = $isEditing ? 'POST' : 'POST'; // Laravel often uses POST with @method('PUT') for updates
                    @endphp

                    <div class="row">
                        <div class="col-md-12">

                            {{-- Form Start --}}
                            <form action="{{ $actionUrl }}" method="POST">
                                @csrf
                                @if ($isEditing)
                                    @method('PUT') {{-- Use PUT method for updates --}}
                                @endif

                                {{-- Display Validation Errors --}}
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <p class="mb-0 fw-bold">Please correct the following errors:</p>
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="form-group-add">
                                    <h5 class="form-title">Plan Details</h5>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="input-block mb-3">
                                                <label for="addon_name">Addon Name <span class="text-danger">*</span></label>
                                                <input type="text" id="addon_name" name="name" class="form-control" 
                                                    placeholder="Enter Addon Name" 
                                                    value="{{ old('name', $addon->name ?? '') }}" 
                                                    required>
                                                @error('name')<span class="text-danger">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group-add">
                                    <h5 class="form-title">Addon Settings</h5>
                                    <div class="row">
                                        
                                        {{-- Helper macro/loop could simplify this, but sticking to explicit code for clarity --}}
                                        @php
                                            $features = [
                                                'services' => ['label' => 'Services', 'id' => 1],
                                                'appointments' => ['label' => 'Appointments', 'id' => 2],
                                                'staffs' => ['label' => 'Staffs', 'id' => 3],
                                                'gallery' => ['label' => 'Gallery', 'id' => 4],
                                                'additional_service' => ['label' => 'Additional Service', 'id' => 5],
                                            ];
                                        @endphp

                                        @foreach ($features as $key => $feature)
                                            @php
                                                // Get current values from model or old input
                                                $limitValue = old($key . '_limit', $addon->{$key . '_limit'} ?? 0);
                                                $isUnlimited = old($key . '_unlimited', $addon->{$key . '_unlimited'} ?? false);
                                                $isEnabled = old($key . '_enabled', $addon->{$key . '_enabled'} ?? true);
                                                $inputNameLimit = $key . '_limit';
                                                $inputNameUnlimited = $key . '_unlimited';
                                                $inputNameEnabled = $key . '_enabled';
                                            @endphp

                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="input-block mb-3">
                                                    <label>{{ $feature['label'] }}</label>
                                                    <div class="align-center d-flex align-items-center">
                                                        {{-- Limit Input --}}
                                                        <input type="number" name="{{ $inputNameLimit }}" 
                                                            class="form-control me-2" 
                                                            placeholder="1-100" 
                                                            value="{{ $limitValue }}" 
                                                            min="1" 
                                                            max="100"
                                                            @if($isUnlimited) disabled @endif>
                                                        
                                                        {{-- Enable/Disable Toggle --}}
                                                        <div class="status-toggle flex-shrink-0 ms-2">
                                                            <input id="rating_{{ $feature['id'] }}" name="{{ $inputNameEnabled }}" class="check" type="checkbox" 
                                                                value="1" 
                                                                {{ old($inputNameEnabled, $isEnabled) ? 'checked' : '' }}>
                                                            <label for="rating_{{ $feature['id'] }}" class="checktoggle checkbox-bg">checkbox</label>
                                                        </div>
                                                    </div>
                                                    
                                                    {{-- Unlimited Checkbox --}}
                                                    <span>
                                                        <label class="custom_check">
                                                            <input type="checkbox" name="{{ $inputNameUnlimited }}" 
                                                                value="1" 
                                                                {{ old($inputNameUnlimited, $isUnlimited) ? 'checked' : '' }}
                                                                onchange="toggleLimitInput(this, 'input[name={{ $inputNameLimit }}]')">
                                                            <span class="checkmark"></span>
                                                            <span>Unlimited</span>
                                                        </label>
                                                        @error($inputNameLimit)<span class="text-danger d-block">{{ $message }}</span>@enderror
                                                    </span>

                                                </div>
                                            </div>
                                        @endforeach
                                        
                                        {{-- Special case for Booking Option (only a toggle) --}}
                                        <div class="col-lg-6 col-md-6 col-sm-12">
                                            <div class="input-block mb-3 booking-option">
                                                <label>Booking Option</label>
                                                <div class="status-toggle">
                                                    <input id="rating_6" name="booking_enabled" class="check" type="checkbox" 
                                                        value="1"
                                                        {{ old('booking_enabled', $addon->booking_enabled ?? true) ? 'checked' : '' }}>
                                                    <label for="rating_6" class="checktoggle checkbox-bg">checkbox</label>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    {{-- Assuming 'addons.index' route for Cancel button --}}
                                    <a href="{{ url('/superadmin/addons') }}" class="btn btn-primary cancel me-2">Cancel</a> 
                                    <button type="submit" class="btn btn-primary">
                                        {{ $isEditing ? 'Update Changes' : 'Save Changes' }}
                                    </button>
                                </div>
                            </form>
                            {{-- Form End --}}

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- /Page Wrapper -->

    {{-- JavaScript to manage the Unlimited checkbox interaction --}}
    <script>
        function toggleLimitInput(checkbox, targetSelector) {
            const limitInput = document.querySelector(targetSelector);
            if (limitInput) {
                limitInput.disabled = checkbox.checked;
                if (checkbox.checked) {
                    limitInput.value = 0; // Clear value or set to a placeholder 0 when unlimited
                }
            }
        }

        // Initialize state on page load (important for old input persistence)
        document.addEventListener('DOMContentLoaded', function() {
            @foreach ($features as $key => $feature)
                const unlimitedCheckbox{{ $feature['id'] }} = document.querySelector('input[name="{{ $key . '_unlimited' }}"]');
                if (unlimitedCheckbox{{ $feature['id'] }} && unlimitedCheckbox{{ $feature['id'] }}.checked) {
                    toggleLimitInput(unlimitedCheckbox{{ $feature['id'] }}, 'input[name="{{ $key . '_limit' }}"]');
                }
            @endforeach
        });
    </script>
@endsection