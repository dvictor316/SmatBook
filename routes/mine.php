<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\IndexCards;
use App\Http\Controllers\Auth\SocialController;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\{
    CustomAuthController,
    CategoryController,
    PageController,
    HomeController,
    RoleController,
    SaleController,
    CustomerController,
    CompanyController,
    InvoiceController,
    EstimateController,
    purchaseCONTROLLER,
    ProductController,
    ProductSaleController,
    ReportController,
    AnalyticsDashboardController, // Main Dashboard
    DashboardController,          // Super Admin/Generic Dashboard
    StatsDashboardController,     // Retained in imports but not used for /dashboard
    SaleItemController,
    SettingController,
    DomainRequestController,
    DomainController, // Ensure you import your controller
    PurchaseOrderViewController,
    VendorController,
    PaymentController,
    MapController,
    BalanceSheetController,
    TrialBalanceController,
    UserController,
    ChatController
    };

    /*
    |--------------------------------------------------------------------------
    | Web Routes
    |--------------------------------------------------------------------------
    | A clean, consolidated file structure.
    |--------------------------------------------------------------------------
    */

        // User Management Routes
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users/store', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::post('/users/{id}/update', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
        
        Route::controller(PageController::class)->group(function () {
        Route::get('/permission', 'permission')->name('permission');
        Route::get('/delete-account-request', 'account')->name('delete-account-request');
        Route::get('/contact-messages', 'contact')->name('contact-messages');
        });

        // ADD THIS OUTSIDE THE GROUP (or below it):
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users/store', [UserController::class, 'store'])->name('users.store');
        Route::delete('/users/delete', [UserController::class, 'destroy'])->name('users.destroy');

            Route::controller(CustomAuthController::class)->group(function () {
        Route::get('login', 'showLoginForm')->name('login');
        Route::post('custom-login', 'customLogin')->name('login.custom');
        Route::get('register', 'showRegistrationForm')->name('register-user');
        Route::post('/custom-register', [CustomAuthController::class, 'registerCustom'])->name('register.custom');
        Route::post('custom-registration', 'customRegistration')->name('register.custom');

    //Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });


        Route::get('auth/{provider}', [CustomAuthController::class, 'redirectToProvider'])->name('social.login');
        Route::get('auth/{provider}/callback', [CustomAuthController::class, 'handleProviderCallback']);

        // Show the forgot password form
        Route::get('forgot-password', [CustomAuthController::class, 'showForgotPasswordForm'])->name('password.request');

        // Handle the form submission
        Route::post('forgot-password', [CustomAuthController::class, 'sendResetLinkEmail'])->name('password.email');

        // Explicit Public Pages
        Route::get('/forgot-password', [HomeController::class, 'forgotpassword'])->name('forgot-password');
        Route::get('/lock-screen', [HomeController::class, 'lockscreen'])->name('lock-screen');
        Route::get('/saas-login', [HomeController::class, 'saaslogin'])->name('saas-login');
        Route::get('/saas-register', [HomeController::class, 'saasregister'])->name('saas-register');
        Route::get('/error-404', [HomeController::class, 'error'])->name('error-404');
        Route::get('/application-error', function () {
            return view('Pages.error-404'); 
        })->name('error.page');


        // 1. The Reset Link Page (What the user sees after clicking the email link)
        Route::get('reset-password/{token}', function (Request $request, $token) {
            return view('auth.reset-password', ['request' => $request, 'token' => $token]);
        })->name('password.reset');

        // 2. The Update Logic (Where the form above submits to)
        Route::post('reset-password', [CustomAuthController::class, 'updatePassword'])->name('password.update');

        Route::get('lock-screen', [CustomAuthController::class, 'lockScreen'])->name('lockscreen');
        Route::post('unlock', [CustomAuthController::class, 'unlock'])->name('unlock');

        Route::get('/global-activity', [CustomAuthController::class, 'showMapVectors'])->name('admin.map');

        // --- 2. AUTHENTICATED ROUTES ---
        Route::middleware(['auth'])->group(function () {

        // 🎯 Use DashboardController (The one we just rewrote)
        Route::get('/', [DashboardController::class, 'index'])->name('home'); 
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('user.dashboard'); 
        Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard'); 
        
    });

        Route::prefix('superadmin')->group(function () {
            // Assuming DashboardController is the Super Admin controller
            Route::get('/dashboard', [AnalyticsDashboardController::class, 'index'])->name('superadmin.dashboard');
        });
        
        // ... other routes

        // 2.2 CORE CRUD RESOURCES
        Route::resources([
            'companies' => CompanyController::class,
        'customers' => CustomerController::class,
        'categories' => CategoryController::class,
        'products' => ProductController::class,
        'invoices' => InvoiceController::class,
        'estimates' => EstimateController::class,
    ]);

            // Route for displaying the list of companies (GET request, used for the breadcrumb link)
        Route::get('/superadmin/companies', [CompanyController::class, 'index']); 

        // Route for handling the form submission to create a new company (POST request)
        Route::post('/superadmin/companies', [CompanyController::class, 'store']); 

    // Custom Product Resource (using dedicated names)
        Route::resource('inventory/products', ProductController::class)->names([
            'index' => 'inventory.Products.index',
            'create' => 'inventory.Products.add-products',
            'store' => 'inventory.Products.store',
            'show' => 'inventory.Products.show',
            'edit' => 'inventory.Products.edit',
            'update' => 'inventory.Products.update',
            'destroy' => 'inventory.Products.destroy',
        ]);

        
        Route::get('/maps-vector', [MapController::class, 'index'])->name('maps-vector');

        // 2.3 SALES & POS
        Route::controller(SaleController::class)->group(function () {
        Route::get('/pos', 'showPos')->name('sales.showPos');
        Route::get('/pos/reports', [SaleController::class, 'index'])->name('pos.reports');
        Route::get('/sales/index', [SaleController::class, 'index'])->name('sales.index');
        Route::post('/sales', 'store')->name('sales.store');
        Route::get('/sales/reports', 'report')->name('pos.report');
        Route::get('/sales', 'index')->name('sales.index');
        Route::get('/sales/{id}', 'showSale')->name('sales.show');
        Route::get('/sales/{sale}/edit', 'edit')->name('sales.edit');
        Route::put('/sales/{sale}', 'update')->name('sales.update');
        Route::get('/sales/invoice/{id}', 'showInvoice')->name('sales.invoice.show');
        Route::get('/sales/chart-data', 'getChartData')->name('sales.chart-data');
      Route::get('/sales/return-to-pos', [SaleController::class, 'returnToPos'])->name('sales.returnToPos');

 });
      Route::middleware(['auth'])->group(function () {
    // The main entry point
    Route::get('/chat/{userId?}', [ChatController::class, 'index'])->name('Applications.Chat.chat');

    // API endpoints used by your JavaScript
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::post('/store', [ChatController::class, 'store'])->name('store');
        Route::get('/contacts', [ChatController::class, 'getContacts'])->name('contacts');
        Route::get('/messages/{userId}', [ChatController::class, 'getMessages'])->name('messages'); // Add this!
        Route::get('/unread-count', [ChatController::class, 'getUnreadCount'])->name('unread-count');
        Route::post('/mark-read', [ChatController::class, 'markAsRead'])->name('mark-read');
        Route::post('/update-last-seen', [ChatController::class, 'updateLastSeen'])->name('update-last-seen');
    });
});



        // Sales Item/Transaction Details
        Route::get('/sales/items/{item}/delete', [SaleItemController::class, 'destroy'])->name('sales.items.delete');
        Route::post('products/{product}/sales', [ProductSaleController::class, 'store'])->name('product_sales.store');
        Route::get('products/{product}/sales', [ProductSaleController::class, 'index'])->name('product_sales.index');
        // Find this line
        Route::get('product-list', [ProductController::class, 'index'])->name('inventory.Products.index');

        // Change it to this
        Route::get('product-list', [ProductController::class, 'index'])->name('product-list');

            // 2.4 API ENDPOINTS (Authenticated)
    Route::prefix('api')->group(function () {
        Route::get('/companies', [CompanyController::class, 'apiIndex'])->name('api.companies');
        Route::get('/products', [ProductController::class, 'apiIndex']);
        Route::get('/recent-invoices', [InvoiceController::class, 'getRecentInvoices'])->name('api.recent-invoices');
        Route::get('/estimates', [EstimateController::class, 'getEstimates'])->name('api.estimates');
        
        Route::controller(AnalyticsDashboardController::class)->group(function () {
            Route::get('/company-stats', 'getCompanyStats');
            Route::get('/sales-analytics', 'getSalesAnalytics');
            Route::get('/invoice-analytics', 'getInvoiceAnalytics');
            Route::get('/sales-data', 'getSalesData')->name('api.sales-data');
        });
        
        Route::get('/chat/messages', [ChatController::class, 'getMessagesApi']);
    });




        // Redirect to Provider
        Route::get('auth/{provider}', [SocialController::class, 'redirectToProvider'])->name('social.login');

        // Callback from Provider
        Route::get('auth/{provider}/callback', [SocialController::class, 'handleProviderCallback']);


// Login Routes
Route::get('/custom-login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/custom-login', [AuthController::class, 'login']);

// Domain Management Routes
Route::get('/domain', [DomainController::class, 'index'])->name('domain');
Route::post('/domains/store', [DomainController::class, 'store'])->name('domains.store');
Route::delete('/domains/{domain}', [DomainController::class, 'destroy'])->name('domains.destroy');

    /*
    |--------------------------------------------------------------------------
    | Accounting Routes
    |--------------------------------------------------------------------------
    |
    | Add these routes to your web.php file
    |
    */

    Route::middleware(['auth'])->group(function () {
        // Balance Sheet Routes
        Route::get('/balance-sheet', [BalanceSheetController::class, 'index'])->name('balance-sheet');
        Route::get('/balance-sheet/export', [BalanceSheetController::class, 'export'])->name('balance-sheet.export');

        // Trial Balance Routes
        Route::get('/trial-balance', [TrialBalanceController::class, 'index'])->name('trial-balance');
        Route::get('/trial-balance/export', [TrialBalanceController::class, 'export'])->name('trial-balance.export');
    });
 

Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    // 2.5 MISCELLANEOUS AUTHENTICATED PAGES (PageController & HomeController)

    Route::controller(PageController::class)->group(function () {
      
        // Settings
        Route::get('/bank-account', 'bank_account')->name('bank-account');
        Route::get('/company-settings', 'company_settings')->name('company-settings');
        Route::get('/email-settings', 'email_settings')->name('email-settings');
        Route::get('/invoice-settings', 'invoice_settings')->name('invoice-settings');
        Route::get('/payment-settings', 'payment_settings')->name('payment-settings');
        Route::get('/plan-billing', 'plan_billing')->name('plan-billing');
        Route::get('/preferences', 'preferences')->name('preferences');
        Route::get('/settings', 'settings')->name('settings');
        Route::get('/tax-rates', 'tax_rates')->name('tax-rates');
        Route::get('/template-invoice', 'template_invoice')->name('template-invoice');
        Route::get('/two-factor', 'two_factor')->name('two-factor');
        Route::get('/custom-filed', 'custom_filed')->name('custom-filed');
        Route::get('/email-template', 'emailtemplate')->name('email-template');
        Route::get('/seo-settings', 'seosettings')->name('seo-settings');
        Route::get('/saas-settings', 'saassettings')->name('saas-settings');

/*
|--------------------------------------------------------------------------
| Unified Report & Returns Routes
|--------------------------------------------------------------------------
| This block handles all financial reports, purchase returns (debit notes),
| and sales returns (credit notes) using the ReportController.
*/

    Route::controller(ReportController::class)->group(function () {
        
        // --- 1. FINANCIAL & OPERATIONAL REPORTS ---
        Route::get('/expense-report', 'expense_report')->name('expense-report');
        Route::get('/income-report', 'income_report')->name('income-report');
        Route::get('/low-stock-report', 'low_stock_report')->name('low-stock-report');
        Route::get('/payment-report', 'payment_report')->name('payment-report');
        Route::get('/purchase-report', 'purchase_report')->name('purchase-report');
        Route::get('/quotation-report', 'quotation_report')->name('quotation-report');
        Route::get('/sales-report', 'sales_report')->name('sales-report');
        Route::get('/stock-report', 'stock_report')->name('stock-report');
        Route::get('/profit-loss-list', 'profit_loss_list')->name('profit-loss-list');
        Route::get('/tax-purchase', 'tax_purchase')->name('tax-purchase');
        Route::get('/tax-sales', 'tax_sales')->name('tax-sales');



    // --- 3. SALES RETURNS (CREDIT NOTES) ---
        // The main report/listing page
        Route::get('/sales-return-report', 'credit_notes')->name('credit-notes');
        
    Route::get('/create-sales-return', 'create_credit_note')->name('create-sales-return');
        
        // Saving the return data
        Route::post('/sales-return/store', 'store_credit_note')->name('credit-notes.store');
        
    });

        Route::get('/get-invoice-items/{id}', [ReportController::class, 'get_invoice_items']);

        // --- 3. OTHER INVOICE ROUTES (Assuming they are in a different controller or the default one) ---
        Route::get('/invoices', 'index')->name('invoices.index');
        Route::get('/add-invoice', 'add_invoice')->name('add-invoice');
        Route::get('/edit-credit-notes', 'edit_credit_notes')->name('edit-credit-notes');
        Route::get('/recurring-invoices', 'recurring_invoices')->name('recurring-invoices'); 

                // Quotations & Delivery
        Route::get('/add-delivery-challans', 'add_delivery_challans')->name('add-delivery-challans');
        Route::get('/add-quotations', 'add_quotations')->name('add-quotations');
        Route::get('/delivery-challans', 'delivery_challans')->name('delivery-challans');
        Route::get('/edit-delivery-challans', 'edit_delivery_challans')->name('edit-delivery-challans');
        Route::get('/edit-quotations', 'edit_quotations')->name('edit-quotations');
        Route::get('/quotations', 'quotations')->name('quotations');

        Route::get('/payment-summary', [ReportController::class, 'paymentSummary'])->name('payment-summary');
        Route::post('/payments/bulk-update', [ReportController::class, 'bulkUpdate'])->name('payments.bulk-update');
     // Route for Individual Deletion
        Route::delete('/payments/{id}', [ReportController::class, 'destroy'])->name('payments.destroy');

        // FIX: Point this to ReportController, NOT the local 'credit_notes' string
        Route::get('/credit-notes', [\App\Http\Controllers\ReportController::class, 'credit_notes'])->name('credit-notes');

        Route::get('/edit-credit-notes', 'edit_credit_notes')->name('edit-credit-notes');
        Route::get('/recurring-invoices', 'recurring_invoices')->name('recurring-invoices');


        // Invoice Templates/Styles
        Route::get('/invoice-five', 'invoice_five')->name('invoice-five');
        Route::get('/invoice-four-a', 'invoice_four_a')->name('invoice-four-a');
        Route::get('/invoice-one-a', 'invoice_one_a')->name('invoice-one-a');
        Route::get('/invoice-template', 'invoice_template')->name('invoice-template');
        Route::get('/invoice-three', 'invoice_three')->name('invoice-three');
        Route::get('/invoice-two', 'invoice_two')->name('invoice-two');

        // Invoice Status Pages
        Route::get('/invoices-cancelled', 'invoices_cancelled')->name('invoices-cancelled');
        Route::get('/invoices-draft', 'invoices_draft')->name('invoices-draft');
        Route::get('/invoices-overdue', 'invoices_overdue')->name('invoices-overdue');
        Route::get('/invoices-paid', 'invoices_paid')->name('invoices-paid');
        Route::get('/invoices-recurring', 'invoices_recurring')->name('invoices-recurring');
        Route::get('/invoices-refunded', 'invoices_refunded')->name('invoices-refunded');
        Route::get('/invoices-unpaid', 'invoices_unpaid')->name('invoices-unpaid');
   

        Route::get('/pay-online', 'pay_online')->name('pay-online');
        Route::get('/signature-invoice', 'signature_invoice')->name('signature-invoice');
        Route::get('/signature-list', 'signature_list')->name('signature-list');
        Route::get('/signature-preview-invoice', 'signature_preview_invoice')->name('signature-preview-invoice');
        Route::get('/mail-pay-invoice', 'mail_pay_invoice')->name('mail-pay-invoice');
        
       // routes/web.php

    Route::middleware(['auth'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    Route::middleware(['auth', 'role:Administrator'])->group(function () {
        Route::get('/roles-permission', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles-store', [RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles-update/{id}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles-delete/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');
        
    
    });

    // 1. This route now matches http://127.0.0.1:8000/domain
    Route::get('/domain', [DomainController::class, 'index'])->name('domain');

    // 2. Keep the processing/admin routes inside the group for security/organization
    Route::group(['prefix' => 'SuperAdmin', 'as' => 'SuperAdmin.'], function () {
        
    // Domain Management (Internal Actions)
    Route::post('/domain-request', [DomainController::class, 'store'])->name('domain.store');
    Route::get('/domain/{domain}/edit', [DomainController::class, 'edit'])->name('domain.edit');
    Route::put('/domain/{domain}', [DomainController::class, 'update'])->name('domain.update');
    Route::delete('/domain/{domain}', [DomainController::class, 'destroy'])->name('domain.destroy');
    //Route::delete('/domain/{domain}', [DomainController::class, 'destroy'])->name('SuperAdmin.domain.destroy');
    
    // Year-End Report for 2025-12-30
    Route::get('/domain-year-end-report', [DomainController::class, 'yearEndReport'])->name('domain.report');
});


    Route::middleware(['auth', 'role:Administrator,Store Manager'])->group(function () {
        Route::resource('products', ProductController::class);
        Route::get('/stock-alerts', [ProductController::class, 'lowStock']);
    });

    Route::middleware(['auth', 'role:Administrator,Cashier,Sales Manager'])->group(function () {
        Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('/sales/store', [SaleController::class, 'store'])->name('sales.store');
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/roles-permission/{id}', [RoleController::class, 'showPermissions'])->name('roles.permissions');
    Route::post('/roles-permission/update', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');
    });

            // ... other routes ...
        Route::get('/packages', 'packages')->name('packages');
      // Update this line in your routes/web.php
        Route::get('/purchase-transaction', [App\Http\Controllers\purchaseCONTROLLER::class, 'purchase_transaction'])->name('purchase-transaction');
        Route::get('/subscription', 'subscription')->name('subscription');

        // Support / Tickets
        Route::get('/ticket-details', 'details')->name('ticket-details');
        Route::get('/tickets-closed', 'closed')->name('tickets-closed');
        Route::get('/tickets-kanban', 'kanban')->name('tickets-kanban');
        Route::get('/tickets-list-closed', 'list_closed')->name('tickets-list-closed');
        Route::get('/tickets-list-open', 'list_open')->name('tickets-list-open');
        Route::get('/tickets-list-pending', 'list_pending')->name('tickets-list-pending');
        Route::get('/tickets-list-resolved', 'list_resolved')->name('tickets-list-resolved');
        Route::get('/tickets', 'tickets')->name('tickets');

        // UI/Forms/Charts/Icons/Utilities
        Route::get('/accordions', 'accordions')->name('accordions');
        Route::get('/alerts', 'alerts')->name('alerts');
        Route::get('/avatar', 'avatar')->name('avatar');
        Route::get('/badges', 'badges')->name('badges');
        Route::get('/buttongroup', 'buttongroup')->name('buttongroup');
        Route::get('/breadcrumbs', 'breadcrumbs')->name('breadcrumbs');
        Route::get('/buttons', 'buttons')->name('buttons');
        Route::get('/cards', 'cards')->name('cards');
        Route::get('/carousel', 'carousel')->name('carousel');
        Route::get('/dropdowns', 'dropdowns')->name('dropdowns');
        Route::get('/grid', 'grid')->name('grid');
        Route::get('/images', 'images')->name('images');
        Route::get('/lightbox', 'lightbox')->name('lightbox');
        Route::get('/media', 'media')->name('media');
        Route::get('/modal', 'modal')->name('modal');
        Route::get('/offcanvas', 'offcanvas')->name('offcanvas');
        Route::get('/pagination', 'pagination')->name('pagination');
        Route::get('/placeholders', 'placeholders')->name('placeholders');
        Route::get('/popover', 'popover')->name('popover');
        Route::get('/progress', 'progress')->name('progress');
        Route::get('/rangeslider', 'rangeslider')->name('rangeslider');
        Route::get('/spinners', 'spinners')->name('spinners');
        Route::get('/sweetalerts', 'sweetalerts')->name('sweetalerts');
        Route::get('/tab', 'tab')->name('tab');
        Route::get('/toastr', 'toastr')->name('toastr');
        Route::get('/tooltip', 'tooltip')->name('tooltip');
        Route::get('/typography', 'typography')->name('typography');
        Route::get('/video', 'video')->name('video');
        Route::get('/chart-apex', 'chart_apex')->name('chart-apex');
        Route::get('/chart-c3', 'chart_c3')->name('chart-c3');
        Route::get('/chart-flot', 'chart_flot')->name('chart-flot');
        Route::get('/chart-js', 'chart_js')->name('chart-js');
        Route::get('/chart-morris', 'chart_morris')->name('chart-morris');
        Route::get('/chart-peity', 'chart_peity')->name('chart-peity');
        Route::get('/clipboard', 'clipboard')->name('clipboard');
        Route::get('/counter', 'counter')->name('counter');
        Route::get('/drag-drop', 'drag_drop')->name('drag-drop');
        Route::get('/form-wizard', 'form_wizard')->name('form-wizard');
        Route::get('/horizontal-timeline', 'horizontal_timeline')->name('horizontal-timeline');
        Route::get('/notification', 'notification')->name('notification');
        Route::get('/rating', 'rating')->name('rating');
        Route::get('/ribbon', 'ribbon')->name('ribbon');
        Route::get('/scrollbar', 'scrollbar')->name('scrollbar');
        Route::get('/stickynote', 'stickynote')->name('stickynote');
        Route::get('/text-editor', 'text_editor')->name('text-editor');
        Route::get('/timeline', 'timeline')->name('timeline');
        Route::get('/form-basic-inputs', 'form_basic_inputs')->name('form-basic-inputs');
        Route::get('/form-fileupload', 'form_fileupload')->name('form-fileupload');
        Route::get('/form-horizontal', 'form_horizontal')->name('form-horizontal');
        Route::get('/form-input-groups', 'form_input_groups')->name('form-input-groups');
        Route::get('/form-select2', 'form_select2')->name('form-select2');
        Route::get('/form-vertical', 'form_vertical')->name('form-vertical');
        Route::get('/form-mask', 'form_mask')->name('form-mask');
        Route::get('/form-validation', 'form_validation')->name('form-validation');
        Route::get('/icon-fontawesome', 'icon_fontawesome')->name('icon-fontawesome');
        Route::get('/icon-feather', 'icon_feather')->name('icon-feather');
        Route::get('/icon-flag', 'icon_flag')->name('icon-flag');
        Route::get('/icon-ionic', 'icon_ionic')->name('icon-ionic');
        Route::get('/icon-material', 'icon_material')->name('icon-material');
        Route::get('/icon-pe7', 'icon_pe7')->name('icon-pe7');
        Route::get('/icon-simpleline', 'icon_simpleline')->name('icon-simpleline');
        Route::get('/icon-themify', 'icon_themify')->name('icon-themify');
        Route::get('/icon-typicon', 'icon_typicon')->name('icon-typicon');
        Route::get('/icon-weather', 'icon_weather')->name('icon-weather');
        Route::get('/data-tables', 'data_tables')->name('data-tables');
        Route::get('/tables-basic', 'tables_basic')->name('tables-basic');
        Route::get('/notifications', 'notifications')->name('notifications');
        
        // Chart API
        Route::get('/api/morris-chart-data', 'getMorrisChartData')->name('api.morris.chart-data');
        Route::get('/morris-data', 'getMorrisData')->name('api.morris.data');
    });
    
    Route::controller(HomeController::class)->group(function () {
        Route::get('/chat', 'chat')->name('chat');
        Route::get('/calendar', 'calendar')->name('calendar');
        Route::get('/inbox', 'inbox')->name('inbox');
        Route::get('/profile', 'profile')->name('profile');
        Route::post('/profile/update-images', [CustomAuthController::class, 'updateProfileImages'])->name('profile.update.images');
        
        // Blog/CMS
        Route::get('/all-blogs', 'allblogs')->name('all-blogs');
        Route::get('/inactive-blog', 'inactiveblog')->name('inactive-blog');
        Route::get('/blog-comments', 'blogcomments')->name('blog-comments');
        Route::get('/categories', 'categories')->name('categories');
        Route::get('/pages', 'pages')->name('pages');
        Route::get('/testimonials', 'testimonials')->name('testimonials');
        Route::get('/faq', 'faq')->name('faq');


        Route::get('/active-customers', [CustomerController::class, 'activeView'])->name('active-customers');
        Route::get('/deactive-customers', [CustomerController::class, 'deactiveView'])->name('deactive-customers');

        Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
        Route::get('/vendors/create', [VendorController::class, 'create'])->name('vendors.create');
        Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');

        Route::get('/Customers/ledger/{id}', [VendorController::class, 'ledger'])->name('ledger');
        Route::get('/ledger', [VendorController::class, 'ledger_general'])->name('ledger.general');

        // Routes for editing and deleting vendors:
        Route::get('/vendors/{id}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
        Route::put('/vendors/{id}', [VendorController::class, 'update'])->name('vendors.update');
        Route::delete('/vendors/{id}', [VendorController::class, 'destroy'])->name('vendors.destroy');

        // Routes for adding specific vendor transactions:
        Route::get('/vendors/{id}/transactions/create', [VendorController::class, 'createTransaction'])->name('vendors.transactions.create');
        Route::post('/vendors/{id}/transactions', [VendorController::class, 'storeTransaction'])->name('vendors.transactions.store');



            // Purchase/Expenses
        // routes/web.php
        Route::middleware(['auth'])->group(function () {
        Route::resource('expenses', \App\Http\Controllers\ExpenseController::class);
        Route::get('expenses/download/{filename}', [\App\Http\Controllers\ExpenseController::class, 'download'])
            ->name('expenses.download');
    });

        // routes/web.php
    Route::middleware(['auth'])->group(function () {
        Route::get('/chat/{userId?}', [ChatController::class, 'index'])->name('Application.Chat.chat');
        Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
        Route::get('/chat/messages/{userId}', [ChatController::class, 'getMessages'])->name('chat.messages');
        Route::get('/chat/contacts', [ChatController::class, 'getContacts'])->name('chat.contacts');
        Route::post('/chat/read/{userId}', [ChatController::class, 'markAsRead'])->name('chat.read');
        Route::post('/chat/update-last-seen', [ChatController::class, 'updateLastSeen'])->name('chat.update-last-seen');
        Route::get('/chat/search-users', [ChatController::class, 'searchUsers'])->name('chat.search-users');
        Route::delete('/chat/message/{id}', [ChatController::class, 'deleteMessage'])->name('chat.delete');
    });



    Route::middleware(['auth'])->group(function () {
        // This single line creates index, store, update, and destroy routes
        Route::resource('payments', PaymentController::class);

        // Custom functional routes (Keep these below the resource)
        Route::get('payments/download/{filename}', [PaymentController::class, 'download'])->name('payments.download');
        Route::get('payments/statistics', [PaymentController::class, 'statistics'])->name('payments.statistics');
        Route::get('payments/sale/{saleId}', [PaymentController::class, 'getBySale'])->name('payments.by-sale');
        Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
    });

        // Standard resource routes (creates all CRUD routes)
        Route::resource('purchases', PurchaseController::class);

        // If you need custom pages, add the methods to your controller:
        Route::get('/add-purchase-return', [PurchaseController::class, 'createReturn'])->name('add-purchase-return');
        Route::get('/purchase-returns/create', [PurchaseController::class, 'createReturn'])->name('purchase-returns.create');
        Route::post('/purchase-returns.store', [PurchaseController::class, 'storeReturn'])->name('purchase-returns.store');
        Route::get('/add-purchases-order', [PurchaseController::class, 'createOrder'])->name('add-purchases-order');
        // Add this to routes/web.php
Route::get('/get-purchase-items/{id}', [App\Http\Controllers\PurchaseController::class, 'getPurchaseItems']);
        // For add-purchases, use the standard create method from resource
        Route::get('/debit-notes', [PurchaseController::class, 'debitNotes'])->name('debit-notes');
        Route::get('/edit-purchase-return/{id}', [PurchaseController::class, 'editReturn'])->name('edit-purchase-return');
        Route::get('/edit-purchases-order/{id}', [PurchaseController::class, 'editOrder'])->name('edit-purchases-order');
        // For edit-purchases, use the standard edit method from resource
        Route::get('/purchase-orders', [PurchaseController::class, 'purchaseOrders'])->name('purchase-orders');
        Route::get('/purchase-details/{id}', [PurchaseController::class, 'show'])->name('purchase-details');
   
// Add these to your existing routes
Route::get('/purchases/{id}/pdf', [PurchaseController::class, 'downloadPDF'])->name('purchases.pdf');
Route::get('/purchases/{id}/excel', [PurchaseController::class, 'exportExcel'])->name('purchases.excel');
        // For purchases listing, use the standard index method from resource

        Route::get('/products/units', [App\Http\Controllers\ProductController::class, 'units'])->name('products.units');

        // Inventory Route
        Route::get('/inventory', [ProductController::class, 'inventory'])->name('inventory');

        // Route for individual product inventory history
        Route::get('/inventory-history/{id}', [ProductController::class, 'inventory_history'])->name('inventory.history');
        Route::post('/inventory-history/delete', [ProductController::class, 'delete_history'])->name('inventory.history.delete');
        Route::get('/test-history-seed/{id}', [ProductController::class, 'seed_test_history']);
        Route::post('/inventory/adjust', [ProductController::class, 'adjust_stock'])->name('inventory.adjust');
            
        Route::prefix('reports')->group(function () {
            // The existing route for the view
            Route::get('/low-stock-report', [ReportController::class, 'low_stock_report'])->name('low-stock-report');

            // The NEW route for the email AJAX action
            Route::post('/email-low-stock', [ReportController::class, 'email_low_stock_report'])->name('reports.email-low-stock');
        });

            // Product/Inventory Views
        Route::get('/payments/{payment}/receipt', [App\Http\Controllers\PaymentController::class, 'receipt'])->name('payments.receipt');    
    Route::controller(ProductController::class)->group(function () {
        // Main List (This is the one we fixed)
        Route::get('/product-list', 'index')->name('product-list');
        
        // Add Product (View & Action)
        Route::get('/add-products', 'create')->name('add-products');
        Route::post('/products/store', 'store')->name('inventory.Products.store');
        
        // Edit Product (Needs an ID to know which product to edit)
        Route::get('/edit-products/{id}', 'edit')->name('inventory.Products.edit');
        Route::put('/products/update/{id}', 'update')->name('inventory.Products.update');
        
        // Delete Product
        Route::delete('/products/delete/{id}', 'destroy')->name('inventory.Products.destroy');

        // These can remain generic or be moved to ProductController if they handle data
        Route::get('/units', 'units')->name('units');
        Route::get('/inventory-history', 'inventoryhistory')->name('inventory-history');
        Route::get('/inventory', 'inventory')->name('inventory');
    });

        // Manual definition to ensure the exact URL you want works
       // Route::get('/inventory/products/category', [CategoryController::class, 'index'])->name('Inventory.Products.categories');
       Route::get('/inventory/products/category', [CategoryController::class, 'index'])->name('categories.index'); 
       Route::post('/categories/store', [CategoryController::class, 'store'])->name('categories.store');
        // Keep your existing resource-style routes if you need them for edit/delete
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        

        // Membership/Subscription Views
        Route::get('/membership-addons', 'membershipaddons')->name('membership-addons');
        Route::get('/membership-plans', 'membershipplans')->name('membership-plans');
        Route::get('/subscribers', 'subscribers')->name('subscribers');
        Route::get('/transactions', 'transactions')->name('transactions');

Route::delete('/roles/delete-user', [RoleController::class, 'deleteUserRequest'])->name('roles.delete-user');

        // Other Views
        Route::get('/maps-vector', 'mapsvector')->name('maps-vector');
        Route::get('/blank-page', 'blankpage')->name('blank-page');


        // Define a route that loads the HTML table page
        Route::get('/admin/purchase-orders', [PurchaseOrderViewController::class, 'showOrdersTable'])
            ->name('admin.purchase.orders');

// Group all SuperAdmin routes together
Route::prefix('super-admin')->name('SuperAdmin.')->group(function () {
    
    // Plan Management
    // The name will automatically become "SuperAdmin.plans"
    Route::get('/plans', [PlanController::class, 'index'])->name('plans'); 
    
    Route::get('/plans/create', [PlanController::class, 'create'])->name('plans.create');
    Route::post('/plans/store', [PlanController::class, 'store'])->name('plans.store');
    Route::get('/plans/edit/{id}', [PlanController::class, 'edit'])->name('plans.edit');
    Route::put('/plans/update/{id}', [PlanController::class, 'update'])->name('plans.update');
    Route::delete('/plans/destroy/{id}', [PlanController::class, 'destroy'])->name('plans.destroy');
    
    // This becomes "SuperAdmin.subscription"
    Route::get('/subscription', [PlanController::class, 'subscribers'])->name('subscription');
});


              // Change the old /product-list route to this:
        Route::get('/product-list', [ProductController::class, 'index'])->name('inventory.Products.index');

        // Add these to make the Save/Delete buttons work in your view
        Route::post('/products/store', [ProductController::class, 'store'])->name('inventory.Products.store');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('inventory.Products.destroy');
        Route::post('/categories/store', [CategoryController::class, 'store']);
    });

    
 

  