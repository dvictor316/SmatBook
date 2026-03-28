{{-- ============================================================== --}}
{{--  1. CORE FRAMEWORK & FONTS                                     --}}
{{-- ============================================================== --}}
<link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">

{{-- Google Fonts --}}
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap">

{{-- FontAwesome (Local Fallback to CDN) --}}
@if (file_exists(public_path('assets/plugins/fontawesome/css/all.min.css')))
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">
    @if (file_exists(public_path('assets/plugins/fontawesome/css/v4-shims.min.css')))
        <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/v4-shims.min.css') }}">
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
@else
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/v4-shims.min.css">
@endif

{{-- Core Icons --}}
<link rel="stylesheet" href="{{ asset('assets/plugins/feather/feather.css') }}">

{{-- ============================================================== --}}
{{--  2. GLOBAL PLUGINS                                             --}}
{{-- ============================================================== --}}
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">

{{-- Layout Logic & DateTimePicker (Excluded on Auth/Landing pages) --}}
@unless(Route::is(['index-two', 'saas-login', 'saas-register', 'forgot-password', 'password.reset']))
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }}">
    <script src="{{ asset('assets/js/layout.js') }}"></script>
@endunless

{{-- ============================================================== --}}
{{--  3. PAGE SPECIFIC PLUGINS                                      --}}
{{-- ============================================================== --}}

{{-- DataTables --}}
@unless(Route::is(['index-two', 'companies', 'saas-login', 'saas-register', 'forgot-password', 'password.reset']))
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables/datatables.min.css') }}">
@endunless

@if (Route::is('companies'))
    <link rel="stylesheet" href="{{ asset('assets/css/dataTables.bootstrap5.min.css') }}">
@endif

{{-- Form Elements (Intl Input, Summernote, Rangeslider, Dragula) --}}
@if (Route::is(['add-customer', 'edit-customer', 'testimonials', 'companies']))
    <link rel="stylesheet" href="{{ asset('assets/plugins/intltelinput/css/intlTelInput.css') }}">
    @if(Route::is('companies'))
        <link rel="stylesheet" href="{{ asset('assets/plugins/intltelinput/css/demo.css') }}">
    @endif
@endif

@if (Route::is('text-editor'))
    <link rel="stylesheet" href="{{ asset('assets/plugins/summernote/summernote-bs4.min.css') }}">
@elseif (Route::is(['add-products', 'all-blogs', 'contact-details', 'edit-products', 'edit-units', 'expenses', 'pages', 'inactive-blog', 'email-template', 'seo-settings', 'saas-settings']))
    <link rel="stylesheet" href="{{ asset('assets/plugins/summernote/summernote-lite.min.css') }}">
@endif

@if (Route::is(['drag-drop', 'clipboard']))
    <link rel="stylesheet" href="{{ asset('assets/plugins/dragula/css/dragula.min.css') }}">
@endif

@if (Route::is('rangeslider'))
    <link rel="stylesheet" href="{{ asset('assets/plugins/ion-rangeslider/css/ion.rangeSlider.min.css') }}">
@endif

{{-- UI Components (Calendar, Lightbox, Scrollbar, Charts) --}}
@if (Route::is('calendar'))
    <link rel="stylesheet" href="{{ asset('assets/plugins/fullcalendar/fullcalendar.min.css') }}">
@endif

@if (Route::is('plan-billing'))
    <link rel="stylesheet" href="{{ asset('assets/css/owl.carousel.min.css') }}">
@endif

@if (Route::is(['lightbox', 'template-invoice']))
    <link rel="stylesheet" href="{{ asset('assets/plugins/lightbox/glightbox.min.css') }}">
@endif

@if (Route::is('scrollbar'))
    <link rel="stylesheet" href="{{ asset('assets/plugins/scrollbar/scroll.min.css') }}">
@endif

@if (Route::is('stickynote'))
    <link rel="stylesheet" href="{{ asset('assets/plugins/stickynote/sticky.css') }}">
@endif

@if (Route::is('notification'))
    <link rel="stylesheet" href="{{ asset('assets/plugins/alertify/alertify.min.css') }}">
@endif

@if (Route::is('maps-vector'))
    <link rel="stylesheet" href="{{ asset('assets/plugins/jvectormap/jquery-jvectormap-2.0.3.css') }}">
@endif

@if (Route::is('chart-c3'))
    <link rel="stylesheet" href="{{ asset('assets/plugins/c3-chart/c3.min.css') }}">
@endif

{{-- ============================================================== --}}
{{--  4. ICON SETS (Conditional Loading)                            --}}
{{-- ============================================================== --}}
@if (Route::is('icon-ionic'))       <link rel="stylesheet" href="{{ asset('assets/plugins/icons/ionic/ionicons.css') }}"> @endif
@if (Route::is('icon-material'))    <link rel="stylesheet" href="{{ asset('assets/plugins/material/materialdesignicons.css') }}"> @endif
@if (Route::is('icon-pe7'))         <link rel="stylesheet" href="{{ asset('assets/plugins/icons/pe7/pe-icon-7.css') }}"> @endif
@if (Route::is('icon-simpleline'))  <link rel="stylesheet" href="{{ asset('assets/plugins/simpleline/simple-line-icons.css') }}"> @endif
@if (Route::is('icon-themify'))     <link rel="stylesheet" href="{{ asset('assets/plugins/icons/themify/themify.css') }}"> @endif
@if (Route::is('icon-weather'))     <link rel="stylesheet" href="{{ asset('assets/plugins/icons/weather/weathericons.css') }}"> @endif
@if (Route::is('icon-typicon'))     <link rel="stylesheet" href="{{ asset('assets/plugins/icons/typicons/typicons.css') }}"> @endif
@if (Route::is('icon-flag'))        <link rel="stylesheet" href="{{ asset('assets/plugins/icons/flags/flags.css') }}"> @endif

{{-- Extra Feather Icons for Invoice/Receipt Pages --}}
@if (Route::is([
    'bus-ticket', 'car-booking-invoice', 'cashreceipt-*', 'coffee-shop', 'domain-hosting',
    'ecommerce', 'fitness-center', 'flight-booking', 'General-invoice-*', 'hotel-booking',
    'internet-billing', 'invoice-*', 'mail-pay-invoice', 'medical', 'moneyexchange',
    'movie-ticket-booking', 'pay-online', 'restuarent-billing', 'signature-preview-invoice',
    'student-billing', 'train-ticket-booking'
]))
    <link rel="stylesheet" href="{{ asset('assets/css/feather.css') }}">
@endif

{{-- ============================================================== --}}
{{--  5. MAIN THEME CSS (Loaded last to override plugins)           --}}
{{-- ============================================================== --}}
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

<style>
    /* Keep legacy sidebar/header Feather class names rendering even when the old font pack is unavailable. */
    .sidebar .sidebar-menu i.fe,
    .header i.fe,
    .top-nav-search i.fe {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.15rem;
        min-width: 1.15rem;
        text-align: center;
    }

    .sidebar .sidebar-menu i.fe::before,
    .header i.fe::before,
    .top-nav-search i.fe::before {
        font-family: "Font Awesome 6 Free" !important;
        font-weight: 900 !important;
        font-style: normal;
        line-height: 1;
    }

    .fe-home::before { content: "\f015"; }
    .fe-grid::before { content: "\f00a"; }
    .fe-command::before { content: "\f120"; }
    .fe-users::before { content: "\f0c0"; }
    .fe-package::before { content: "\f466"; }
    .fe-archive::before { content: "\f187"; }
    .fe-file::before { content: "\f15b"; }
    .fe-clipboard::before { content: "\f328"; }
    .fe-file-text::before { content: "\f15c"; }
    .fe-shopping-cart::before { content: "\f07a"; }
    .fe-shopping-bag::before { content: "\f290"; }
    .fe-file-plus::before { content: "\f0fe"; }
    .fe-credit-card::before { content: "\f09d"; }
    .fe-dollar-sign::before { content: "\24"; }
    .fe-lock::before { content: "\f023"; }
    .fe-settings::before { content: "\f013"; }
    .fe-shield::before { content: "\f3ed"; }
    .fe-user::before { content: "\f007"; }
    .fe-trending-up::before { content: "\f201"; }
    .fe-briefcase::before { content: "\f0b1"; }
    .fe-bar-chart::before { content: "\f080"; }
    .fe-book-open::before { content: "\f518"; }
    .fe-percent::before { content: "\25"; }
    .fe-git-branch::before { content: "\f126"; }
    .fe-activity::before { content: "\f201"; }
    .fe-check::before { content: "\f00c"; }
</style>
