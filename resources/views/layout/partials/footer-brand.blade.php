<img src="{{ asset('assets/pwa/icon-192.png') }}" alt="">
<span class="spb-footer-brand-text">
    {{ $footerBrandLabel ?? 'SmartProbook' }}
    @if (!empty($footerBrandCaption))
        <span class="spb-footer-brand-caption">{{ $footerBrandCaption }}</span>
    @endif
</span>
