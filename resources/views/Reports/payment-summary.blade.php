<?php $page = 'payment-summary'; ?>
@extends('layout.mainlayout')

@section('content')
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
    tailwind.config = {
        theme: {
            extend: { 
                colors: { 'primary': '#4f46e5' },
                animation: { 'fade-in': 'fadeIn 0.2s ease-out', 'slide-up': 'slideUp 0.3s ease-out' }
            }
        }
    }
</script>

<div class="page-wrapper report-page bg-gray-50 min-h-screen relative">
    <div class="p-4 sm:p-6 lg:p-8 w-full">
        @php
            $currencySymbol = '₦';
            $showingFrom = $payments->firstItem() ?? 0;
            $showingTo = $payments->lastItem() ?? 0;
        @endphp
        
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Payment Summary Report</h1>
                <p class="text-sm text-gray-500 mt-1">
                    System Date: <span class="font-bold">{{ now()->format('M d, Y') }}</span> | Total Revenue:
                    <span class="text-green-600 font-bold text-lg">{{ $currencySymbol }}{{ number_format($totalRevenue ?? 0, 2) }}</span>
                </p>
            </div>
            
            <div class="flex items-center space-x-2 no-print">
                <button onclick="generatePDF()" class="inline-flex items-center px-3 py-2 bg-white border border-gray-200 rounded-xl font-semibold text-red-500 hover:bg-gray-50 transition text-sm">
                    <i class="fe fe-file-text mr-2"></i> PDF
                </button>
                <button onclick="generateExcel()" class="inline-flex items-center px-3 py-2 bg-white border border-gray-200 rounded-xl font-semibold text-green-600 hover:bg-gray-50 transition text-sm">
                    <i class="fe fe-download mr-2"></i> Excel
                </button>
                <button onclick="generateCSV()" class="inline-flex items-center px-3 py-2 bg-white border border-gray-200 rounded-xl font-semibold text-blue-600 hover:bg-gray-50 transition text-sm">
                    <i class="fe fe-list mr-2"></i> CSV
                </button>
                <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-xl font-bold shadow-lg shadow-primary/20 hover:bg-indigo-700 transition text-sm">
                    <i class="fe fe-printer mr-2"></i> Print All
                </button>
            </div>
        </div>
        @include('Reports.partials.context-strip', [
            'reportLabel' => 'Payment Summary Report',
            'periodLabel' => request('from') || request('to')
                ? 'Filtered Payment Window'
                : 'All Recorded Payments',
        ])

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            <div class="summary-metric-card bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
                <p class="summary-metric-label text-[11px] uppercase tracking-[0.2em] text-gray-400 font-black">Collected Revenue</p>
                <h2 class="summary-metric-value mt-3 font-black text-gray-900">{{ $currencySymbol }}{{ number_format($totalRevenue ?? 0, 2) }}</h2>
                <p class="summary-metric-note mt-2 text-sm text-gray-500">{{ number_format($summary['total_transactions'] ?? 0) }} payment records in current result</p>
            </div>
            <div class="summary-metric-card bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
                <p class="summary-metric-label text-[11px] uppercase tracking-[0.2em] text-gray-400 font-black">Completed Payments</p>
                <h2 class="summary-metric-value mt-3 font-black text-emerald-600">{{ number_format($summary['completed_count'] ?? 0) }}</h2>
                <p class="summary-metric-note mt-2 text-sm text-gray-500">{{ $currencySymbol }}{{ number_format($summary['completed_amount'] ?? 0, 2) }} confirmed</p>
            </div>
            <div class="summary-metric-card bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
                <p class="summary-metric-label text-[11px] uppercase tracking-[0.2em] text-gray-400 font-black">Average Payment</p>
                <h2 class="summary-metric-value mt-3 font-black text-indigo-600">{{ $currencySymbol }}{{ number_format($summary['average_payment'] ?? 0, 2) }}</h2>
                <p class="summary-metric-note mt-2 text-sm text-gray-500">Largest payment {{ $currencySymbol }}{{ number_format($summary['largest_payment'] ?? 0, 2) }}</p>
            </div>
            <div class="summary-metric-card bg-white p-5 rounded-2xl shadow-sm border border-gray-200">
                <p class="summary-metric-label text-[11px] uppercase tracking-[0.2em] text-gray-400 font-black">Payment Mix</p>
                <h2 class="summary-metric-value mt-3 font-black text-gray-900">{{ $summary['top_method'] ?? 'N/A' }}</h2>
                <p class="summary-metric-note mt-2 text-sm text-gray-500">
                    Pending {{ number_format($summary['pending_count'] ?? 0) }} | Partial {{ number_format($summary['partial_count'] ?? 0) }} | Failed {{ number_format($summary['failed_count'] ?? 0) }}
                </p>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-200 mb-6 no-print">
            <form action="{{ route('reports.payment-summary') }}" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-4">
                    <label class="text-[10px] font-bold text-gray-400 uppercase ml-1">Search Keywords</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="ID, Reference, or Note..." 
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary/20 outline-none transition">
                </div>
                <div class="md:col-span-2">
                    <label class="text-[10px] font-bold text-gray-400 uppercase ml-1">Status</label>
                    <select name="status" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl outline-none">
                        <option value="">All Statuses</option>
                        @foreach ($statusOptions as $statusOption)
                            <option value="{{ $statusOption }}" @selected(request('status') === $statusOption)>{{ $statusOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-[10px] font-bold text-gray-400 uppercase ml-1">Method</label>
                    <select name="method" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl outline-none">
                        <option value="">All Methods</option>
                        @foreach ($methodOptions as $methodOption)
                            <option value="{{ $methodOption }}" @selected(request('method') === $methodOption)>{{ $methodOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-[10px] font-bold text-gray-400 uppercase ml-1">From</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="text-[10px] font-bold text-gray-400 uppercase ml-1">To</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl outline-none">
                </div>
                <div class="md:col-span-12 flex flex-col sm:flex-row gap-3 md:justify-end">
                    <a href="{{ route('reports.payment-summary') }}" class="inline-flex items-center justify-center px-5 py-3 border border-gray-200 rounded-xl font-bold text-gray-600 hover:bg-gray-50 transition">
                        Reset
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center px-5 py-3 bg-gray-900 text-white rounded-xl font-bold hover:bg-black transition shadow-sm">
                        <i class="fe fe-search mr-2"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <div id="bulk-action-bar" class="hidden no-print mb-6 p-4 bg-indigo-50 border border-indigo-100 rounded-2xl flex items-center justify-between animate-fade-in">
            <div class="flex items-center">
                <div class="bg-primary text-white text-xs font-black px-3 py-1.5 rounded-full mr-4" id="selected-count">0</div>
                <p class="text-sm font-semibold text-primary">Rows selected for processing</p>
            </div>
            <div class="flex items-center space-x-3">
                <select id="bulk-status" class="text-sm border-gray-300 rounded-xl focus:ring-primary py-2 px-4 outline-none">
                    <option value="">Bulk Action...</option>
                    <option value="Completed">Mark Completed</option>
                    <option value="Pending">Mark Pending</option>
                    <option value="DELETE_ACTION" class="text-red-600 font-bold">Delete Selected</option>
                </select>
                <button onclick="applyBulkAction()" class="bg-primary text-white px-6 py-2 rounded-xl text-sm font-bold hover:bg-indigo-700 transition shadow-md">Execute</button>
            </div>
        </div>

        <div id="report-container" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200" id="main-payment-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 no-print w-10">
                                <input type="checkbox" id="select-all" class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                            </th>
                            <th class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-widest">Payment ID</th>
                            <th class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-widest">Reference</th>
                            <th class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-widest">Method</th>
                            <th class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-widest">Amount</th>
                            <th class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-widest">Status</th>
                            <th class="px-6 py-4 text-left text-[11px] font-bold text-gray-500 uppercase tracking-widest">Date</th>
                            <th class="px-6 py-4 text-right text-[11px] font-bold text-gray-500 uppercase tracking-widest no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($payments as $payment)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 no-print">
                                <input type="checkbox" class="row-checkbox w-4 h-4 text-primary border-gray-300 rounded" value="{{ $payment->id }}">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-primary">{{ $payment->payment_id }}</td>
                            <td class="px-6 py-4 min-w-[220px]">
                                <div class="text-sm font-semibold text-gray-900">{{ $payment->reference ?: ($payment->sale->invoice_no ?? $payment->sale->order_number ?? 'Payment record') }}</div>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $payment->sale->customer_name ?? optional($payment->sale->customer)->name ?? ($payment->note ?: 'No extra note') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $payment->method ?: 'Not set' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-gray-900">{{ $currencySymbol }}{{ number_format($payment->amount, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $rowStatus = $payment->resolved_status ?? $payment->status ?? 'Pending';
                                    $rowStatusKey = strtolower((string) $rowStatus);
                                    $rowStatusClass = match ($rowStatusKey) {
                                        'completed' => 'bg-green-100 text-green-700',
                                        'partial' => 'bg-blue-100 text-blue-700',
                                        'failed', 'cancelled', 'canceled' => 'bg-red-100 text-red-700',
                                        default => 'bg-yellow-100 text-yellow-700',
                                    };
                                @endphp
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-black uppercase {{ $rowStatusClass }}">
                                    {{ $rowStatus }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-medium">
                                {{ \Carbon\Carbon::parse($payment->created_at)->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right no-print">
                                <div class="flex justify-end space-x-1">
                                    <button onclick="viewPayment({{ $payment->id }})" class="p-2 text-gray-400 hover:text-indigo-600 transition hover:bg-indigo-50 rounded-lg" title="View Receipt"><i class="fe fe-eye"></i></button>
                                    <button onclick="editPayment({{ $payment->id }})" class="p-2 text-gray-400 hover:text-green-600 transition hover:bg-green-50 rounded-lg" title="Edit Record"><i class="fe fe-edit"></i></button>
                                    <button onclick="deleteRow({{ $payment->id }})" class="p-2 text-gray-400 hover:text-red-600 transition hover:bg-red-50 rounded-lg" title="Delete"><i class="fe fe-trash-2"></i></button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="px-6 py-20 text-center text-gray-400 font-medium italic">No transactions found matching your search.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 no-print">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="text-xs font-bold text-gray-400 tracking-widest uppercase">
                        Showing {{ $showingFrom }} - {{ $showingTo }} of {{ $payments->total() }} entries
                    </div>
                    <div class="bs-pagination">
                        {{ $payments->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="toast" class="hidden fixed bottom-8 right-8 z-[100] bg-gray-900 text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center space-x-3 transition-all duration-300 animate-slide-up">
    <div class="bg-green-500 rounded-full p-1"><i class="fe fe-check text-white"></i></div>
    <p id="toast-message" class="text-sm font-bold tracking-wide"></p>
</div>

<div id="paymentModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-6 md:p-8 overflow-hidden transition-all">
            
            <div class="flex justify-between items-center mb-6 no-print">
                <h3 class="text-xl font-bold text-gray-900" id="modalTitle">Details</h3>
                <div class="flex space-x-2">
                    <button onclick="closeModal()" class="w-8 h-8 flex items-center justify-center bg-gray-100 rounded-full text-gray-400 hover:text-gray-600 transition"><i class="fe fe-x"></i></button>
                </div>
            </div>

            <div id="receipt-view" class="receipt-sheet hidden pt-2 border-t border-gray-100">
                <div class="receipt-actions no-print flex justify-end mb-5">
                    <button onclick="printReceipt()" id="printReceiptBtn" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 text-white rounded-xl text-xs font-black uppercase tracking-[0.18em] shadow-lg shadow-gray-900/10 hover:bg-black transition">
                        <i class="fe fe-printer text-sm"></i>
                        <span>Print Receipt</span>
                    </button>
                </div>
                <div class="receipt-topband mb-5 rounded-2xl px-4 py-3">
                    <div class="text-[10px] font-bold uppercase tracking-[0.28em] text-indigo-700">Transaction Receipt</div>
                    <div class="text-xs text-slate-500 mt-1">Verified payment summary for your records</div>
                </div>
                <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4 mb-5">
                    <div>
                        <div class="text-[2rem] font-black text-gray-900 tracking-tight">Receipt</div>
                        <div class="mt-2 text-[11px] text-gray-400 font-bold uppercase tracking-[0.25em]">Payment ID</div>
                        <div id="rec_id" class="text-sm font-bold text-gray-900 mt-1"></div>
                        <div class="mt-4 text-[11px] text-gray-400 font-bold uppercase tracking-[0.25em]">Customer</div>
                        <div id="rec_customer" class="text-sm font-semibold text-gray-800 mt-1"></div>
                    </div>
                    <div class="md:text-right">
                        <div class="text-[11px] text-gray-400 font-bold uppercase tracking-[0.25em]">Transaction Record</div>
                        <div id="rec_date_full" class="text-sm text-gray-600 font-semibold mt-1"></div>
                        <div class="mt-4 text-[11px] text-gray-400 font-bold uppercase tracking-[0.25em]">Reference</div>
                        <div id="rec_reference" class="text-sm font-semibold text-gray-800 mt-1"></div>
                    </div>
                </div>

                <div class="receipt-panel bg-gray-50 rounded-2xl p-4 md:p-5 mb-5 border border-gray-200 relative overflow-hidden">
                    <div id="paid-stamp" class="receipt-stamp absolute top-5 right-5 border-2 border-green-500/30 text-green-500/35 font-black px-3 py-1 rotate-12 rounded-lg pointer-events-none uppercase">Paid</div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
                        <div class="receipt-fact">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.25em]">Method</p>
                            <p id="rec_method" class="text-base font-bold text-gray-900 mt-1"></p>
                        </div>
                        <div class="receipt-fact">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.25em]">Status</p>
                            <p id="rec_status_text" class="text-base font-bold text-gray-900 mt-1"></p>
                        </div>
                        <div class="receipt-fact">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.25em]">Account</p>
                            <p id="rec_account" class="text-sm font-semibold text-gray-800 mt-1"></p>
                        </div>
                        <div class="receipt-fact xl:text-right">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.25em]">Processed By</p>
                            <p id="rec_processed_by" class="text-sm font-semibold text-gray-800 mt-1"></p>
                        </div>
                    </div>

                    <div class="border-t border-dashed border-gray-200 my-4"></div>

                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.25em]">Total Paid</p>
                            <p class="text-[0.92rem] font-semibold text-gray-500 mt-1">Amount received and recorded</p>
                        </div>
                        <div class="text-right">
                            <p id="rec_amount" class="receipt-amount font-black text-primary leading-none"></p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div class="bg-white border border-gray-200 rounded-2xl px-4 py-4">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.25em] mb-1">Invoice / Order</p>
                        <p id="rec_invoice" class="text-sm font-semibold text-gray-800"></p>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl px-4 py-4">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.25em] mb-1">Payment Source</p>
                        <p id="rec_source" class="text-sm font-semibold text-gray-800"></p>
                    </div>
                </div>

                <div class="mb-5">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.25em] mb-2">Notes / Description</p>
                    <div class="bg-white border border-gray-200 rounded-2xl px-4 py-4">
                        <p id="rec_note" class="text-sm text-gray-600 italic leading-relaxed"></p>
                    </div>
                </div>

                <div class="text-center text-[10px] text-gray-400 font-bold uppercase tracking-widest pt-4 border-t border-gray-100">
                    Thank you for your business
                </div>
            </div>

            <form id="editForm" onsubmit="savePayment(event)" class="hidden">
                <input type="hidden" id="edit_id">
                <div class="space-y-5">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">System Payment ID</label>
                        <input type="text" id="view_payment_id" disabled class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-2.5 text-sm font-bold text-primary">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Method</label>
                            <input type="text" id="edit_method" class="w-full border-gray-300 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-primary/20 transition">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Amount (₦)</label>
                            <input type="number" step="0.01" id="edit_amount" class="w-full border-gray-300 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-primary/20 transition">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Status</label>
                        <select id="edit_status" class="w-full border-gray-300 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-primary/20 transition">
                            <option value="Completed">Completed</option>
                            <option value="Pending">Pending</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Note/Reference</label>
                        <textarea id="edit_reference" rows="3" class="w-full border-gray-300 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-primary/20 transition"></textarea>
                    </div>
                    <div class="flex space-x-3 pt-4">
                        <button type="button" onclick="closeModal()" class="flex-1 px-4 py-3 bg-gray-100 text-gray-600 rounded-xl font-bold hover:bg-gray-200 transition text-xs uppercase">Cancel</button>
                        <button type="submit" class="flex-1 px-4 py-3 bg-primary text-white rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-primary/20 transition text-xs uppercase tracking-widest">Update Record</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    @media print { 
        .no-print { display: none !important; }
        body * { visibility: hidden; }
        body.receipt-print-active #paymentModal,
        body.receipt-print-active #paymentModal * { visibility: visible; }
        body.receipt-print-active #paymentModal { display: block !important; position: absolute; left: 0; top: 0; width: 100%; height: auto; margin: 0; padding: 0; background: white; overflow: visible !important; }
        body.receipt-print-active #paymentModal > div { display: block !important; min-height: auto !important; padding: 0 !important; }
        body.receipt-print-active #paymentModal .fixed { display: none !important; }
        body.receipt-print-active #paymentModal .relative { max-width: none !important; width: 100% !important; padding: 0 !important; border-radius: 0 !important; box-shadow: none !important; overflow: visible !important; }
        body.receipt-print-active #receipt-view { display: block !important; border-top: 0 !important; padding-top: 0 !important; }
        body.receipt-print-active .receipt-panel { background: linear-gradient(135deg, #f4f7ff 0%, #ecfdf3 100%) !important; border-color: #d6e3f5 !important; }
        body.receipt-print-active .receipt-stamp { font-size: 0.95rem !important; top: 1rem !important; right: 1rem !important; background: rgba(240,253,244,0.98) !important; color: #16a34a !important; border-color: rgba(22,163,74,0.45) !important; }
        body.receipt-print-active .receipt-amount { font-size: 2.2rem !important; color: #4338ca !important; }
        body.receipt-print-active .receipt-sheet .text-3xl { font-size: 2rem !important; }
        body.receipt-print-active .receipt-sheet .text-base { font-size: 0.95rem !important; }
        body.receipt-print-active .receipt-sheet .text-sm { font-size: 0.88rem !important; }
        .page-wrapper { background: white !important; }
    }
    .summary-metric-card {
        min-height: 220px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        overflow: hidden;
    }
    .report-page {
        font-size: 0.95rem;
    }
    .summary-metric-label {
        line-height: 1.45;
    }
    .summary-metric-value {
        font-size: clamp(1.75rem, 2vw, 2.3rem);
        line-height: 1;
        letter-spacing: -0.04em;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .summary-metric-note {
        max-width: 14rem;
        line-height: 1.35;
        font-size: 0.95rem;
    }
    .receipt-sheet {
        color: #0f172a;
    }
    .receipt-topband {
        background: linear-gradient(135deg, rgba(79, 70, 229, 0.08) 0%, rgba(14, 165, 233, 0.06) 100%);
        border: 1px solid rgba(99, 102, 241, 0.12);
    }
    .receipt-panel {
        background: linear-gradient(135deg, #f8f9ff 0%, #eefaf4 100%);
        border-color: #d7e4f6;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
    }
    .receipt-fact p:last-child {
        line-height: 1.35;
    }
    .receipt-stamp {
        letter-spacing: 0.08em;
        font-size: 0.82rem;
        background: rgba(240,253,244,0.9);
    }
    .receipt-amount {
        letter-spacing: -0.04em;
        overflow-wrap: anywhere;
        word-break: break-word;
        font-size: clamp(1.7rem, 2.1vw, 2.15rem);
    }
    #main-payment-table td,
    #main-payment-table th {
        font-size: 0.92rem;
    }
    .bs-pagination .pagination { display: flex; gap: 6px; list-style: none; margin: 0; }
    .bs-pagination .page-item .page-link { border-radius: 12px !important; padding: 8px 16px; font-weight: 800; color: #4f46e5; border: 1px solid #e5e7eb; font-size: 0.75rem; transition: all 0.2s; background: white; }
    .bs-pagination .page-item.active .page-link { background-color: #4f46e5 !important; border-color: #4f46e5 !important; color: white !important; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2); }
    @media (max-width: 767.98px) {
        .summary-metric-card {
            min-height: 180px;
        }
        .summary-metric-value {
            font-size: 1.85rem;
        }
        .summary-metric-note {
            max-width: 100%;
        }
        .receipt-sheet .text-\[2rem\] {
            font-size: 1.75rem !important;
        }
        .receipt-stamp {
            font-size: 0.68rem;
            top: 1rem;
            right: 1rem;
        }
        .receipt-amount {
            font-size: 1.4rem !important;
        }
    }
</style>

<script>
    const paymentApi = {
        show: "{{ route('reports.payments.show', ['id' => '__ID__']) }}",
        update: "{{ route('reports.payments.update', ['id' => '__ID__']) }}",
        destroy: "{{ route('reports.payments.destroy', ['id' => '__ID__']) }}",
        bulk: "{{ route('reports.payments.bulk-update') }}",
    };
    let isReceiptPrinting = false;

    function paymentUrl(key, id) {
        return paymentApi[key].replace('__ID__', String(id));
    }

    // 1. TOAST & MODAL CORE
    function showToast(msg) {
        const toast = document.getElementById('toast');
        document.getElementById('toast-message').innerText = msg;
        toast.classList.remove('hidden');
        setTimeout(() => { toast.classList.add('hidden'); window.location.reload(); }, 2000);
    }

    function closeModal() { document.getElementById('paymentModal').classList.add('hidden'); }

    window.addEventListener('afterprint', function () {
        document.body.classList.remove('receipt-print-active');
        isReceiptPrinting = false;
    });

    function printReceipt() {
        const modal = document.getElementById('paymentModal');
        const receipt = document.getElementById('receipt-view');
        if (!modal || !receipt || receipt.classList.contains('hidden') || isReceiptPrinting) {
            return;
        }
        isReceiptPrinting = true;
        document.body.classList.add('receipt-print-active');
        setTimeout(() => {
            window.print();
        }, 80);
    }

    function openModal(id, mode) {
        fetch(paymentUrl('show', id), { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                const isView = mode === 'view';
                
                // Populate Receipt Mode
                document.getElementById('rec_id').innerText = data.payment_id;
                document.getElementById('rec_method').innerText = data.method || 'Not specified';
                document.getElementById('rec_amount').innerText = '₦' + parseFloat(data.amount).toLocaleString(undefined, {minimumFractionDigits: 2});
                const resolvedStatus = data.resolved_status || data.status || 'Pending';
                document.getElementById('rec_status_text').innerText = resolvedStatus;
                document.getElementById('rec_note').innerText = data.reference || data.note || 'No notes available.';
                document.getElementById('rec_customer').innerText = data.sale?.customer_name || data.sale?.customer?.name || 'Walk-in customer';
                document.getElementById('rec_reference').innerText = data.reference || data.payment_id || 'N/A';
                document.getElementById('rec_invoice').innerText = data.sale?.invoice_no || data.sale?.order_number || 'No linked invoice';
                document.getElementById('rec_account').innerText = data.account?.name || data.account?.account_name || 'Unassigned account';
                document.getElementById('rec_processed_by').innerText = data.creator?.name || 'System';
                document.getElementById('rec_source').innerText = data.sale ? 'POS / Sales Payment' : 'Direct Payment Entry';
                document.getElementById('rec_date_full').innerText = new Date(data.created_at).toLocaleString('en-NG', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                // Populate Edit Mode
                document.getElementById('edit_id').value = data.id;
                document.getElementById('view_payment_id').value = data.payment_id;
                document.getElementById('edit_method').value = data.method;
                document.getElementById('edit_amount').value = data.amount;
                document.getElementById('edit_status').value = data.status || resolvedStatus;
                document.getElementById('edit_reference').value = data.reference || data.note || '';

                // UI Toggle
                document.getElementById('receipt-view').classList.toggle('hidden', !isView);
                document.getElementById('editForm').classList.toggle('hidden', isView);
                document.getElementById('printReceiptBtn').classList.toggle('hidden', !isView);
                document.getElementById('modalTitle').innerText = isView ? "Payment Receipt" : "Edit Transaction";
                
                // Stamp Styling
                const stamp = document.getElementById('paid-stamp');
                stamp.innerText = resolvedStatus;
                const isDone = String(resolvedStatus).toLowerCase() === 'completed';
                stamp.style.borderColor = isDone ? '#22c55e' : '#eab308';
                stamp.style.color = isDone ? '#22c55e' : '#eab308';

                document.getElementById('paymentModal').classList.remove('hidden');
            })
            .catch(() => showToast('Unable to load payment details right now.'));
    }

    // 2. ACTIONS (VIEW, EDIT, DELETE)
    function viewPayment(id) { openModal(id, 'view'); }
    function editPayment(id) { openModal(id, 'edit'); }
    
    function savePayment(e) {
        e.preventDefault();
        const id = document.getElementById('edit_id').value;
        const payload = {
            method: document.getElementById('edit_method').value,
            amount: document.getElementById('edit_amount').value,
            status: document.getElementById('edit_status').value,
            reference: document.getElementById('edit_reference').value,
        };
        fetch(paymentUrl('update', id), {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            closeModal();
            showToast(data.message || 'Payment updated.');
        })
        .catch(() => showToast('Unable to update payment.'));
    }

    function deleteRow(id) {
        if(confirm('Permanently delete this payment?')) {
            fetch(paymentUrl('destroy', id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(d => showToast(d.message || 'Payment deleted.'))
            .catch(() => showToast('Unable to delete payment.'));
        }
    }

    // 3. BULK SELECTION LOGIC
    const selectAll = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkBar = document.getElementById('bulk-action-bar');
    const countDisplay = document.getElementById('selected-count');

    function toggleBulk() {
        const count = document.querySelectorAll('.row-checkbox:checked').length;
        countDisplay.innerText = count;
        count > 0 ? bulkBar.classList.remove('hidden') : bulkBar.classList.add('hidden');
    }
    selectAll.addEventListener('change', e => { rowCheckboxes.forEach(cb => cb.checked = e.target.checked); toggleBulk(); });
    rowCheckboxes.forEach(cb => cb.addEventListener('change', toggleBulk));

    function applyBulkAction() {
        const ids = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
        const status = document.getElementById('bulk-status').value;
        if(!status) return;
        if(confirm(`Are you sure you want to perform bulk ${status}?`)) {
            fetch(paymentApi.bulk, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ ids, status })
            }).then(r => r.json()).then(d => showToast(d.message || 'Bulk action completed.'));
        }
    }

    // 4. EXPORT LOGIC
    function generatePDF() { html2pdf().set({ margin:0.5, filename:'Report.pdf', jsPDF:{orientation:'landscape'} }).from(document.getElementById('report-container')).save(); }
    function generateExcel() { XLSX.writeFile(XLSX.utils.table_to_book(document.getElementById("main-payment-table")), "Report.xlsx"); }
    function generateCSV() { 
        const csv = XLSX.utils.sheet_to_csv(XLSX.utils.table_to_sheet(document.getElementById("main-payment-table")));
        const link = document.createElement("a");
        link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
        link.download = "Report.csv"; link.click();
    }
</script>
@endsection
