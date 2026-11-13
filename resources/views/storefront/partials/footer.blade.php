<footer class="spb-store-footer">
    <div class="spb-store-footer-inner">
        <div class="spb-store-details">
            <strong>{{ $store->store_name }}</strong>
            @if ($store->contact_phone || $store->contact_email)
                <span class="spb-store-contact">{{ $store->contact_phone }}{{ $store->contact_phone && $store->contact_email ? ' | ' : '' }}{{ $store->contact_email }}</span>
            @endif
        </div>
        <div class="spb-footer-brand">
            @include('layout.partials.footer-brand', ['footerBrandLabel' => 'Powered by SmartProbook'])
        </div>
    </div>
</footer>
