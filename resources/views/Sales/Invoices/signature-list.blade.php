@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper">
    <div class="content container-fluid">

        {{-- Page Header --}}
        <div class="page-header">
            <div class="content-page-header">
                <h5>Signature List</h5>
                <div class="list-btn">
                    <ul class="filter-list">
                        <li>
                            <a class="btn btn-primary" href="javascript:window.print();">
                                <i class="fa fa-print me-2"></i>Print List
                            </a>
                        </li>
                        <li>
                            <a class="btn btn-success" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#add_signature">
                                <i class="fa fa-plus-circle me-2"></i>Add Signature
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Table Section --}}
        <div class="row">
            <div class="col-sm-12">
                <div class="card-table">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-center table-hover datatable">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Signature Name</th>
                                        <th>Preview</th>
                                        <th>Status</th>
                                        <th class="no-print text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($signatures as $key => $signature)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td><strong>{{ $signature->name }}</strong></td>
                                            <td>
                                                <img src="{{ $signature->image_url }}" alt="sign" 
                                                     class="img-thumbnail" style="width: 100px; height: auto; background: #f8f8f8;">
                                            </td>
                                            <td>
                                                <span class="badge {{ $signature->status === 'active' ? 'bg-success-light' : 'bg-danger-light' }}">
                                                    {{ ucfirst($signature->status) }}
                                                </span>
                                            </td>
                                            <td class="no-print text-end">
                                                <div class="dropdown dropdown-action">
                                                    <a href="#" class="btn-action-icon" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></a>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        <a class="dropdown-item" href="javascript:void(0);"><i class="far fa-edit me-2"></i>Edit</a>
                                                        <form action="#" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="dropdown-item"><i class="far fa-trash-alt me-2"></i>Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No signatures found. Click "Add Signature" to begin.</td>
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

{{-- Add Signature Modal --}}
<div class="modal custom-modal fade" id="add_signature" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="form-header modal-header-title text-start mb-0">
                    <h4 class="mb-0">Upload New Signature</h4>
                </div>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('signature.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label>Display Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Accounts Dept Signature" required>
                    </div>
                    <div class="form-group">
                        <label>Signature Image</label>
                        <div class="custom-file">
                            <input type="file" name="signature_image" class="form-control" accept="image/png, image/jpeg" required>
                            <small class="text-muted">Recommended: Transparent PNG, 300x150px.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" data-bs-dismiss="modal" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    @media print {
        .header, .sidebar, .list-btn, .no-print, .dropdown-action {
            display: none !important;
        }
        .page-wrapper { margin: 0 !important; padding: 0 !important; }
        .img-thumbnail { border: none !important; }
    }
</style>
@endsection