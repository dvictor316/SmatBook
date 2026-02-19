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

<div class="bg-gray-50 font-sans antialiased min-h-screen pt-4 pb-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="pb-6 border-b border-gray-200 mb-8 flex justify-between items-center no-print">
            <div>
                <h1 class="text-3xl font-bold leading-tight text-gray-900">Subscribers</h1>
                @if($selectedPlan)
                    <p class="text-sm text-primary font-medium mt-1">
                        Showing highlight for: <span class="underline">{{ $selectedPlan }}</span>
                        <a href="{{ route('super_admin.subscription') }}" class="ml-2 text-gray-400 hover:text-red-500 text-xs">Clear Filter</a>
                    </p>
                @endif
            </div>
            
            <button onclick="window.print()" class="bg-primary text-white px-5 py-2 rounded-lg shadow-md hover:bg-indigo-700 transition-all flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Page
            </button>
        </div>

        <div class="shadow-xl rounded-2xl bg-white overflow-hidden border border-gray-100">
            <div class="overflow-x-auto">
                <table id="subscribers-table" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subscriber</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Plan Name</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Billing</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Start Date</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Expiry</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($subscriptions as $sub)
                            @php
                                // Check if this record matches the plan picked from the previous page
                                $isPicked = ($selectedPlan && trim(strtolower($sub->package_name)) == trim(strtolower($selectedPlan)));
                            @endphp
                            
                            <tr class="transition-all duration-200 {{ $isPicked ? 'bg-indigo-50/50 border-l-4 border-primary' : 'hover:bg-gray-50' }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-400">
                                    {{ $loop->iteration }}
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full flex items-center justify-center font-bold text-xs transition-colors {{ $isPicked ? 'bg-primary text-white shadow-lg' : 'bg-gray-200 text-gray-600' }}">
                                                {{ strtoupper(substr($sub->customer_name, 0, 2)) }}
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold {{ $isPicked ? 'text-primary' : 'text-gray-900' }}">
                                                {{ $sub->customer_name }}
                                            </div>
                                            <div class="text-xs text-gray-500">{{ $sub->email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium {{ $isPicked ? 'text-indigo-700' : 'text-gray-700' }}">
                                        {{ $sub->package_name }}
                                    </span>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-tighter">{{ $sub->domain_name }}</p>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="flex items-center text-xs font-medium text-green-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-green-500 mr-1.5"></span> Active
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $sub->package_type == 'Yearly' ? 'bg-purple-100 text-purple-700' : 'bg-orange-100 text-orange-700' }}">
                                        {{ $sub->package_type }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $sub->created_at->format('d M, Y') }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium {{ $isPicked ? 'text-gray-900' : 'text-gray-500' }}">
                                    {{ $sub->expiry_date ? \Carbon\Carbon::parse($sub->expiry_date)->format('d M, Y') : 'Life-time' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <p class="text-gray-400 italic">No matching active subscribers found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print, .header, .sidebar, .footer, button { display: none !important; }
        .bg-gray-50 { background-color: white !important; }
        .shadow-xl { box-shadow: none !important; border: 1px solid #eee; }
        body { padding: 0; margin: 0; }
        .max-w-7xl { max-width: 100% !important; width: 100% !important; padding: 0 !important; }
        /* Force color printing in some browsers */
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
</style>
@endsection