@extends('layout.app')
@section('title', 'Payroll History')

@section('content')
<style>
:root { --blue-deep:#002347; --gold:#c5a059; --gold-bright:#ffdf91; --red:#bc002d; }
.payroll-shell { width:100%; max-width:1560px; margin:0 auto; padding:1.5rem 0.75rem; min-width:0; overflow-x:hidden; }
.payroll-shell .row { margin-left:0; margin-right:0; }
.payroll-shell .row > * { padding-left:calc(var(--bs-gutter-x, 1.5rem) * 0.5); padding-right:calc(var(--bs-gutter-x, 1.5rem) * 0.5); }
.page-header { background:linear-gradient(135deg,var(--blue-deep),#003d6b); border-radius:16px; padding:28px 32px; color:white; margin-bottom:28px; }
.page-header h1 { font-size:1.5rem; font-weight:800; margin:0; color:#ffffff; }
.table-wrap { background:#fff; border:1px solid #e8ecf4; border-radius:14px; overflow-x:auto; overflow-y:hidden; box-shadow:0 2px 12px rgba(0,35,71,0.05); }
.table-header { padding:20px 24px; border-bottom:1px solid #e8ecf4; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.table-header h6 { font-weight:800; color:var(--blue-deep); margin:0; }
.data-table { width:100%; border-collapse:collapse; }
.data-table th { padding:12px 16px; font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#8a92a0; border-bottom:1px solid #e8ecf4; background:#f8faff; text-align:left; }
.data-table td { padding:14px 16px; border-bottom:1px solid #f0f4f8; font-size:0.87rem; color:#3d4a5c; vertical-align:middle; }
.data-table tr:last-child td { border-bottom:none; }
.data-table tr:hover td { background:#f8faff; }
.status-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 12px; border-radius:20px; font-size:0.7rem; font-weight:800; }
.status-completed { background:#dcfce7; color:#15803d; }
.status-processing { background:#dbeafe; color:#1d4ed8; }
.status-failed { background:#fee2e2; color:#991b1b; }
.status-draft { background:#f1f5f9; color:#64748b; }
.btn-gold { background:linear-gradient(135deg,var(--gold),var(--gold-bright)); color:var(--blue-deep)!important; border:none; padding:10px 22px; font-weight:800; border-radius:8px; font-size:0.8rem; text-transform:uppercase; letter-spacing:1px; transition:all 0.3s; text-decoration:none; display:inline-flex; align-items:center; gap:7px; }
.btn-outline { background:transparent; color:var(--blue-deep)!important; border:1.5px solid #e8ecf4; padding:8px 16px; font-weight:700; border-radius:8px; font-size:0.78rem; transition:all 0.3s; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
.btn-outline:hover { border-color:var(--gold); color:var(--gold)!important; }
@media(max-width:768px){
    .page-header h1{font-size:1.2rem;}
    .btn-gold, .btn-outline { width:100%; justify-content:center; }
    .table-header { align-items:stretch; }
}
@media(min-width:768px){ .payroll-shell{ padding-left:1rem; padding-right:1rem; } }
</style>

<div class="page-wrapper">
<div class="payroll-shell">

    <div class="page-header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('payroll.index') }}" style="color:rgba(255,255,255,0.7);text-decoration:none;"><i class="fas fa-arrow-left"></i></a>
                <div>
                    <h1><i class="fas fa-history me-2" style="color:var(--gold-bright);"></i>Payroll History</h1>
                    <p style="color:rgba(255,255,255,0.7);margin:6px 0 0;font-size:0.88rem;">All processed payroll runs</p>
                </div>
            </div>
            <a href="{{ route('payroll.run') }}" class="btn-gold"><i class="fas fa-plus"></i> New Run</a>
        </div>
    </div>

    <div class="table-wrap">
        <div class="table-header">
            <h6><i class="fas fa-list me-2" style="color:var(--gold);"></i>All Payroll Runs</h6>
            <a href="{{ route('payroll.export') }}" class="btn-outline"><i class="fas fa-download"></i> Export</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Period</th>
                        <th>Pay Date</th>
                        <th>Staff Paid</th>
                        <th>Total Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Processed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($runs as $index => $run)
                    <tr>
                        <td style="color:#8a92a0;font-weight:700;">{{ $runs->firstItem() + $index }}</td>
                        <td style="font-weight:700;color:var(--blue-deep);">{{ $run->period }}</td>
                        <td>{{ \Carbon\Carbon::parse($run->pay_date)->format('d M Y') }}</td>
                        <td>{{ $run->staff_count }} staff</td>
                        <td style="font-weight:800;color:var(--blue-deep);">₦{{ number_format($run->total_amount) }}</td>
                        <td style="text-transform:capitalize;">{{ str_replace('_', ' ', $run->payment_method ?? 'bank transfer') }}</td>
                        <td><span class="status-badge status-{{ strtolower($run->status) }}">{{ ucfirst($run->status) }}</span></td>
                        <td style="color:#8a92a0;font-size:0.8rem;">{{ $run->created_at->format('d M Y H:i') }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('payroll.export', ['run_id' => $run->id]) }}" class="btn-outline" style="padding:5px 10px;font-size:0.72rem;" title="Download">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5" style="color:#8a92a0;">
                            <i class="fas fa-history" style="font-size:2rem;display:block;margin-bottom:10px;opacity:0.3;"></i>
                            No payroll runs yet.<br>
                            <a href="{{ route('payroll.run') }}" class="btn-gold mt-3 d-inline-flex"><i class="fas fa-play"></i> Run First Payroll</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($runs->hasPages())
        <div class="p-3 border-top d-flex justify-content-center">{{ $runs->links() }}</div>
        @endif
    </div>
</div>
</div>
@endsection
