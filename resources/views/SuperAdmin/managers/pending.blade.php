@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Manager Verifications</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('super_admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Pending Approvals</li>
                    </ul>
                </div>
                <div class="col-auto float-right ml-auto">

                    <button onclick="window.print();" class="btn btn-white border shadow-sm">
                        <i class="fas fa-print"></i> Print Verification List
                    </button>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped custom-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Manager Name</th>
                                        <th>Email</th>
                                        <th>Joined Date</th>
                                        <th>Status</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pending as $manager)
                                    <tr>
                                        <td>
                                            <h2 class="table-avatar">
                                                <a href="#">{{ $manager->manager_name ?? $manager->business_name ?? 'Deployment Manager' }} <span>Deployment Manager</span></a>
                                            </h2>
                                        </td>
                                        <td>{{ $manager->email }}</td>
                                        <td>{{ $manager->created_at->format('d M, Y') }}</td>
                                        <td>
                                            <span class="badge badge-warning-border">Awaiting Review</span>
                                        </td>
                                        <td class="text-right">
                                            <form action="{{ route('super_admin.managers.approve', $manager->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success btn-rounded shadow-sm">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>

                                            <button type="button" class="btn btn-sm btn-danger btn-rounded shadow-sm" data-toggle="modal" data-target="#rejectModal{{ $manager->id }}">
                                                <i class="fas fa-times"></i> Reject
                                            </button>

                                            <a href="{{ route('messages.index', ['type' => 'chat', 'user' => $manager->id]) }}" class="btn btn-sm btn-outline-primary btn-rounded shadow-sm">
                                                <i class="fas fa-comments"></i> Chat
                                            </a>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="rejectModal{{ $manager->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reject Manager Application</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <form action="{{ route('super_admin.managers.reject', $manager->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label>Reason for Rejection <span class="text-danger">*</span></label>
                                                            <textarea name="reason" class="form-control" rows="4" placeholder="This reason will be sent to the manager via chat/email..." required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center p-5">
                                            <img src="{{ asset('assets/images/empty-state.png') }}" style="width: 150px;" alt="No pending managers">
                                            <p class="mt-3 text-muted">No managers are currently awaiting verification for <strong>{{ env('SESSION_DOMAIN', 'the system') }}</strong>.</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .badge-warning-border {
        border: 1px solid #ffbc34;
        color: #ffbc34;
        background: transparent;
        padding: 5px 10px;
    }
    .btn-rounded { border-radius: 50px; }

    @media print {
        .btn, .sidebar, .header, .text-right, .modal, .breadcrumb { display: none !important; }
        .page-wrapper { margin-left: 0 !important; padding: 0 !important; }
        .card { border: 1px solid #ddd !important; box-shadow: none !important; }
    }
</style>
@endsection
