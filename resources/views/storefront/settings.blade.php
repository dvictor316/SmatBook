<?php $page = 'online-store'; ?>
@extends('layout.mainlayout')

@section('content')
<div class="page-wrapper"><div class="content container-fluid">
    @component('components.page-header') @slot('title') Online Store @endslot @endcomponent
    @include('layout.partials.flash-messages')
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-4">
        <div class="col-xl-4">
            <section class="border rounded bg-white shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3"><div><h4 class="mb-1">Store Settings</h4><p class="text-muted mb-0">Publish active stock to a customer-facing catalogue.</p></div><span class="badge {{ $setting->is_enabled ? 'bg-success' : 'bg-secondary' }}">{{ $setting->is_enabled ? 'Live' : 'Offline' }}</span></div>
                <form method="POST" action="{{ route('online-store.settings.update') }}">@csrf @method('PUT')
                    <div class="mb-3"><label class="form-label">Store Name *</label><input class="form-control" name="store_name" value="{{ old('store_name', $setting->store_name) }}" required></div>
                    <div class="mb-3"><label class="form-label">SmartProbook Store URL *</label><div class="input-group"><span class="input-group-text">{{ url('/shop') }}/</span><input class="form-control" name="slug" value="{{ old('slug', $setting->slug) }}" required></div></div>
                    <div class="mb-3"><label class="form-label">Custom Domain</label><input class="form-control" name="custom_domain" value="{{ old('custom_domain', $setting->custom_domain) }}" placeholder="shop.yourbusiness.com"><div class="form-text">Optional: enter a web address you own, such as shop.yourbusiness.com. To use it, ask the person who manages your website to connect it to SmartProbook and turn on secure access (HTTPS). If you are unsure, leave this blank and use the SmartProbook Store URL above.</div></div>
                    <div class="mb-3"><label class="form-label">About Your Business / Store Description</label><textarea class="form-control" name="description" rows="3" placeholder="Tell customers what your business does and what makes your store special.">{{ old('description', $setting->description) }}</textarea><div class="form-text">Customers will see this on your store homepage and About Us page.</div></div>
                    <div class="row g-3 mb-3"><div class="col-6"><label class="form-label">Phone</label><input class="form-control" name="contact_phone" value="{{ old('contact_phone', $setting->contact_phone) }}"></div><div class="col-6"><label class="form-label">Email</label><input class="form-control" type="email" name="contact_email" value="{{ old('contact_email', $setting->contact_email) }}"></div></div>
                    <div class="mb-3"><label class="form-label">WhatsApp number for order messages</label><input class="form-control" type="tel" name="owner_whatsapp_number" value="{{ old('owner_whatsapp_number', $setting->owner_whatsapp_number) }}" placeholder="+2348012345678"><div class="form-text">The business owner's saved profile phone is always preferred; this number is only a fallback if the owner profile has no phone. Internationally formatted numbers use WhatsApp; local owner numbers use SMS. Customers can also email the owner when an email address is available.</div>@if($orderContact['phone'] || $orderContact['email'])<div class="form-text mt-1"><strong>Current order contact:</strong> {{ $orderContact['phone'] ?: $orderContact['email'] }}{{ $orderContact['phone'] && $orderContact['email'] ? ' · ' . $orderContact['email'] : '' }}</div>@else<div class="form-text mt-1 text-warning">Add the business owner's phone number or email to their profile so order messages reach them.</div>@endif<div class="form-text mt-1">Automatic WhatsApp owner alerts require the Meta access token, phone-number ID, supported Graph API version, and approved template configured on the server.</div></div>
                    <div class="mb-3"><label class="form-label">Fulfilment Note</label><input class="form-control" name="fulfilment_note" value="{{ old('fulfilment_note', $setting->fulfilment_note) }}" placeholder="Orders confirmed within one business day"></div>
                    <div class="form-check form-switch mb-4"><input type="hidden" name="is_enabled" value="0"><input class="form-check-input" type="checkbox" role="switch" name="is_enabled" value="1" id="storeEnabled" @checked(old('is_enabled', $setting->is_enabled))><label class="form-check-label fw-bold" for="storeEnabled">Publish store</label></div>
                    <button class="btn btn-primary w-100" type="submit"><i class="fas fa-floppy-disk me-1"></i> Save Store</button>
                    <div class="form-text mt-2">The approved WhatsApp order template must accept six text values: order number, store, customer name, customer phone, total, and order status.</div>
                    @if($setting->exists && $setting->is_enabled)<a class="btn btn-outline-primary w-100 mt-2" href="{{ route('storefront.catalogue', $setting->slug) }}" target="_blank"><i class="fas fa-arrow-up-right-from-square me-1"></i> Open Store</a>@endif
                </form>
            </section>
        </div>
        <div class="col-xl-8">
            <section class="border rounded bg-white shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-end gap-3 mb-3"><div><h4 class="mb-1">Online Orders</h4><p class="text-muted mb-0">Orders reserve stock immediately and await your confirmation.</p></div><span class="badge bg-primary">{{ $orders->total() }} orders</span></div>
                <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Payment</th><th>Total</th><th>Status</th><th>WhatsApp</th><th>Update</th></tr></thead><tbody>
                    @forelse($orders as $order)
                        @php($statusClass = match($order->status){'fulfilled'=>'bg-success','cancelled'=>'bg-danger','confirmed','ready'=>'bg-primary',default=>'bg-warning text-dark'})
                        <tr><td><strong>{{ $order->order_number }}</strong><div class="small text-muted">{{ $order->created_at->format('d M Y H:i') }}</div></td><td>{{ $order->customer_name }}<div class="small text-muted">{{ $order->customer_phone }}</div></td><td>{{ $order->items->sum('quantity') }}</td><td>{{ str($order->payment_method)->replace('_', ' ')->title() }}</td><td class="fw-bold">{{ \App\Support\GeoCurrency::format((float) $order->total_amount) }}</td><td><span class="badge {{ $statusClass }}">{{ str($order->status)->replace('_', ' ')->title() }}</span></td><td><span class="badge {{ $order->whatsapp_notification_status === 'sent' ? 'bg-success' : ($order->whatsapp_notification_status === 'failed' ? 'bg-danger' : 'bg-secondary') }}">{{ str($order->whatsapp_notification_status ?: 'pending')->replace('_', ' ')->title() }}</span>@if($order->whatsapp_notification_error)<div class="small text-danger" title="{{ $order->whatsapp_notification_error }}">Delivery failed</div>@endif</td><td>@if(in_array($order->status, ['fulfilled','cancelled']))<span class="text-muted small">Closed</span>@else<form class="d-flex gap-2" method="POST" action="{{ route('online-store.orders.update', $order) }}">@csrf @method('PATCH')<select class="form-select form-select-sm" name="status" aria-label="Order status"><option value="confirmed">Confirmed</option><option value="ready">Ready</option><option value="fulfilled">Fulfilled</option><option value="cancelled">Cancelled</option></select><button class="btn btn-sm btn-outline-primary" type="submit" title="Update order"><i class="fas fa-check"></i></button></form>@endif</td></tr>
                    @empty<tr><td colspan="8" class="text-center text-muted py-5">No online orders have been placed.</td></tr>@endforelse
                </tbody></table></div>
                <div class="mt-3">{{ $orders->links() }}</div>
            </section>
        </div>
    </div>
</div></div>
@endsection
