@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper customer-profile-page">
    <div class="content container-fluid">
        @php
            $statusClass = ($customer->status ?? '') === 'active' ? 'is-active' : 'is-inactive';
            $profileImage = $customer->image ? asset('storage/' . $customer->image) : asset('assets/img/profiles/avatar-14.jpg');
        @endphp

        @if (session('success'))
            <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">
                <div class="fw-semibold mb-1">Please review the customer details form.</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <style>
            .customer-profile-page {
                --cp-navy: #102a5c;
                --cp-blue: #1d4ed8;
                --cp-sky: #eff6ff;
                --cp-accent: #f97316;
                --cp-violet: #6d28d9;
                --cp-emerald: #16a34a;
                --cp-surface: #ffffff;
                --cp-border: #e5edf7;
                --cp-muted: #64748b;
                --cp-text: #0f172a;
            }
            .customer-profile-shell {
                display: grid;
                gap: 24px;
            }
            .customer-profile-topbar {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 16px;
                flex-wrap: wrap;
            }
            .customer-profile-title {
                margin: 0;
                color: var(--cp-text);
                font-size: clamp(1.8rem, 3vw, 2.5rem);
                font-weight: 800;
                line-height: 1.08;
            }
            .customer-profile-title span {
                color: var(--cp-violet);
            }
            .customer-profile-sub {
                color: var(--cp-muted);
                margin-top: 8px;
                max-width: 700px;
            }
            .customer-profile-actions {
                display: flex;
                gap: 12px;
                flex-wrap: wrap;
            }
            .customer-profile-card,
            .customer-profile-bank,
            .customer-profile-notes {
                background: var(--cp-surface);
                border: 1px solid var(--cp-border);
                border-radius: 26px;
                box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
            }
            .customer-profile-card {
                padding: 28px;
            }
            .customer-profile-grid {
                display: grid;
                grid-template-columns: 1.15fr 0.85fr;
                gap: 24px;
            }
            .customer-profile-summary {
                display: grid;
                grid-template-columns: 1.2fr repeat(3, minmax(0, 1fr));
                gap: 18px;
                align-items: stretch;
            }
            .customer-hero {
                display: flex;
                gap: 18px;
                align-items: center;
                min-width: 0;
            }
            .customer-avatar-wrap {
                position: relative;
                width: 96px;
                height: 96px;
                flex-shrink: 0;
            }
            .customer-avatar {
                width: 100%;
                height: 100%;
                border-radius: 28px;
                overflow: hidden;
                border: 4px solid #fff;
                box-shadow: 0 18px 36px rgba(29, 78, 216, 0.14);
                background: #dbeafe;
            }
            .customer-avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .customer-avatar-edit {
                position: absolute;
                right: -4px;
                bottom: -4px;
                width: 34px;
                height: 34px;
                border-radius: 50%;
                border: 0;
                background: linear-gradient(135deg, var(--cp-violet), #8b5cf6);
                color: #fff;
                box-shadow: 0 10px 22px rgba(109, 40, 217, 0.24);
            }
            .customer-name {
                margin: 0 0 6px;
                color: var(--cp-text);
                font-size: 1.65rem;
                font-weight: 800;
                line-height: 1.1;
            }
            .customer-status {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                border-radius: 999px;
                padding: 8px 14px;
                font-weight: 700;
                font-size: 0.86rem;
            }
            .customer-status.is-active {
                background: #dcfce7;
                color: #15803d;
            }
            .customer-status.is-inactive {
                background: #fee2e2;
                color: #b91c1c;
            }
            .customer-pill {
                border-radius: 22px;
                padding: 18px 20px;
                background: linear-gradient(180deg, #f8fbff, #ffffff);
                border: 1px solid #e8eef8;
                min-width: 0;
            }
            .customer-pill-label {
                color: #94a3b8;
                font-size: 0.75rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                margin-bottom: 8px;
            }
            .customer-pill-value {
                color: var(--cp-text);
                font-size: 1rem;
                font-weight: 700;
                line-height: 1.55;
                word-break: break-word;
            }
            .customer-pill-value a {
                color: var(--cp-blue);
                text-decoration: none;
            }
            .customer-pill-id .customer-pill-value {
                color: var(--cp-violet);
                font-size: 1.6rem;
                font-weight: 800;
                line-height: 1.1;
            }
            .customer-address-card {
                padding: 24px;
            }
            .customer-address-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }
            .customer-section-title {
                display: flex;
                align-items: center;
                gap: 10px;
                color: var(--cp-text);
                font-size: 1.05rem;
                font-weight: 800;
                margin-bottom: 16px;
            }
            .customer-section-title.billing i {
                color: var(--cp-violet);
            }
            .customer-section-title.shipping i {
                color: var(--cp-accent);
            }
            .customer-address-box {
                background: linear-gradient(180deg, #fafcff, #ffffff);
                border: 1px solid #edf2f7;
                border-radius: 22px;
                padding: 20px;
                min-height: 180px;
            }
            .customer-address-name {
                color: var(--cp-text);
                font-weight: 800;
                margin-bottom: 10px;
            }
            .customer-address-lines {
                color: var(--cp-muted);
                line-height: 1.8;
                font-size: 0.95rem;
                margin: 0;
            }
            .customer-transaction-card {
                overflow: hidden;
            }
            .customer-transaction-head {
                padding: 22px 24px 0;
            }
            .customer-transaction-head h5 {
                margin: 0;
                color: var(--cp-text);
                font-size: 1.15rem;
                font-weight: 800;
            }
            .customer-transaction-head p {
                margin: 6px 0 0;
                color: var(--cp-muted);
            }
            .customer-transaction-table {
                margin: 18px 0 0;
            }
            .customer-transaction-table thead th {
                background: #eef4ff;
                color: #334155;
                border: 0;
                font-size: 0.78rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                padding: 14px 18px;
            }
            .customer-transaction-table tbody td {
                padding: 16px 18px;
                border-color: #edf2f7;
                vertical-align: middle;
            }
            .customer-bank-wrap {
                display: grid;
                gap: 24px;
            }
            .customer-profile-bank {
                padding: 26px;
                background: linear-gradient(160deg, #0f2f68 0%, #1d4ed8 58%, #3b82f6 100%);
                color: #fff;
                border: 0;
            }
            .customer-profile-bank .bank-title {
                display: flex;
                align-items: center;
                gap: 10px;
                color: rgba(255,255,255,0.82);
                font-size: 1rem;
                font-weight: 800;
                margin-bottom: 18px;
            }
            .bank-row + .bank-row {
                margin-top: 18px;
            }
            .bank-label {
                display: block;
                color: rgba(255,255,255,0.66);
                font-size: 0.82rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                margin-bottom: 7px;
            }
            .bank-value {
                display: block;
                color: #fff;
                font-size: 1.06rem;
                font-weight: 700;
                line-height: 1.5;
                word-break: break-word;
            }
            .customer-profile-notes {
                padding: 24px;
            }
            .customer-profile-notes h6 {
                color: var(--cp-text);
                font-size: 1rem;
                font-weight: 800;
                margin-bottom: 10px;
            }
            .customer-profile-notes p {
                color: var(--cp-muted);
                margin: 0;
                line-height: 1.8;
            }
            .customer-upload-preview {
                width: 140px;
                height: 140px;
                border-radius: 28px;
                overflow: hidden;
                border: 1px solid var(--cp-border);
                background: #eef4ff;
                margin-top: 12px;
            }
            .customer-upload-preview img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            @media (max-width: 1199.98px) {
                .customer-profile-summary {
                    grid-template-columns: 1fr 1fr;
                }
                .customer-hero {
                    grid-column: 1 / -1;
                }
            }
            @media (max-width: 991.98px) {
                .customer-profile-grid {
                    grid-template-columns: 1fr;
                }
            }
            @media (max-width: 767.98px) {
                .customer-profile-title {
                    font-size: 1.55rem;
                }
                .customer-profile-card,
                .customer-profile-bank,
                .customer-profile-notes {
                    border-radius: 22px;
                }
                .customer-profile-card {
                    padding: 18px;
                }
                .customer-profile-summary,
                .customer-address-grid {
                    grid-template-columns: 1fr;
                }
                .customer-profile-actions {
                    width: 100%;
                }
                .customer-profile-actions .btn {
                    flex: 1 1 auto;
                    justify-content: center;
                }
                .customer-hero {
                    align-items: flex-start;
                }
            }
        </style>

        <div class="customer-profile-shell">
            <div class="customer-profile-topbar no-print">
                <div>
                    <h1 class="customer-profile-title">Customer Profile: <span>{{ $customer->customer_name }}</span></h1>
                    <p class="customer-profile-sub">A clean customer dossier showing profile identity, contact records, billing and shipping data, banking details, and recent invoice activity.</p>
                </div>
                <div class="customer-profile-actions">
                    <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#customerProfileModal">
                        <i class="fe fe-edit me-2"></i>Edit Profile
                    </button>
                    <button onclick="window.print()" class="btn btn-light border rounded-pill px-4">
                        <i class="fe fe-printer me-2"></i>Print Dossier
                    </button>
                </div>
            </div>

            <div class="customer-profile-card">
                <div class="customer-profile-summary">
                    <div class="customer-hero">
                        <div class="customer-avatar-wrap">
                            <div class="customer-avatar">
                                <img src="{{ $profileImage }}" alt="{{ $customer->customer_name }}">
                            </div>
                            <button type="button" class="customer-avatar-edit no-print" data-bs-toggle="modal" data-bs-target="#customerProfileModal" aria-label="Edit customer image">
                                <i class="fe fe-camera"></i>
                            </button>
                        </div>
                        <div class="min-w-0">
                            <h2 class="customer-name">{{ $customer->customer_name }}</h2>
                            <span class="customer-status {{ $statusClass }}">{{ ucfirst($customer->status ?? 'inactive') }}</span>
                        </div>
                    </div>

                    <div class="customer-pill">
                        <div class="customer-pill-label">Contact Info</div>
                        <div class="customer-pill-value">
                            <div><i class="fe fe-mail me-2 text-primary"></i>{{ $customer->email ?: 'No email added' }}</div>
                            <div><i class="fe fe-phone me-2 text-success"></i>{{ $customer->phone ?: 'No phone added' }}</div>
                        </div>
                    </div>

                    <div class="customer-pill">
                        <div class="customer-pill-label">Financials</div>
                        <div class="customer-pill-value">
                            <div>Currency: {{ $customer->currency ?: '₦' }}</div>
                            <div>Website:
                                @if ($customer->website)
                                    <a href="{{ $customer->website }}" target="_blank" rel="noopener">{{ $customer->website }}</a>
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="customer-pill customer-pill-id">
                        <div class="customer-pill-label">Customer ID</div>
                        <div class="customer-pill-value">#CL-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</div>
                    </div>
                </div>
            </div>

            <div class="customer-profile-grid">
                <div class="d-grid gap-4">
                    <div class="customer-profile-card customer-address-card">
                        <div class="customer-address-grid">
                            <div class="customer-address-box">
                                <div class="customer-section-title billing"><i class="fe fe-map-pin"></i><span>Billing Address</span></div>
                                <div class="customer-address-name">{{ $customer->billing_name ?: $customer->customer_name }}</div>
                                <p class="customer-address-lines">
                                    {{ $customer->billing_address_line1 ?: '-' }}<br>
                                    {{ $customer->billing_address_line2 ?: '-' }}<br>
                                    {{ $customer->billing_city ?: '-' }}, {{ $customer->billing_state ?: '-' }}<br>
                                    {{ $customer->billing_country ?: '-' }} {{ $customer->billing_pincode ? '- ' . $customer->billing_pincode : '' }}
                                </p>
                            </div>
                            <div class="customer-address-box">
                                <div class="customer-section-title shipping"><i class="fe fe-truck"></i><span>Shipping Address</span></div>
                                <div class="customer-address-name">{{ $customer->shipping_name ?: $customer->customer_name }}</div>
                                <p class="customer-address-lines">
                                    {{ $customer->shipping_address_line1 ?: '-' }}<br>
                                    {{ $customer->shipping_address_line2 ?: '-' }}<br>
                                    {{ $customer->shipping_city ?: '-' }}, {{ $customer->shipping_state ?: '-' }}<br>
                                    {{ $customer->shipping_country ?: '-' }} {{ $customer->shipping_pincode ? '- ' . $customer->shipping_pincode : '' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="customer-profile-card customer-transaction-card">
                        <div class="customer-transaction-head">
                            <h5>Recent Transactions</h5>
                            <p>Latest invoice activity attached to this customer record.</p>
                        </div>
                        <div class="table-responsive">
                            <table class="table customer-transaction-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Invoice ID</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($invoices as $invoice)
                                        <tr>
                                            <td class="ps-4 fw-bold">#INV-{{ $invoice->id }}</td>
                                            <td>{{ $invoice->created_at->format('d M Y') }}</td>
                                            <td>{{ $customer->currency ?: '₦' }}{{ number_format($invoice->total_amount, 2) }}</td>
                                            <td>
                                                <span class="badge rounded-pill {{ $invoice->status === 'paid' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis' }}">
                                                    {{ ucfirst($invoice->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No recent invoices found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="customer-bank-wrap">
                    <div class="customer-profile-bank">
                        <div class="bank-title"><i class="fe fe-credit-card"></i><span>Bank Account Details</span></div>
                        <div class="bank-row">
                            <span class="bank-label">Bank Name</span>
                            <span class="bank-value">{{ $customer->bank_name ?: 'Not Set' }}</span>
                        </div>
                        <div class="bank-row">
                            <span class="bank-label">Account Holder</span>
                            <span class="bank-value">{{ $customer->account_holder ?: 'Not Set' }}</span>
                        </div>
                        <div class="bank-row">
                            <span class="bank-label">Account Number</span>
                            <span class="bank-value">{{ $customer->account_number ?: '**** **** ****' }}</span>
                        </div>
                        <div class="bank-row">
                            <span class="bank-label">IFSC / Branch</span>
                            <span class="bank-value">
                                @if ($customer->ifsc || $customer->branch)
                                    {{ $customer->ifsc ?: 'N/A' }}{{ $customer->branch ? ' (' . $customer->branch . ')' : '' }}
                                @else
                                    Not Set
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="customer-profile-notes">
                        <h6>Internal Notes</h6>
                        <p>{{ $customer->notes ?: 'No internal notes available for this customer.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="customerProfileModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Customer Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('customers.update', $customer->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="redirect_to" value="show">
                        <input type="hidden" name="status" value="{{ $customer->status ?: 'active' }}">
                        <div class="modal-body">
                            <div class="row g-4">
                                <div class="col-lg-4">
                                    <label class="form-label fw-semibold">Profile Image</label>
                                    <input type="file" name="image" id="customerImageInput" class="form-control" accept="image/*">
                                    <div class="customer-upload-preview">
                                        <img id="customerImagePreview" src="{{ $profileImage }}" alt="{{ $customer->customer_name }}">
                                    </div>
                                    <div class="form-text">Upload a clear customer image. Preview updates immediately after selection.</div>
                                </div>
                                <div class="col-lg-8">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Customer Name</label>
                                            <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $customer->customer_name) }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone</label>
                                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Currency</label>
                                            <input type="text" name="currency" class="form-control" value="{{ old('currency', $customer->currency) }}" placeholder="₦">
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">Website</label>
                                            <input type="text" name="website" class="form-control" value="{{ old('website', $customer->website) }}" placeholder="https://example.com">
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">Notes</label>
                                            <textarea name="notes" rows="4" class="form-control">{{ old('notes', $customer->notes) }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const input = document.getElementById('customerImageInput');
                const preview = document.getElementById('customerImagePreview');

                input?.addEventListener('change', function (event) {
                    const file = event.target.files && event.target.files[0];
                    if (!file || !preview) return;

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        preview.src = e.target?.result || preview.src;
                    };
                    reader.readAsDataURL(file);
                });
            });
        </script>
    </div>
</div>
@endsection
