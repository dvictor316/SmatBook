<?php $page = 'packages'; ?>
@extends('layout.mainlayout')

@section('content')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: { 'primary': '#4f46e5' },
                fontFamily: { sans: ['Inter', 'sans-serif'] },
            }
        }
    }
</script>

<div class="page-wrapper bg-gray-50 min-h-screen">
    <div class="p-4 sm:p-6 lg:p-8 w-full max-w-2xl mx-auto">
        
        {{-- Breadcrumbs / Back Button --}}
        <div class="mb-6">
            <a href="{{ route('super_admin.packages.index') }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-primary transition">
                <i class="fe fe-arrow-left mr-2"></i> Back to All Plans
            </a>
        </div>

        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="bg-primary p-6">
                <h3 class="text-xl font-black text-white">Edit Subscription Plan</h3>
                <p class="text-indigo-100 text-sm">Update pricing, features, and billing cycles for <b>{{ $plan->name }}</b></p>
            </div>

            <form action="{{ route('super_admin.packages.update', $plan->id) }}" method="POST" class="p-8">
                @csrf
                {{-- Note: Your route is defined as POST in web.php, so no @method('PUT') is needed unless you change the route --}}
                
                <div class="space-y-6">
                    {{-- Plan Name --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Plan Name</label>
                        <input type="text" name="name" value="{{ old('name', $plan->name) }}" required 
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary outline-none transition font-semibold">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        {{-- Price --}}
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Price ($)</label>
                            <input type="number" step="0.01" name="price" value="{{ old('price', $plan->price) }}" required 
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none font-semibold">
                        </div>
                        {{-- Billing Cycle --}}
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Billing Cycle</label>
                            <select name="duration" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none font-semibold">
                                <option value="monthly" {{ $plan->billing_cycle == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="yearly" {{ $plan->billing_cycle == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                <option value="lifetime" {{ $plan->billing_cycle == 'lifetime' ? 'selected' : '' }}>Lifetime</option>
                            </select>
                        </div>
                    </div>

                    {{-- Features --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Features (Comma separated)</label>
                        <textarea name="features" rows="4" 
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none transition">{{ old('features', $plan->features) }}</textarea>
                        <p class="mt-2 text-[10px] text-gray-400 italic">Example: 24/7 Support, Unlimited Reports, Pro Tools</p>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Status</label>
                        <div class="flex items-center space-x-4">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" name="status" value="1" {{ $plan->is_active == 1 ? 'checked' : '' }} class="hidden peer">
                                <div class="px-4 py-2 rounded-xl border border-gray-200 peer-checked:bg-green-50 peer-checked:border-green-500 peer-checked:text-green-700 font-bold text-xs transition">Active</div>
                            </label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" name="status" value="0" {{ $plan->is_active == 0 ? 'checked' : '' }} class="hidden peer">
                                <div class="px-4 py-2 rounded-xl border border-gray-200 peer-checked:bg-red-50 peer-checked:border-red-500 peer-checked:text-red-700 font-bold text-xs transition">Inactive</div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex space-x-3">
                    <button type="submit" class="flex-1 py-4 bg-primary text-white font-black rounded-2xl shadow-lg shadow-indigo-100 hover:opacity-90 transition transform active:scale-95">
                        Update Plan Details
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .page-wrapper { margin-left: 250px; }
    body.mini-sidebar .page-wrapper { margin-left: 80px; }
    @media (max-width: 991.98px) { .page-wrapper { margin-left: 0 !important; } }
</style>
@endsection