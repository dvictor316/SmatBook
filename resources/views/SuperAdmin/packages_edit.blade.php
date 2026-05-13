<?php $page = 'packages'; ?>
@extends('layout.mainlayout')

@section('content')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: { 'primary': '#0f3a8a', 'brand-gold': '#d7a928', 'brand-navy': '#061a44' },
                fontFamily: { sans: ['Inter', 'sans-serif'] },
            }
        }
    }
</script>

<div class="page-wrapper min-h-screen">
    <div class="p-4 sm:p-6 lg:p-8 w-full max-w-2xl mx-auto">

        <div class="mb-6">
            <a href="{{ route('super_admin.packages.index') }}" class="inline-flex items-center text-sm font-bold text-gray-500 hover:text-primary transition">
                <i class="fe fe-arrow-left mr-2"></i> Back to All Plans
            </a>
        </div>

        <div class="bg-white rounded-3xl shadow-xl border overflow-hidden" style="border-color: #d8e3f5;">
            <div class="p-6" style="background: linear-gradient(135deg, #061a44, #0f3a8a);">
                <h3 class="text-xl font-black text-white">Edit Subscription Plan</h3>
                <p class="text-sm" style="color: #fff1bf;">Update pricing, features, and billing cycles for <b>{{ $plan->name }}</b></p>
            </div>

            <form action="{{ route('super_admin.packages.update', $plan->id) }}" method="POST" class="p-8">
                @csrf

                <div class="space-y-6">

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Plan Name</label>
                        <input type="text" name="name" value="{{ old('name', $plan->name) }}" required 
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none transition font-semibold">
                    </div>

                    <div class="grid grid-cols-2 gap-4">

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Price ($)</label>
                            <input type="number" step="0.01" name="price" value="{{ old('price', $plan->price) }}" required 
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none font-semibold">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Billing Cycle</label>
                            <select name="duration" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none font-semibold">
                                <option value="monthly" {{ $plan->billing_cycle == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="yearly" {{ $plan->billing_cycle == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                <option value="lifetime" {{ $plan->billing_cycle == 'lifetime' ? 'selected' : '' }}>Lifetime</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Features (Comma separated)</label>
                        <textarea name="features" rows="4" 
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none transition">{{ old('features', $plan->features) }}</textarea>
                        <p class="mt-2 text-[10px] text-gray-400 italic">Example: 24/7 Support, Unlimited Reports, Pro Tools</p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Status</label>
                        <div class="flex items-center space-x-4">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" name="status" value="1" {{ $plan->is_active == 1 ? 'checked' : '' }} class="hidden peer">
                                <div class="px-4 py-2 rounded-xl border border-gray-200 font-bold text-xs transition active-plan-pill">Active</div>
                            </label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" name="status" value="0" {{ $plan->is_active == 0 ? 'checked' : '' }} class="hidden peer">
                                <div class="px-4 py-2 rounded-xl border border-gray-200 peer-checked:bg-red-50 peer-checked:border-red-500 peer-checked:text-red-700 font-bold text-xs transition">Inactive</div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex space-x-3">
                    <button type="submit" class="flex-1 py-4 bg-primary text-white font-black rounded-2xl shadow-lg transition transform active:scale-95 plan-save-btn">
                        Update Plan Details
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .page-wrapper {
        margin-left: 250px;
        background:
            radial-gradient(circle at 8% 0%, rgba(215, 169, 40, 0.11), transparent 28%),
            radial-gradient(circle at 92% 12%, rgba(37, 99, 235, 0.10), transparent 30%),
            #f7faff;
    }
    body.mini-sidebar .page-wrapper { margin-left: 80px; }
    .focus\:ring-primary:focus {
        --tw-ring-color: rgba(215, 169, 40, .42) !important;
        border-color: #d7a928 !important;
    }
    .plan-save-btn {
        border: 1px solid transparent;
        box-shadow: 0 14px 28px -20px rgba(15, 58, 138, .65);
    }
    .plan-save-btn:hover {
        background: #fff !important;
        color: #0f3a8a !important;
        border-color: #d7a928 !important;
    }
    input[name="status"]:checked + .active-plan-pill {
        background: #fff8db !important;
        border-color: #d7a928 !important;
        color: #061a44 !important;
    }
    @media (max-width: 991.98px) { .page-wrapper { margin-left: 0 !important; } }
</style>
@endsection
