@extends('layout.mainlayout')

@section('page-title', 'Database Error')

@section('content')
<div class="sb-shell">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="sb-card p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="text-danger" style="font-size: 28px;">
                        <i class="fas fa-database"></i>
                    </div>
                    <div>
                        <h4 class="mb-2">Database Connection Error</h4>
                        <p class="text-muted mb-2">
                            {{ $message ?? 'We could not connect to the database at the moment.' }}
                        </p>
                        @isset($timestamp)
                            <p class="small text-muted mb-0">Time: {{ $timestamp }}</p>
                        @endisset
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
