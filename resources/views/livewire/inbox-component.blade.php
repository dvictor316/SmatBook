<div> 
    <div class="col-lg-12">
        <div class="card bg-white border-0 shadow-sm" style="border-radius: 15px;">
            <div class="card-body">

                <div class="email-header">
                    <div class="row align-items-center">
                        <div class="col top-action-left">
                            <div class="d-flex align-items-center gap-3">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-light text-dark dropdown-toggle border" data-bs-toggle="dropdown">
                                        Select
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="javascript:void(0);">All</a>
                                        <a class="dropdown-item" href="javascript:void(0);">Read</a>
                                        <a class="dropdown-item" href="javascript:void(0);">Unread</a>
                                    </div>
                                </div>

                                <div class="mail-search flex-grow-1">
                                    <div class="input-group">
                                        <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-search text-muted"></i></span>
                                        <input type="text" wire:model.live.debounce.500ms="search" placeholder="Search in {{ $folder }}..." class="form-control border-start-0 ps-0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-auto">
                            <span class="text-muted small fw-bold">Showing {{ $messages->count() }} of {{ $messages->total() }}</span>
                        </div>
                    </div>
                </div>

                <div class="email-content mt-3">
                    <div class="table-responsive">
                        <table class="table table-inbox table-hover border-top">
                            <thead>
                                <tr class="bg-light">
                                    <th style="width: 40px;" class="ps-3">
                                        <input type="checkbox" class="form-check-input">
                                    </th>
                                    <th colspan="4" class="py-3">
                                        <span class="text-uppercase small text-muted fw-bold">Message Details</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($messages as $message)
                                    <tr class="{{ $message->read_at ? 'text-muted' : 'fw-bold bg-soft-primary' }}" 
                                        style="cursor: pointer; transition: all 0.2s;">

                                        <td class="text-center ps-3">
                                            <input type="checkbox" class="form-check-input me-2">
                                        </td>

                                        <td class="star-col" style="width: 30px;">
                                            <span class="mail-important">
                                                <i class="far fa-star text-warning"></i>
                                            </span>
                                        </td>

                                        <td class="name" style="width: 200px;">
                                            {{ $folder == 'sent' ? 'To: ' . ($message->receiver->name ?? 'User') : ($message->sender->name ?? 'System') }}
                                        </td>

                                        <td class="subject">
                                            <span class="badge bg-soft-info text-info me-2">{{ ucfirst($folder) }}</span>
                                            <strong>{{ $message->subject }}</strong> 
                                            <span class="text-muted fw-normal">- {{ Str::limit($message->content, 100) }}</span>
                                        </td>

                                        <td class="mail-date text-end pe-3 text-nowrap">
                                            {{ $message->created_at->diffForHumans() }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="py-4">
                                                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="60" class="opacity-25 mb-3">
                                                <h6 class="text-muted">No messages found in {{ $folder }}</h6>
                                                <small class="text-muted">When you receive messages, they will appear here.</small>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-center">
                    {{ $messages->links() }}
                </div>
            </div>
        </div>
    </div>

    <style>
        .bg-soft-primary { background-color: rgba(0, 123, 255, 0.03); border-left: 3px solid #007bff; }
        .table-inbox tr:hover { transform: scale(1.002); box-shadow: 0 4px 10px rgba(0,0,0,0.05); z-index: 10; }
        .mail-search .form-control:focus { box-shadow: none; border-color: #dee2e6; }

        @media print {
            .email-header, .star-col, .form-check-input, .pagination { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .table-responsive { overflow: visible !important; }
        }
    </style>
</div>