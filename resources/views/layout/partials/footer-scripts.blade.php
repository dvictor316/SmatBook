{{-- Core JS --}}
<script src="{{ URL::asset('/assets/js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ URL::asset('/assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ URL::asset('/assets/js/feather.min.js') }}"></script>
<script src="{{ URL::asset('/assets/js/jspdf.min.js') }}"></script>
<script src="{{ URL::asset('/assets/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>
<script src="{{ URL::asset('/assets/js/jquery-ui.min.js') }}"></script>

{{-- Common Plugins (Excluded from SaaS Auth Pages to prevent console errors) --}}
@if (!Route::is(['saas-login', 'saas-register', 'forgot-password', 'password.reset']))
    <script src="{{ URL::asset('/assets/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/js/bootstrap-datetimepicker.min.js') }}"></script>
@endif

{{-- DataTables Logic --}}
@if (!Route::is(['companies', 'saas-login', 'saas-register', 'forgot-password', 'password.reset']))
    <script src="{{ URL::asset('/assets/plugins/datatables/datatables.min.js') }}"></script>
@endif

{{-- Dashboard & Charts Logic --}}
@php
    $dashboardRoutes = ['chart-apex', 'chart-js', 'dashboard', 'index-five', 'index-four', 'index-three', 'index-two', 'index', '/'];
@endphp

@if (Route::is($dashboardRoutes) || Route::is('chart-apex'))
    <script src="{{ URL::asset('/assets/plugins/apexchart/apexcharts.min.js') }}"></script>
@endif

@if (Route::is($dashboardRoutes) || Route::is('chart-js'))
    <script src="{{ URL::asset('/assets/plugins/chartjs/chart.min.js') }}"></script>
@endif

@if (Route::is('chart-morris'))
    <script src="{{ URL::asset('/assets/plugins/morris/raphael-min.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/morris/morris.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/morris/chart-data.js') }}"></script>
@endif

@if (Route::is('chart-flot'))
    <script src="{{ URL::asset('/assets/plugins/flot/jquery.flot.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/flot/jquery.flot.fillbetween.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/flot/jquery.flot.pie.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/flot/chart-data.js') }}"></script>
@endif

@if (Route::is('chart-peity'))
    <script src="{{ URL::asset('/assets/plugins/peity/jquery.peity.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/peity/chart-data.js') }}"></script>
@endif

@if (Route::is('chart-c3'))
    <script src="{{ URL::asset('/assets/plugins/c3-chart/d3.v5.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/c3-chart/c3.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/c3-chart/chart-data.js') }}"></script>
@endif

{{-- UI Components --}}
@if (Route::is('horizontal-timeline'))
    <script src="{{ URL::asset('/assets/plugins/timeline/horizontal-timeline.js') }}"></script>
@endif

@if (Route::is('stickynote'))
    <script src="{{ URL::asset('/assets/plugins/stickynote/sticky.js') }}"></script>
@endif

@if (Route::is('notification'))
    <script src="{{ URL::asset('/assets/plugins/alertify/alertify.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/alertify/custom-alertify.min.js') }}"></script>
@endif

@if (Route::is('scrollbar'))
    <script src="{{ URL::asset('/assets/plugins/scrollbar/scrollbar.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/scrollbar/custom-scroll.js') }}"></script>
@endif

@if (Route::is('counter'))
    <script src="{{ URL::asset('/assets/plugins/countup/jquery.counterup.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/countup/jquery.waypoints.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/countup/jquery.missofis-countdown.js') }}"></script>
@endif

@if (Route::is('rating'))
    <script src="{{ URL::asset('/assets/plugins/raty/jquery.raty.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/raty/custom.raty.js') }}"></script>
@endif

@if (Route::is('clipboard'))
    <script src="{{ URL::asset('/assets/plugins/clipboard/clipboard.min.js') }}"></script>
@endif

@if (Route::is('sweetalerts'))
    <script src="{{ URL::asset('/assets/plugins/sweetalert/sweetalert2.all.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/sweetalert/sweetalerts.min.js') }}"></script>
@endif

@if (Route::is('rangeslider'))
    <script src="{{ URL::asset('/assets/plugins/ion-rangeslider/js/ion.rangeSlider.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/ion-rangeslider/js/custom-rangeslider.js') }}"></script>
@endif

@if (Route::is('plan-billing'))
    <script src="{{ URL::asset('/assets/js/owl.carousel.min.js') }}"></script>
@endif

@if (Route::is('form-select2'))
    <script src="{{ URL::asset('/assets/plugins/select2/js/custom-select.js') }}"></script>
@endif

@if (Route::is(['lightbox', 'template-invoice']))
    <script src="{{ URL::asset('/assets/plugins/lightbox/glightbox.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/lightbox/lightbox.js') }}"></script>
@endif

@if (Route::is('drag-drop'))
    <script src="{{ URL::asset('/assets/plugins/dragula/js/dragula.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/dragula/js/drag-drop.min.js') }}"></script>
@endif

@if (Route::is('text-editor'))
    <script src="{{ URL::asset('/assets/plugins/summernote/summernote-bs4.min.js') }}"></script>
@endif

@if (Route::is(['add-products', 'all-blogs', 'contact-details', 'edit-products', 'edit-units', 'expenses', 'pages', 'inactive-blog', 'email-template', 'seo-settings', 'saas-settings']))
    <script src="{{ URL::asset('/assets/plugins/summernote/summernote-lite.min.js') }}"></script>
@endif

@if (Route::is('form-mask'))
    <script src="{{ URL::asset('/assets/js/jquery.maskedinput.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/js/mask.js') }}"></script>
@endif

@if (Route::is('form-fileupload'))
    <script src="{{ URL::asset('/assets/plugins/fileupload/fileupload.min.js') }}"></script>
@endif

@if (Route::is('form-validation'))
    <script src="{{ URL::asset('/assets/js/form-validation.js') }}"></script>
@endif

@if (Route::is(['income-report', 'low-stock-report', 'payment-report', 'tax-purchase', 'tax-sales']))
    <script src="{{ URL::asset('/assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
@endif

@if (Route::is('maps-vector'))
    <script src="{{ URL::asset('/assets/plugins/jvectormap/jquery-jvectormap-2.0.3.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/jvectormap/jquery-jvectormap-world-mill.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/jvectormap/jquery-jvectormap-ru-mill.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/jvectormap/jquery-jvectormap-us-aea.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/jvectormap/jquery-jvectormap-uk_countries-mill.js') }}"></script>
    <script src="{{ URL::asset('/assets/plugins/jvectormap/jquery-jvectormap-in-mill.js') }}"></script>
    <script src="{{ URL::asset('/assets/js/jvectormap.js') }}"></script>
@endif

{{-- Main Theme Script --}}
<script src="{{ URL::asset('/assets/js/script.js') }}"></script>