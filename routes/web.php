<?php

use Illuminate\Support\Facades\{Route, Auth, Session, DB, Hash, Schema};
use App\Models\{User, Subscription, Company};
use App\Http\Controllers\{
    AuthController, LandingController, SubscriptionController, DashboardController,
    HomeController, RoleController, UserController, CompanyController, CustomerController,
    VendorController, SupplierController, ProductController, CategoryController, ProductSaleController,
    SaleController, SaleItemController, InvoiceController, SalesInvoiceController,
    EstimateController, PurchaseController, PurchaseOrderViewController, ExpenseController,
    PaymentController, ReportController, CashFlowController, BalanceSheetController,
    TrialBalanceController, GeneralLedgerController, SettingController, ChatController, MapController,
    CustomAuthController, AnalyticsDashboardController, DomainController, PlanController,
    SuperAdminDashboardController, MessageController, CalendarController, EventController,
    NotificationController, ActivityLogController, BackupController, AuditController,
    TaxCenterController, TaxFilingController, PeriodCloseController, ProjectManagementController
    , AiQuickAgentController, RecurringTransactionController, FinanceApprovalController, FixedAssetController, BudgetController
};
use App\Http\Controllers\SuperAdmin\DeploymentManagerController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/robots.txt', function () {
    $base = rtrim(config('app.url') ?: url('/'), '/');
    $content = implode("\n", [
        'User-agent: *',
        'Allow: /',
        'Disallow: /superadmin/',
        'Disallow: /deployment/',
        'Disallow: /saas/',
        'Disallow: /login',
        'Disallow: /register',
        'Disallow: /saas-login',
        'Disallow: /saas-register',
        'Disallow: /password/',
        'Sitemap: ' . $base . '/sitemap.xml',
    ]);

    return response($content, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
});

Route::get('/sitemap.xml', function () {
    $now = now()->toAtomString();
    $urls = [
        ['loc' => url('/'), 'changefreq' => 'daily', 'priority' => '1.0'],
        ['loc' => route('landing.about'), 'changefreq' => 'weekly', 'priority' => '0.8'],
        ['loc' => route('landing.contact'), 'changefreq' => 'weekly', 'priority' => '0.7'],
        ['loc' => route('landing.team'), 'changefreq' => 'weekly', 'priority' => '0.7'],
        ['loc' => route('landing.policy'), 'changefreq' => 'monthly', 'priority' => '0.5'],
        ['loc' => route('landing.projects.lahome'), 'changefreq' => 'weekly', 'priority' => '0.7'],
        ['loc' => route('landing.projects.master-jamb'), 'changefreq' => 'weekly', 'priority' => '0.7'],
        ['loc' => route('landing.projects.payplus'), 'changefreq' => 'weekly', 'priority' => '0.7'],
        ['loc' => route('membership-plans'), 'changefreq' => 'weekly', 'priority' => '0.8'],
    ];

    $xml = view('sitemap.xml', compact('urls', 'now'));
    return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
})->name('sitemap.xml');

Route::get('/', [LandingController::class, 'index'])->name('landing.index');
Route::get('/about-us', [LandingController::class, 'about'])->name('landing.about');
Route::get('/contact-us', [LandingController::class, 'contact'])->name('landing.contact');
Route::get('/demo', [LandingController::class, 'demo'])->name('landing.demo');
Route::post('/contact-us', [LandingController::class, 'storeContact'])->name('contact.store');
Route::get('/media/product-image/{path}', [ProductController::class, 'serveImage'])
    ->where('path', '.*')
    ->name('products.image');
Route::get('/media/public/{path}', [ProductController::class, 'serveImage'])
    ->where('path', '.*')
    ->name('media.public');
Route::get('/our-team', [LandingController::class, 'team'])->name('landing.team');
Route::get('/company-policy', [LandingController::class, 'policy'])->name('landing.policy');
Route::get('/projects/lahome-properties', [LandingController::class, 'projectLahome'])->name('landing.projects.lahome');
Route::get('/projects/master-jamb', [LandingController::class, 'projectMasterJamb'])->name('landing.projects.master-jamb');
Route::get('/projects/payplus', [LandingController::class, 'projectPayplus'])->name('landing.projects.payplus');
Route::get('/pricing', [SubscriptionController::class, 'plans'])->name('pricing');
Route::get('/membership-plans', [SubscriptionController::class, 'plans'])->name('membership-plans');
Route::get('/deploy-infrastructure', function (\Illuminate\Http\Request $request) {
    // Always start deployment from a clean state (no stale plan/checkout context).
    $request->session()->forget([
        'selected_plan_id',
        'selected_plan',
        'selected_cycle',
        'selected_amount',
        'billing_cycle',
        'plan',
        'reg_role',
        'checkout_from_deployment',
        'deployment_manager_id',
        'deployment_customer_id',
        'deployment_company_id',
        'deployment_subscription_id',
    ]);

    return redirect()->to(route('landing.index') . '#licensing');
})->name('deploy.infrastructure');
Route::get('/session/ping', function () {
    return response()->json([
        'ok' => true,
        'ts' => now()->toIso8601String(),
    ]);
})->name('session.ping');

Route::get('/session/csrf-token', function (\Illuminate\Http\Request $request) {
    $request->session()->regenerateToken();

    return response()->json([
        'token' => csrf_token(),
    ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
})->name('session.csrf-token');

Route::get('/workspace-not-found', function () {
    return response()->view('errors.workspace-not-found', [], 404);
})->name('workspace.not.found');

// Public upgrade redirect — guests are handled inside the controller (redirected to register with plan stored in session)
Route::get('/membership-plans/upgrade', [SubscriptionController::class, 'redirectToUpgradeCheckout'])->name('subscription.upgrade.redirect');

Route::middleware(['auth'])->group(function () {
    Route::get('/workspace/business/dashboard', [DashboardController::class, 'businessDashboard'])->name('workspace.business.dashboard');
    Route::get('/workspace/business', [DashboardController::class, 'switchToBusinessWorkspace'])->name('workspace.business');
    Route::get('/workspace/platform', [DashboardController::class, 'switchToPlatformWorkspace'])->name('workspace.platform');
    Route::get('/subscription/expired', [HomeController::class, 'subscriptionExpired'])->name('subscription.expired');
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATION ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/saas-login', [AuthController::class, 'showLogin'])->name('saas-login');
    Route::post('/saas-login', [AuthController::class, 'login'])->name('saas-login.post');
    Route::get('/login-account', [AuthController::class, 'showLogin'])->name('login-account');
    Route::post('/login-account', [AuthController::class, 'login'])->name('login-account.post');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    
    Route::get('/saas-register', [AuthController::class, 'showRegister'])->name('saas-register');
    Route::post('/saas-register', [AuthController::class, 'register'])->name('saas-register.post');
    Route::get('/register-account', [AuthController::class, 'showRegister'])->name('saas-register-initial');
    Route::post('/register-account', [AuthController::class, 'register'])->name('saas-register-initial.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    
    Route::get('/auth/{provider}', [AuthController::class, 'redirectToProvider'])->name('social.login');
    Route::get('/auth/{provider}/callback', [AuthController::class, 'handleProviderCallback'])->name('social.callback');
    
    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password', function () {
        return redirect()->route('password.request')
            ->with('error', 'A valid password reset link is required. Request a new reset email to continue.');
    });
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'updatePassword'])->name('password.update');
});

Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| SUBSCRIPTION & PAYMENT ROUTES
|--------------------------------------------------------------------------
*/

Route::controller(SubscriptionController::class)->group(function () {
    Route::get('/select-plan', 'plans')->name('saas.select_plan');
    Route::post('/select-plan/save', 'savePlan')->name('saas.select_plan.save');
    Route::get('/setup-workspace/{id?}', 'create')->name('saas.setup.legacy');
    Route::post('/setup-workspace/save', 'store')->name('user.setup.save.legacy');
});

/*
|--------------------------------------------------------------------------
| MANAGER VERIFICATION ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/manager/pending-approval', fn() => view('Auth.manager_pending'))->name('manager.pending.notice');
    Route::get('/manager/verify-profile', [DeploymentManagerController::class, 'showVerificationForm'])->name('manager.verification.form');
    Route::post('/manager/verify-profile', [DeploymentManagerController::class, 'submitVerification'])->name('manager.submit.verification'); 
    Route::post('/verify', [DeploymentManagerController::class, 'submitVerification'])->name('submit.verification'); 
});



// ============================================================
// SAAS / SUBSCRIPTION ROUTES (outside deployment prefix)
// ============================================================


Route::middleware(['auth'])->group(function () {

    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::match(['get', 'post'], '/ai/quick-agent/query', [AiQuickAgentController::class, 'query'])->name('ai.quick-agent.query');

    // ── Subscription / SaaS routes ──────────────────────────────
    Route::controller(SubscriptionController::class)->group(function () {

        // Setup wizard (domain/workspace config)
        Route::get('/saas/setup/{id?}',             'create')              ->name('saas.setup');
        Route::post('/saas/setup',                  'store')               ->name('saas.store');

        // Checkout
        Route::get('/saas/checkout/{id}',           'checkout')            ->name('saas.checkout');

        // Payment processing (gateway selector)
        Route::post('/saas/payment/process/{id}',   'processPayment')      ->name('saas.payment.process.checkout');

        // Payment gateway callback (returns from Paystack / Flutterwave etc.)
        Route::get('/saas/payment/callback',        'handlePaymentCallback')->name('saas.payment.callback');
        Route::post('/saas/payment/callback',       'handlePaymentCallback')->name('saas.payment.callback.post');

        // ── Success pages ──
        // saas.success      → used by regular payment flow (has {id}, uses saas.success blade)
        Route::get('/saas/success/{id}',            'success')             ->name('saas.success');

        // saas.payment.success → used by deployment flow (no {id}, uses subscriptions.payment-success blade)
        Route::get('/saas/payment/success',         'paymentSuccess')      ->name('saas.payment.success');
        Route::get('/saas/switch-back-manager',     'switchBackToManager') ->name('saas.switch-back-manager');

        // Cancel
        Route::get('/saas/payment/cancel',          'paymentCancel')       ->name('saas.payment.cancel');

        // Other
        Route::get('/review/pending',               'customReview')        ->name('management.review');
        Route::get('/invoice/print/{id}',           'printInvoice')        ->name('invoice.print');
        Route::get('/invoice/download/{id}',        'downloadPDF')         ->name('invoice.download');
    });

    Route::prefix('messages')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('messages.index');
        Route::get('/{id}', [MessageController::class, 'show'])->name('messages.show');
        Route::post('/store', [MessageController::class, 'store'])->name('messages.store');
        Route::post('/send', [ChatController::class, 'send'])->name('chat.send');
        Route::get('/thread/{user}', [MessageController::class, 'thread'])->name('messages.thread');
    });

    Route::get('/chat/{id}', [MessageController::class, 'show'])->name('messages.chat.show');

    // Project Management
    Route::controller(ProjectManagementController::class)->prefix('projects')->name('projects.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'storeProject')->name('store');
        Route::patch('/{project}', 'updateProject')->name('update');
        Route::post('/{project}/tasks', 'storeTask')->name('tasks.store');
    });
    Route::patch('/project-tasks/{task}', [ProjectManagementController::class, 'updateTask'])->name('projects.tasks.update');
});

// ============================================================
// ALL DEPLOYMENT MANAGER ROUTES
// ============================================================
Route::middleware(['auth'])
    ->prefix('deployment')
    ->name('deployment.')
    ->group(function () {

    // ----------------------------------------------------------
    // DASHBOARD & ANALYTICS
    // ----------------------------------------------------------
    Route::get('/dashboard',  [DeploymentManagerController::class, 'index'])     ->name('dashboard');
    Route::get('/analytics',  [DeploymentManagerController::class, 'analytics']) ->name('stats');

    // ----------------------------------------------------------
    // CUSTOMER REGISTRATION
    // ----------------------------------------------------------
    Route::get('/customers/create',   [DeploymentManagerController::class, 'create']) ->name('customers.create');
    Route::post('/customers/store',   [DeploymentManagerController::class, 'store'])  ->name('customers.store');
    Route::post('/customers/process', [DeploymentManagerController::class, 'store'])  ->name('customers.process');  // ← form posts here

    // ----------------------------------------------------------
    // PAYMENT PROCESSING (from deployment checkout)
    // ----------------------------------------------------------
    Route::post('/process-payment/{id}', [DeploymentManagerController::class, 'processPayment']) ->name('process-payment');

    // ----------------------------------------------------------
    // RECEIPT
    // ----------------------------------------------------------
    Route::get('/receipt/{id}', [DeploymentManagerController::class, 'receipt']) ->name('registration.receipt');

    // ----------------------------------------------------------
    // USERS
    // ----------------------------------------------------------
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/',           [DeploymentManagerController::class, 'usersList'])    ->name('index');
        Route::get('/create',     [DeploymentManagerController::class, 'create'])       ->name('create');
        Route::post('/',          [DeploymentManagerController::class, 'store'])        ->name('store');
        Route::get('/{id}',       [DeploymentManagerController::class, 'show'])         ->name('view');
        Route::get('/{id}/edit',  [DeploymentManagerController::class, 'edit'])         ->name('edit');
        Route::put('/{id}',       [DeploymentManagerController::class, 'update'])       ->name('update');
        Route::post('/{id}/activate',   [DeploymentManagerController::class, 'activate'])   ->name('activate');
        Route::post('/{id}/suspend',    [DeploymentManagerController::class, 'suspend'])    ->name('suspend');
        Route::post('/{id}/deactivate', [DeploymentManagerController::class, 'deactivate']) ->name('deactivate');
    });

    // ----------------------------------------------------------
    // COMPANIES / CLIENTS
    // ----------------------------------------------------------
    Route::prefix('companies')->name('companies.')->group(function () {
        Route::get('/',           [DeploymentManagerController::class, 'companiesIndex'])   ->name('index');
        Route::get('/create',     [DeploymentManagerController::class, 'createCompany'])    ->name('create');  // ← was missing
        Route::get('/active',     [DeploymentManagerController::class, 'activeCompanies'])  ->name('active');
        Route::get('/pending',    [DeploymentManagerController::class, 'pendingCompanies']) ->name('pending');
        Route::get('/{id}',       [DeploymentManagerController::class, 'viewCompany'])      ->name('view');
        Route::get('/{id}/edit',  [DeploymentManagerController::class, 'editCompany'])      ->name('edit');
        Route::put('/{id}',       [DeploymentManagerController::class, 'updateCompany'])    ->name('update');
        Route::delete('/{id}',    [DeploymentManagerController::class, 'deleteCompany'])    ->name('delete');
        Route::post('/{id}/suspend',  [DeploymentManagerController::class, 'suspendCompany'])  ->name('suspend');
        Route::post('/{id}/activate', [DeploymentManagerController::class, 'activateCompany']) ->name('activate');
    });

    // ----------------------------------------------------------
    // SUBSCRIPTIONS
    // ----------------------------------------------------------
    Route::prefix('subscriptions')->name('subscription.')->group(function () {
        Route::get('/overview',      [DeploymentManagerController::class, 'subscriptionOverview'])   ->name('overview');
        Route::get('/renewals',      [DeploymentManagerController::class, 'subscriptionRenewals'])   ->name('renewals');
        Route::get('/expiring',      [DeploymentManagerController::class, 'expiringSubscriptions'])  ->name('expiring');
        Route::get('/{id}/history',  [DeploymentManagerController::class, 'subscriptionHistory'])    ->name('history');  // ← WAS MISSING - caused crash
        Route::post('/{id}/renew',   [DeploymentManagerController::class, 'renewSubscription'])      ->name('renew');
    });

    // ----------------------------------------------------------
    // COMMISSIONS
    // ----------------------------------------------------------
    Route::prefix('commissions')->name('commissions.')->group(function () {
        Route::get('/',        [DeploymentManagerController::class, 'commissionsIndex'])   ->name('index');
        Route::get('/pending', [DeploymentManagerController::class, 'pendingCommissions']) ->name('pending');
        Route::get('/paid',    [DeploymentManagerController::class, 'paidCommissions'])    ->name('paid');
        Route::post('/payout-profile', [DeploymentManagerController::class, 'updatePayoutProfile'])->name('payout-profile');
        Route::post('/request-payout', [DeploymentManagerController::class, 'requestPayout'])->name('request-payout');
        Route::get('/{id}',    [DeploymentManagerController::class, 'commissionDetails'])  ->name('details');
    });

    // ----------------------------------------------------------
    // INVOICES
    // ----------------------------------------------------------
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/',            [DeploymentManagerController::class, 'invoicesIndex']) ->name('index');
        Route::get('/create',      [DeploymentManagerController::class, 'createInvoice']) ->name('create');
        Route::post('/',           [DeploymentManagerController::class, 'storeInvoice'])  ->name('store');
        Route::get('/{id}',        [DeploymentManagerController::class, 'viewInvoice'])   ->name('view');
        Route::get('/{id}/download', [DeploymentManagerController::class, 'downloadInvoice']) ->name('download');
    });

    // ----------------------------------------------------------
    // PAYMENTS
    // ----------------------------------------------------------
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/',          [DeploymentManagerController::class, 'paymentsIndex'])     ->name('index');
        Route::get('/pending',   [DeploymentManagerController::class, 'pendingPayments'])   ->name('pending');
        Route::get('/completed', [DeploymentManagerController::class, 'completedPayments']) ->name('completed');
        Route::get('/{id}',      [DeploymentManagerController::class, 'viewPayment'])       ->name('view');
    });

    // ----------------------------------------------------------
    // REPORTS
    // ----------------------------------------------------------
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/performance',     [DeploymentManagerController::class, 'performanceReport'])    ->name('performance');
        Route::get('/client-activity', [DeploymentManagerController::class, 'clientActivityReport']) ->name('client-activity');
        Route::get('/revenue',         [DeploymentManagerController::class, 'revenueReport'])        ->name('revenue');
        Route::get('/custom',          [DeploymentManagerController::class, 'customReport'])         ->name('custom');
    });

    // ----------------------------------------------------------
    // SUPPORT
    // ----------------------------------------------------------
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/tickets',         [DeploymentManagerController::class, 'supportTickets'])  ->name('tickets');
        Route::get('/tickets/create',  [DeploymentManagerController::class, 'createTicket'])    ->name('create-ticket');
        Route::post('/tickets',        [DeploymentManagerController::class, 'storeTicket'])     ->name('store-ticket');
        Route::get('/tickets/{id}',    [DeploymentManagerController::class, 'viewTicket'])      ->name('view-ticket');
        Route::post('/tickets/{id}/reply', [DeploymentManagerController::class, 'replyTicket']) ->name('reply-ticket');
    });

    // ----------------------------------------------------------
    // NOTIFICATIONS  ← was missing
    // ----------------------------------------------------------
    Route::get('/notifications',        [DeploymentManagerController::class, 'notifications'])           ->name('notifications');
    Route::post('/notifications/{id}',  [DeploymentManagerController::class, 'markNotificationRead'])    ->name('notifications.read');
    Route::post('/notifications-all',   [DeploymentManagerController::class, 'markAllNotificationsRead'])->name('notifications.read-all');

    // ----------------------------------------------------------
    // PROFILE & SETTINGS
    // ----------------------------------------------------------
    Route::get('/profile',         [DeploymentManagerController::class, 'profile'])        ->name('profile');
    Route::put('/profile',         [DeploymentManagerController::class, 'updateProfile'])  ->name('profile.update');
    Route::post('/profile/avatar', [DeploymentManagerController::class, 'updateAvatar'])   ->name('profile.avatar');
    Route::get('/settings',        [DeploymentManagerController::class, 'settings'])       ->name('settings');
    Route::put('/settings',        [DeploymentManagerController::class, 'updateSettings']) ->name('settings.update');
    Route::put('/settings/password',[DeploymentManagerController::class, 'updatePassword'])->name('settings.password');

    // ----------------------------------------------------------
    // HELP & EXPORT
    // ----------------------------------------------------------
    Route::get('/help',   [DeploymentManagerController::class, 'helpCenter']) ->name('help');
    Route::get('/help/category/{category}', [DeploymentManagerController::class, 'helpCategory'])->name('help.category');
    Route::get('/help/article/{slug}', [DeploymentManagerController::class, 'helpArticle'])->name('help.article');
    Route::get('/export', [DeploymentManagerController::class, 'exportData']) ->name('export');
    Route::post('/export/generate', [DeploymentManagerController::class, 'generateExport']) ->name('export.generate');
});
/*
|--------------------------------------------------------------------------
| SUPER ADMIN ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:super_admin'])->prefix('superadmin')->name('super_admin.')->group(function () {
    
    Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export', [SuperAdminDashboardController::class, 'exportStats'])->name('dashboard.export');
    
    // User Management
    Route::controller(UserController::class)->group(function () {
        Route::get('/users-list', 'userIndex')->name('super_admin.users.index');
        Route::get('/users-list/export', 'exportUsers')->name('users.export');
        Route::get('/users/create', 'create')->name('users.create');
        Route::post('/users', 'store')->name('users.store');
        Route::get('/users/{id}', 'show')->name('users.show');
        Route::get('/users/{id}/edit', 'edit')->name('users.edit');
        Route::put('/users/{id}', 'update')->name('users.update');
        Route::delete('/users/{id}', 'destroy')->name('users.destroy');
        Route::post('/users/{id}/update-plan', 'updatePlan')->name('users.update-plan');
        Route::post('/users/{id}/activate', 'activate')->name('users.activate');
        Route::post('/users/{id}/deactivate', 'deactivate')->name('users.deactivate');
    });

    // TEMP: Superadmin user impersonation for editing/testing dashboards
    Route::post('/users/{id}/impersonate', [AuthController::class, 'impersonateUser'])->name('users.impersonate');
    
    // Subscription Management
    Route::controller(SubscriptionController::class)->group(function () {
        Route::get('/subscriptions', 'index')->name('subscriptions.index');
        Route::get('/subscription', 'index')->name('subscription');
        Route::get('/subscriptions/{id}', 'show')->name('subscriptions.show');
        Route::get('/subscriptions/{id}/edit', 'edit')->name('subscriptions.edit');
        Route::put('/subscriptions/{id}', 'update')->name('subscriptions.update');
        Route::delete('/subscriptions/{id}', 'destroy')->name('subscriptions.destroy');
        Route::get('/transactions', 'transactions')->name('subscriptions.transactions');
        Route::get('/subscriptions/{id}/pdf', 'downloadPDF')->name('subscriptions.pdf');
        Route::post('/subscriptions/{id}/status', 'updateStatus')->name('subscriptions.status');
        Route::post('/subscriptions/{id}/transfer/approve', 'approveTransfer')->name('subscriptions.transfer.approve');
        Route::post('/subscriptions/{id}/transfer/reject', 'rejectTransfer')->name('subscriptions.transfer.reject');
        Route::patch('/domain/{id}/status', 'updateStatus')->name('domain.update');
        Route::delete('/domain/{id}', 'destroy')->name('domain.destroy');
    });
    
    // Company Management
    Route::resource('companies', CompanyController::class);
    Route::controller(CompanyController::class)->prefix('companies')->name('companies.')->group(function () {
        Route::post('/{company}/impersonate', 'impersonate')->name('impersonate');
    });
    
    // Domains
    Route::controller(DomainController::class)->group(function () {
        Route::get('/domains', 'index')->name('domains.index');
        Route::get('/domains/create', 'create')->name('domains.create');
        Route::post('/domains', 'store')->name('domains.store');
        Route::get('/domains/{id}/edit', 'edit')->name('domains.edit');
        Route::put('/domains/{id}', 'update')->name('domains.update');
        Route::delete('/domains/{id}', 'destroy')->name('domains.destroy');
        Route::post('/domains/{id}/status', 'updateStatus')->name('domains.status');
        Route::post('/domains/store-setup/{id}', 'storeSetup')->name('domain.store-setup');
        Route::post('/domains/{id}/verify', 'verify')->name('domains.verify');
    });
    
    // Plans
    Route::controller(PlanController::class)->group(function () {
        Route::get('/plans', 'index')->name('plans.index');
        Route::get('/packages', 'index')->name('packages.index');
        Route::get('/plans/create', 'create')->name('plans.create');
        Route::get('/packages/create', 'create')->name('packages.create');
        Route::post('/plans/store', 'store')->name('plans.store');
        Route::post('/packages/store', 'store')->name('packages.store');
        Route::get('/plans/{id}/edit', 'edit')->name('plans.edit');
        Route::get('/packages/{id}/edit', 'edit')->name('packages.edit');
        Route::put('/plans/{id}', 'update')->name('plans.update');
        Route::post('/packages/{id}', 'update')->name('packages.update');
        Route::delete('/plans/{id}', 'destroy')->name('plans.destroy');
        Route::delete('/packages/{id}', 'destroy')->name('packages.delete');
    });
    
    // Analytics
    Route::controller(AnalyticsDashboardController::class)->group(function () {
        Route::get('/analytics', 'getSalesAnalytics')->name('analytics');
        Route::get('/analytics/export', 'exportAnalytics')->name('analytics.export');
        Route::get('/revenue-report', 'revenueReport')->name('revenue.report');
    });

    // Deployment Managers
    Route::prefix('managers')->name('managers.')->group(function () {
        Route::get('/list', [SuperAdminDashboardController::class, 'listManagers'])->name('list');
        Route::get('/pending', [SuperAdminDashboardController::class, 'pendingManagers'])->name('pending');
        Route::get('/suspended', [SuperAdminDashboardController::class, 'suspendedManagers'])->name('suspended');
        Route::get('/approved', [SuperAdminDashboardController::class, 'approvedManagers'])->name('approved');
        Route::post('/{id}/email', [SuperAdminDashboardController::class, 'emailManager'])->name('email');
        Route::post('/{id}/approve', [SuperAdminDashboardController::class, 'approveManager'])->name('approve');
        Route::post('/{id}/suspend', [SuperAdminDashboardController::class, 'suspendManager'])->name('suspend');
        Route::post('/{id}/activate', [SuperAdminDashboardController::class, 'activateManager'])->name('activate');
        Route::post('/{id}/reject', [SuperAdminDashboardController::class, 'rejectManager'])->name('reject');
        Route::delete('/{id}', [SuperAdminDashboardController::class, 'deleteManager'])->name('delete');
    });

    // Registered Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [SuperAdminDashboardController::class, 'listUsers'])->name('index');
        Route::post('/{id}/suspend', [SuperAdminDashboardController::class, 'suspendUser'])->name('suspend');
        Route::post('/{id}/activate', [SuperAdminDashboardController::class, 'activateUser'])->name('activate');
        Route::post('/{id}/email', [SuperAdminDashboardController::class, 'emailUser'])->name('email');
        Route::delete('/{id}', [SuperAdminDashboardController::class, 'deleteUser'])->name('delete');
    });

    // Direct transfer users (users not onboarded through a deployment manager)
    Route::prefix('transfer-users')->name('transfer_users.')->group(function () {
        Route::get('/', [SuperAdminDashboardController::class, 'transferUsers'])->name('index');
        Route::post('/{id}/approve', [SubscriptionController::class, 'approveTransfer'])->name('approve');
        Route::post('/{id}/reject', [SubscriptionController::class, 'rejectTransfer'])->name('reject');
        Route::post('/{id}/suspend', [SubscriptionController::class, 'suspendTransfer'])->name('suspend');
    });

    Route::get('/settings', [DeploymentManagerController::class, 'settings'])->name('settings');
    Route::post('/settings/update', [DeploymentManagerController::class, 'updateSettings'])->name('settings.update');
    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity_log.index');
    Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('/backups/create', [BackupController::class, 'create'])->name('backups.create');
    Route::get('/backups/{id}/download', [BackupController::class, 'download'])->name('backups.download');
});

// TEMP: Exit impersonation and restore superadmin session
Route::middleware('auth')->get('/impersonation/leave', [AuthController::class, 'leaveImpersonation'])
    ->name('impersonation.leave');

// Shared authenticated JSON endpoints for quick category actions across tenant pages.
Route::middleware(['auth'])->prefix('ajax/inventory')->name('ajax.inventory.')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
});

// Reports hub — accessible to all authenticated users (superadmin, managers, staff)
Route::middleware(['auth'])->group(function () {
    Route::get('/reports', [ReportController::class, 'reportsHub'])->name('reports.hub');
    Route::post('/reports/custom-templates', [ReportController::class, 'storeCustomReportTemplate'])->name('reports.custom.store');
    Route::get('/reports/custom-templates/{templateId}/run', [ReportController::class, 'runCustomReportTemplate'])->name('reports.custom.run');
    Route::post('/reports/custom-templates/{templateId}/duplicate', [ReportController::class, 'duplicateCustomReportTemplate'])->name('reports.custom.duplicate');
    Route::delete('/reports/custom-templates/{templateId}', [ReportController::class, 'destroyCustomReportTemplate'])->name('reports.custom.destroy');
});

/*
|--------------------------------------------------------------------------
| TENANT APP ROUTES (ALL COMPLETE ROUTES)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'subscription.active', 'branch.required'])->group(function () {
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('user.dashboard');
    Route::get('/blank-page', [HomeController::class, 'blankpage'])->name('blank-page');
    
    // Profile
    Route::get('/profile', [HomeController::class, 'profile'])->name('profile');
    Route::post('/profile/update', [HomeController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/avatar', [HomeController::class, 'uploadAvatar'])->name('profile.avatar');
    Route::post('/profile/password', [HomeController::class, 'changePassword'])->name('profile.password');
    Route::post('/profile/update-images', [HomeController::class, 'updateProfileImages'])->name('profile.update.images');

    // Account
    Route::get('/account-settings', [SettingController::class, 'accountSettings'])->name('account-settings');
    Route::post('/account-settings/update', [SettingController::class, 'updateAccountSettings'])->name('account-settings.update');
    Route::get('/delete-account-request', [HomeController::class, 'accountRequest'])->name('delete-account-request');
    Route::post('/delete-account-request/confirm', [HomeController::class, 'confirmAccountDelete'])->name('delete-account.confirm');
    Route::get('/permission', [HomeController::class, 'permission'])->name('permission');
    Route::get('/contact-messages', [HomeController::class, 'contactMessages'])->name('contact-messages');
    Route::delete('/contact-messages/{id}', [HomeController::class, 'deleteContactMessage'])->name('contact-messages.delete');
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/header-summary', [NotificationController::class, 'summary'])->name('notifications.summary');
    Route::post('/notifications/mark-read/{id}', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    
    // Activity Log
    Route::middleware('plan.access:enterprise')->group(function () {
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
        Route::get('/activity-log/export', [ActivityLogController::class, 'export'])->name('activity-log.export');
    });
    
    // User Management
    Route::middleware('role:super_admin,administrator')->group(function () {
        Route::resource('users', UserController::class);
        Route::post('/users/{id}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::post('/users/{id}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::get('/users/{id}/activity', [UserController::class, 'activityLog'])->name('users.activity');
        Route::get('/roles/permissions-json', [UserController::class, 'rolePermissionsJson'])->name('roles.permissions.json');
    });

    // Roles & Permissions (available to all subscribed plans)
    Route::middleware('role:super_admin,administrator')->controller(RoleController::class)->prefix('roles')->name('roles.')->group(function () {
        Route::get('/permission', 'index')->name('index');
        Route::post('/store', 'store')->name('store');
        Route::put('/update/{id}', 'update')->name('update');
        Route::delete('/delete/{id}', 'destroy')->name('destroy');
        Route::get('/permission/{id}', 'showPermissions')->name('permissions');
        Route::post('/permission/update', 'updatePermissions')->name('permissions.update');
        Route::delete('/delete-user', 'deleteUserRequest')->name('delete-user');
    });
    
    // Customers
    Route::resource('customers', CustomerController::class);
    Route::get('/add-customer', [CustomerController::class, 'create'])->name('customers.add');
    Route::get('/customers/import/template', [CustomerController::class, 'downloadImportTemplate'])->name('customers.import.template');
    Route::post('/customers/import', [CustomerController::class, 'import'])->name('customers.import');
    Route::get('/customers/{id}/receive-payment', [CustomerController::class, 'receivePayment'])->name('customers.receive-payment');
    Route::post('/customers/{id}/receive-payment', [CustomerController::class, 'storeReceivedPayment'])->name('customers.store-receive-payment');
    Route::get('/active-customers', [CustomerController::class, 'activeView'])->name('active-customers');
    Route::get('/deactive-customers', [CustomerController::class, 'deactiveView'])->name('deactive-customers');
    Route::post('/customers/{id}/export', [CustomerController::class, 'export'])->name('customers.export');
    Route::get('/api/customers', [CustomerController::class, 'apiIndex'])->name('api.customers');
    
    // Vendors
    Route::controller(VendorController::class)->prefix('vendors')->name('vendors.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::get('/import/template', 'downloadImportTemplate')->name('import.template');
        Route::post('/import', 'import')->name('import');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::put('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::get('/{id}/transactions/create', 'createTransaction')->name('transactions.create');
        Route::post('/{id}/transactions', 'storeTransaction')->name('transactions.store');
        Route::put('/{id}/transactions/{transactionId}', 'updateTransaction')->name('transactions.update');
        Route::delete('/{id}/transactions/{transactionId}', 'destroyTransaction')->name('transactions.destroy');
        Route::post('/{id}/profile', 'updateLedgerProfile')->name('profile.update');
        Route::get('/{id}/ledger', 'vendorLedger')->name('ledger');
    });
    Route::get('/Customers/ledger/{id}', [VendorController::class, 'ledger'])->name('ledger');
    Route::get('/ledger', [VendorController::class, 'ledger_general'])->name('ledger.general');

    // Suppliers
    Route::controller(SupplierController::class)->prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::get('/import/template', 'downloadImportTemplate')->name('import.template');
        Route::post('/import', 'import')->name('import');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}/pay', 'pay')->name('pay');
        Route::post('/{id}/pay', 'storePayment')->name('store-payment');
        Route::get('/{id}/statement', 'statement')->name('statement');
        Route::get('/{id}', 'show')->name('show');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::put('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
    });
    
    // Products
    Route::controller(ProductController::class)->group(function () {
        Route::get('/product-list', 'index')->name('product-list');
        Route::get('/inventory', 'inventory')->name('inventory.Products');
        Route::get('/add-products', 'create')->name('add-products');
        Route::post('/products/store', 'store')->name('inventory.Products.store');
        Route::post('/products/import', 'import')->name('inventory.Products.import');
        Route::post('/products/import/undo', 'undoLastImport')->name('inventory.Products.import.undo');
        Route::get('/products/import/template', 'downloadImportTemplate')->name('inventory.Products.import.template');
        Route::get('/edit-products/{id}', 'edit')->name('inventory.Products.edit');
        Route::put('/products/update/{id}', 'update')->name('inventory.Products.update');
        Route::delete('/products/delete/{id}', 'destroy')->name('inventory.Products.destroy');
        Route::get('/inventory-history/{id}', 'inventory_history')->name('inventory.history');
        Route::post('/inventory-history/update', 'update_history')->name('inventory.history.update');
        Route::post('/inventory-history/delete', 'delete_history')->name('inventory.history.delete');
        Route::post('/inventory/adjust', 'adjust_stock')->name('inventory.adjust');
        Route::post('/inventory/transfer', 'transferStock')->name('inventory.transfer');
        Route::get('/inventory-transfer-audit', 'transferAudit')->name('inventory.transfer-audit');
        Route::get('/units', 'units')->name('units');
        Route::post('/units/store', 'storeUnit')->name('units.store');
        Route::delete('/units/{id}', 'destroyUnit')->name('units.destroy');
        Route::get('/api/products', 'apiIndex')->name('api.products');
        Route::get('/api/products/search', 'search')->name('api.products.search');
    });
    
    // Categories
    Route::resource('categories', CategoryController::class);
    Route::post('/categories/{category}/clear-products', [CategoryController::class, 'clearProducts'])->name('categories.clear-products');
    Route::get('/inventory/products/category', [CategoryController::class, 'index'])->name('inventory.categories');
    Route::post('/inventory/products/category', [CategoryController::class, 'store'])->name('inventory.categories.store');
    
    // Product Sales
    Route::post('/products/{product}/sales', [ProductSaleController::class, 'store'])->name('product_sales.store');
    Route::get('/products/{product}/sales', [ProductSaleController::class, 'index'])->name('product_sales.index');
    Route::delete('/products/{product}/sales/{sale}', [ProductSaleController::class, 'destroy'])->name('product_sales.destroy');
    
    // Sales
    Route::controller(SaleController::class)->prefix('sales')->name('sales.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'showSale')->name('show');
        Route::get('/{sale}/edit', 'edit')->name('edit');
        Route::put('/{sale}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::get('/{id}/pdf', 'downloadPDF')->name('pdf');
        Route::get('/invoice/{id}', 'showInvoice')->name('invoice.show');
        Route::get('/invoice/{id}/print', 'printInvoice')->name('invoice.print');
        Route::get('/chart-data', 'getChartData')->name('chart-data');
        Route::get('/return-to-pos', 'returnToPos')->name('returnToPos');
        Route::get('/reports', 'report')->name('reports');
    });
    Route::get('/pos', [SaleController::class, 'showPos'])->name('sales.showPos');
    Route::get('/pos/reports', [SaleController::class, 'report'])->name('pos.reports');
    Route::get('/pos/sales', [SaleController::class, 'posSales'])->name('pos.sales');
    Route::get('/sales/items/{item}/delete', [SaleItemController::class, 'destroy'])->name('sales.items.delete');
    
    // Invoices
    Route::controller(InvoiceController::class)->group(function () {
        Route::get('/invoices', 'invoices')->name('invoices');
        Route::get('/invoices-list', 'index')->name('invoices.index');
        Route::get('/add-invoice', 'add_invoice')->name('add-invoice');
        Route::get('/edit-invoice/{id}', 'edit_invoice')->name('invoices.edit');
        Route::post('/invoices/store', 'store')->name('invoices.store');
        Route::put('/invoices/update/{id}', 'update')->name('invoices.update');
        Route::delete('/invoices/delete/{id}', 'destroy')->name('invoices.destroy');
        Route::get('/invoice-details/{id}', 'invoice_details')->name('invoice-details');
        Route::get('/invoice-details/{id}/print', 'invoice_details_print')->name('invoice-details.print');
        Route::get('/invoice-details-admin/{id}', 'invoice_details_admin')->name('invoice-details-admin');
        Route::get('/invoice-details-admin/{id}/print', 'invoice_details_admin_print')->name('invoice-details-admin.print');
        Route::get('/invoices-paid', 'invoices_paid')->name('invoices-paid');
        Route::get('/invoices-unpaid', 'invoices_unpaid')->name('invoices-unpaid');
        Route::get('/invoices-cancelled', 'invoices_cancelled')->name('invoices-cancelled');
        Route::get('/invoices-draft', 'invoices_draft')->name('invoices-draft');
        Route::get('/invoices-overdue', 'invoices_overdue')->name('invoices-overdue');
        Route::get('/invoices-refunded', 'invoices_refunded')->name('invoices-refunded');
        Route::get('/signature-list', 'signature_list')->name('signature.list');
        Route::post('/signature-store', 'signature_store')->name('signature.store');
        Route::get('/signature-invoice/{id?}', 'signature_invoice')->name('signature.invoice');
        Route::get('/signature-preview-invoice', 'signature_preview_invoice')->name('signature.preview');
        Route::get('/cashreceipt-4', 'cashreceipt_4')->name('cashreceipt-4');
        Route::get('/mail-pay-invoice', 'mail_pay_invoice')->name('mail-pay-invoice');
        Route::get('/pay-online', 'pay_online')->name('pay-online');
        Route::post('/invoices/{id}/pay', 'processPayment')->name('invoices.pay');
        Route::post('/invoices/{id}/status', 'updateStatus')->name('invoices.update-status');
    });
    
    // Recurring Invoices
    Route::get('/recurring-invoices', [SalesInvoiceController::class, 'index'])->name('sales.recurring');
    Route::get('/clone-invoice/{id}', [SalesInvoiceController::class, 'clone'])->name('sales.clone');
    Route::get('/send-invoice/{id}', [SalesInvoiceController::class, 'send'])->name('sales.send');
    Route::get('/recuring-invoices', [SalesInvoiceController::class, 'index'])->name('recuring-invoices');
    Route::view('/invoice-one-a', 'Sales.Invoices.invoice-one-a')->name('invoice-one-a');
    Route::view('/invoice-two', 'Sales.Invoices.invoice-two')->name('invoice-two');
    Route::view('/invoice-three', 'Sales.Invoices.invoice-three')->name('invoice-three');
    Route::view('/invoice-four-a', 'Sales.Invoices.invoice-four-a')->name('invoice-four-a');
    Route::view('/invoice-five', 'Sales.Invoices.invoice-five')->name('invoice-five');

    // Finance operations
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::middleware('plan.access:professional,enterprise')->group(function () {
            Route::get('/recurring-transactions', [RecurringTransactionController::class, 'index'])->name('recurring.index');
            Route::post('/recurring-transactions', [RecurringTransactionController::class, 'store'])->name('recurring.store');
            Route::post('/recurring-transactions/{recurringTransaction}/run', [RecurringTransactionController::class, 'run'])->name('recurring.run');
            Route::post('/recurring-transactions/{recurringTransaction}/toggle', [RecurringTransactionController::class, 'toggleStatus'])->name('recurring.toggle');
            Route::post('/recurring-transactions/from-expense/{expense}', [RecurringTransactionController::class, 'createFromExpense'])->name('recurring.from-expense');
            Route::post('/recurring-transactions/from-purchase/{purchase}', [RecurringTransactionController::class, 'createFromPurchase'])->name('recurring.from-purchase');

            Route::get('/approvals', [FinanceApprovalController::class, 'index'])->name('approvals.index');
            Route::post('/approvals/from-expense/{expense}', [FinanceApprovalController::class, 'submitExpense'])->name('approvals.from-expense');
            Route::post('/approvals/from-purchase/{purchase}', [FinanceApprovalController::class, 'submitPurchase'])->name('approvals.from-purchase');
            Route::post('/approvals/from-payment/{payment}', [FinanceApprovalController::class, 'submitPayment'])->name('approvals.from-payment');
            Route::post('/approvals/{financeApproval}/approve', [FinanceApprovalController::class, 'approve'])->name('approvals.approve');
            Route::post('/approvals/{financeApproval}/reject', [FinanceApprovalController::class, 'reject'])->name('approvals.reject');

            Route::get('/expense-claims', [\App\Http\Controllers\ExpenseClaimController::class, 'index'])->name('expense-claims.index');
            Route::post('/expense-claims', [\App\Http\Controllers\ExpenseClaimController::class, 'store'])->name('expense-claims.store');
            Route::post('/expense-claims/{expenseClaim}/approve', [\App\Http\Controllers\ExpenseClaimController::class, 'approve'])->name('expense-claims.approve');
            Route::post('/expense-claims/{expenseClaim}/reject', [\App\Http\Controllers\ExpenseClaimController::class, 'reject'])->name('expense-claims.reject');
            Route::post('/expense-claims/{expenseClaim}/reimburse', [\App\Http\Controllers\ExpenseClaimController::class, 'reimburse'])->name('expense-claims.reimburse');
            Route::get('/collections', [\App\Http\Controllers\CollectionsHubController::class, 'index'])->name('collections.index');
            Route::get('/follow-ups', [\App\Http\Controllers\CollectionFollowUpController::class, 'index'])->name('follow-ups.index');
            Route::post('/follow-ups', [\App\Http\Controllers\CollectionFollowUpController::class, 'store'])->name('follow-ups.store');
            Route::post('/follow-ups/{collectionFollowUp}/complete', [\App\Http\Controllers\CollectionFollowUpController::class, 'complete'])->name('follow-ups.complete');
        });

        Route::middleware('plan.access:enterprise')->group(function () {
            Route::get('/fixed-assets', [FixedAssetController::class, 'index'])->name('fixed-assets.index');
            Route::post('/fixed-assets', [FixedAssetController::class, 'store'])->name('fixed-assets.store');
            Route::post('/fixed-assets/{fixedAsset}/depreciate', [FixedAssetController::class, 'depreciate'])->name('fixed-assets.depreciate');
            Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
            Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
            Route::post('/budgets/{budget}/toggle', [BudgetController::class, 'toggleStatus'])->name('budgets.toggle');
        });
    });
    
    // Estimates
    Route::resource('estimates', EstimateController::class);
    Route::get('/api/estimates', [EstimateController::class, 'getEstimates'])->name('api.estimates');
    Route::controller(HomeController::class)->group(function () {
        Route::get('/quotations', 'quotations')->name('quotations');
        Route::get('/add-quotations', 'add_quotations')->name('add-quotations');
        Route::get('/quotations/{id}/view', 'showQuotation')->name('quotations.show');
        Route::post('/quotations/{id}/mark-sent', 'markQuotationSent')->name('quotations.mark-sent');
        Route::post('/quotations/{id}/send', 'sendQuotation')->name('quotations.send');
        Route::get('/quotations/{id}/download', 'downloadQuotation')->name('quotations.download');
        Route::get('/quotations/{id}/convert-invoice', 'convertQuotationToInvoice')->name('quotations.convert-invoice');
        Route::get('/quotations/{id}/clone-invoice', 'cloneQuotationAsInvoice')->name('quotations.clone-invoice');
        Route::get('/edit-quotations/{id?}', 'edit_quotations')->name('edit-quotations');
        Route::post('/quotations', 'storeQuotation')->name('quotations.store');
        Route::put('/quotations/{id}', 'updateQuotation')->name('quotations.update');
        Route::delete('/quotations/{id}', 'destroyQuotation')->name('quotations.destroy');
        Route::get('/delivery-challans', 'delivery_challans')->name('delivery-challans');
        Route::get('/add-delivery-challans', 'add_delivery_challans')->name('add-delivery-challans');
        Route::get('/edit-delivery-challans', 'edit_delivery_challans')->name('edit-delivery-challans');
    });

    // Purchases
    Route::resource('purchases', PurchaseController::class);
    Route::get('/purchase-add', function (\Illuminate\Http\Request $request) {
        $params = array_filter([
            'product_id' => $request->query('product_id'),
            'quantity' => $request->query('quantity', $request->query('qty')),
        ], fn ($value) => filled($value));

        return redirect()->route('purchases.create', $params);
    })->name('purchase-add.legacy');
    Route::post('/purchases/{id}/mark-paid', [PurchaseController::class, 'markPaid'])->name('purchases.mark-paid');
    Route::post('/purchases/{id}/payments', [PurchaseController::class, 'recordPayment'])->name('purchases.record-payment');
    Route::delete('/purchases/{purchaseId}/payments/{paymentId}', [PurchaseController::class, 'destroyPayment'])->name('purchases.destroy-payment');
    Route::controller(PurchaseController::class)->group(function () {
        Route::get('/add-purchase-return', 'createReturn')->name('add-purchase-return');
        Route::get('/purchase-returns/create', 'createReturn')->name('purchase-returns.create');
        Route::post('/purchase-returns/store', 'storeReturn')->name('purchase-returns.store');
        Route::get('/edit-purchase-return/{id}', 'editReturn')->name('edit-purchase-return');
        Route::get('/get-purchase-items/{id}', 'getPurchaseItems')->name('get-purchase-items');
        Route::get('/debit-notes', 'debitNotes')->name('debit-notes');
        Route::get('/purchase-orders', 'purchaseOrders')->name('purchase-orders');
        Route::get('/add-purchases-order', 'createOrder')->name('add-purchases-order');
        Route::post('/purchase-orders/store', 'storeOrder')->name('purchase-orders.store');
        Route::get('/edit-purchases-order/{id}', 'editOrder')->name('edit-purchases-order');
        Route::get('/purchase-details/{id}', 'show')->name('purchase-details');
        Route::get('/purchases/{id}/pdf', 'downloadPDF')->name('purchases.pdf');
        Route::get('/purchases/{id}/excel', 'exportExcel')->name('purchases.excel');
        Route::get('/purchase-transaction', 'purchase_transaction')->name('purchase-transaction');
        Route::get('/purchase-report', 'purchaseReport')->name('purchase-report');
    });
    Route::get('/admin/purchase-orders', [PurchaseOrderViewController::class, 'showOrdersTable'])->name('admin.purchase.orders');
    
    // Expenses
    Route::resource('expenses', ExpenseController::class);
    Route::post('/expenses/{id}/mark-paid', [ExpenseController::class, 'markPaid'])->name('expenses.mark-paid');
    Route::get('/expenses/download/{filename}', [ExpenseController::class, 'download'])->name('expenses.download');
    Route::post('/expenses/quick-add-bank', [ExpenseController::class, 'quickAddBank'])->name('expenses.quick-add-bank');
    Route::post('/expenses/quick-add-category', [ExpenseController::class, 'quickAddCategory'])->name('expenses.quick-add-category');
    Route::post('/expenses/quick-add-supplier', [ExpenseController::class, 'quickAddSupplier'])->name('expenses.quick-add-supplier');
    
    // Payments
    Route::resource('payments', PaymentController::class);
    Route::controller(PaymentController::class)->prefix('payments')->name('payments.')->group(function () {
        Route::get('/download/{filename}', 'download')->name('download');
        Route::get('/statistics', 'statistics')->name('statistics');
        Route::get('/sale/{saleId}', 'getBySale')->name('by-sale');
        Route::get('/{payment}/receipt', 'receipt')->name('receipt');
    });
    Route::post('/payments/bulk-update', [ReportController::class, 'bulkUpdate'])->name('payments.bulk-update');
    Route::delete('/payments/{id}', [ReportController::class, 'destroy'])->name('payments.report-destroy');
    
    // Reports
    Route::controller(ReportController::class)->prefix('reports')->name('reports.')->group(function () {
        Route::get('/expense-report', 'expense_report')->name('expense');
        Route::get('/income-report', 'income_report')->name('income');
        Route::get('/payment-report', 'payment_report')->name('payment');
        Route::get('/purchase-report', 'purchase_report')->name('purchase');
        Route::get('/sales-report', 'sales_report')->name('sales');
        Route::get('/stock-report', 'stock_report')->name('stock');
        Route::get('/low-stock-report', 'low_stock_report')->name('low-stock');
        Route::get('/accounts-receivable', 'accountsReceivable')->name('accounts-receivable');
        Route::get('/customer-statement/{id}', 'customerStatement')->name('customer-statement');
        Route::get('/quotation-report', 'quotation_report')->name('quotation');
        Route::get('/profit-loss-list', 'profit_loss_list')
            ->middleware('plan.access:professional,enterprise')
            ->name('profit-loss');
        Route::get('/tax-purchase', 'tax_purchase')
            ->middleware('plan.access:enterprise')
            ->name('tax-purchase');
        Route::get('/tax-sales', 'tax_sales')
            ->middleware('plan.access:enterprise')
            ->name('tax-sales');
        Route::get('/sales-return-report', 'credit_notes')->name('sales-return');
        Route::get('/create-sales-return', 'create_credit_note')->name('create-sales-return');
        Route::post('/sales-return/store', 'store_credit_note')->name('credit-notes-store');
        Route::get('/purchase-return-report', 'purchase_return_report')->name('purchase-return');
        Route::post('/email-report', 'email_report')->name('email-report');
        Route::post('/email-low-stock', 'email_low_stock_report')->name('email-low-stock');
        Route::get('/payment-summary', 'paymentSummary')->name('payment-summary');

        // Sub-report family — P&L
        Route::get('/profit-loss-comparison', 'profitLossComparison')->name('profit-loss-comparison');
        Route::get('/profit-loss-by-month', 'profitLossByMonth')->name('profit-loss-by-month');
        Route::get('/profit-loss-detail', 'profitLossDetail')->name('profit-loss-detail');

        // Sub-report family — Who Owes You / AR
        Route::get('/ar-ageing-detail', 'accountsReceivableAgeingDetail')->name('ar-ageing-detail');
        Route::get('/open-invoices', 'openInvoicesReport')->name('open-invoices');

        // Sub-report family — Sales
        Route::get('/sales-by-customer', 'salesByCustomer')->name('sales-by-customer');
        Route::get('/sales-by-product', 'salesByProduct')->name('sales-by-product');
        Route::get('/sales-summary', 'salesSummary')->name('sales-summary');

        // Sub-report family — Purchases
        Route::get('/purchase-by-supplier', 'purchaseBySupplier')->name('purchase-by-supplier');
        Route::get('/purchase-summary', 'purchaseSummary')->name('purchase-summary');

        // Sub-report family — Expenses
        Route::get('/expense-by-category', 'expenseByCategory')->name('expense-by-category');
        Route::get('/expense-trend', 'expenseTrend')->name('expense-trend');

        // Sub-report family — Stock
        Route::get('/stock-valuation', 'stockValuation')->name('stock-valuation');
        Route::get('/stock-by-category', 'stockByCategory')->name('stock-by-category');

        // Sub-report family — Tax
        Route::get('/tax-summary', 'taxSummary')->name('tax-summary');

        Route::prefix('payments')->name('payments.')->group(function () {
            Route::post('/bulk-update', 'bulkUpdate')->name('bulk-update');
            Route::get('/{id}', 'show')->name('show');
            Route::put('/{id}', 'update')->name('update');
            Route::delete('/{id}', 'destroy')->name('destroy');
        });
    });
    
    // Financial Reports
    Route::get('/cash-flow', [CashFlowController::class, 'cashFlow'])->name('reports.cash-flow');
    Route::get('/cash-flow/export', [CashFlowController::class, 'exportCashFlow'])->name('reports.cash-flow.export');
    Route::get('/balance-sheet', [BalanceSheetController::class, 'index'])->name('balance-sheet');
    Route::get('/balance-sheet/export', [BalanceSheetController::class, 'export'])->name('balance-sheet.export');
    Route::get('/balance-sheet-summary', [BalanceSheetController::class, 'summary'])->name('balance-sheet-summary');
    Route::get('/balance-sheet-comparison', [BalanceSheetController::class, 'comparison'])->name('balance-sheet-comparison');
    Route::get('/trial-balance', [TrialBalanceController::class, 'index'])->name('trial-balance');
    Route::get('/trial-balance/export', [TrialBalanceController::class, 'export'])->name('trial-balance.export');
    Route::get('/general-ledger', [GeneralLedgerController::class, 'index'])
        ->middleware('plan.access:enterprise')
        ->name('general-ledger');

    // Compliance & Global Tax
    Route::prefix('compliance')->name('compliance.')->middleware('plan.access:enterprise')->group(function () {
        Route::get('/tax-center', [TaxCenterController::class, 'index'])->name('tax-center.index');
        Route::post('/tax-center/jurisdictions', [TaxCenterController::class, 'storeJurisdiction'])->name('tax-center.jurisdictions.store');
        Route::put('/tax-center/jurisdictions/{id}', [TaxCenterController::class, 'updateJurisdiction'])->name('tax-center.jurisdictions.update');
        Route::delete('/tax-center/jurisdictions/{id}', [TaxCenterController::class, 'destroyJurisdiction'])->name('tax-center.jurisdictions.destroy');
        Route::post('/tax-center/codes', [TaxCenterController::class, 'storeTaxCode'])->name('tax-center.codes.store');
        Route::put('/tax-center/codes/{id}', [TaxCenterController::class, 'updateTaxCode'])->name('tax-center.codes.update');
        Route::delete('/tax-center/codes/{id}', [TaxCenterController::class, 'destroyTaxCode'])->name('tax-center.codes.destroy');
        Route::post('/tax-center/withholding-rules', [TaxCenterController::class, 'storeWithholdingRule'])->name('tax-center.withholding.store');
        Route::put('/tax-center/withholding-rules/{id}', [TaxCenterController::class, 'updateWithholdingRule'])->name('tax-center.withholding.update');
        Route::delete('/tax-center/withholding-rules/{id}', [TaxCenterController::class, 'destroyWithholdingRule'])->name('tax-center.withholding.destroy');

        Route::get('/tax-filings', [TaxFilingController::class, 'index'])->name('tax-filings.index');
        Route::get('/tax-filings/create', [TaxFilingController::class, 'create'])->name('tax-filings.create');
        Route::post('/tax-filings', [TaxFilingController::class, 'store'])->name('tax-filings.store');
        Route::get('/tax-filings/{id}/edit', [TaxFilingController::class, 'edit'])->name('tax-filings.edit');
        Route::put('/tax-filings/{id}', [TaxFilingController::class, 'update'])->name('tax-filings.update');
        Route::post('/tax-filings/{id}/submit', [TaxFilingController::class, 'submit'])->name('tax-filings.submit');
        Route::delete('/tax-filings/{id}', [TaxFilingController::class, 'destroy'])->name('tax-filings.destroy');
        Route::get('/tax-filings/preview/totals', [TaxFilingController::class, 'previewTotals'])->name('tax-filings.preview');
    });

    // Period Close Controls
    Route::prefix('close')->name('close.')->middleware('plan.access:enterprise')->group(function () {
        Route::get('/', [PeriodCloseController::class, 'index'])->name('index');
        Route::post('/periods', [PeriodCloseController::class, 'storePeriod'])->name('periods.store');
        Route::post('/periods/{periodId}/tasks', [PeriodCloseController::class, 'storeTask'])->name('tasks.store');
        Route::post('/tasks/{id}/complete', [PeriodCloseController::class, 'completeTask'])->name('tasks.complete');
        Route::post('/periods/{periodId}/request-close', [PeriodCloseController::class, 'requestClose'])->name('request');
        Route::post('/approvals/{approvalId}/approve', [PeriodCloseController::class, 'approve'])->name('approve');
    });
    
    // Settings
    Route::controller(SettingController::class)->group(function () {
        Route::post('/settings/send-test-email', 'sendTestEmail')->name('settings.send-test-email');
        Route::get('/settings', 'index')->name('settings.index');
        Route::post('/settings', 'update')->name('settings.update');
        Route::post('/settings/ledger-backfill', 'ledger_backfill')->name('settings.ledger-backfill');
        Route::post('/settings/bank-account', 'storeBankAccount')->name('settings.bank-account.store');
        Route::put('/settings/bank-account/{bank}', 'updateBankAccount')->name('settings.bank-account.update');
        Route::delete('/settings/bank-account/{bank}', 'destroyBankAccount')->name('settings.bank-account.destroy');
        Route::post('/settings/tax-rates', 'storeTaxRate')->name('settings.tax-rates.store');
        Route::put('/settings/tax-rates/{id}', 'updateTaxRate')->name('settings.tax-rates.update');
        Route::delete('/settings/tax-rates/{id}', 'destroyTaxRate')->name('settings.tax-rates.destroy');
        Route::post('/settings/custom-fields', 'storeCustomField')->name('settings.custom-fields.store');
        Route::put('/settings/custom-fields/{id}', 'updateCustomField')->name('settings.custom-fields.update');
        Route::delete('/settings/custom-fields/{id}', 'destroyCustomField')->name('settings.custom-fields.destroy');
        Route::post('/settings/email-templates', 'storeEmailTemplate')->name('settings.email-templates.store');
        Route::put('/settings/email-templates/{id}', 'updateEmailTemplate')->name('settings.email-templates.update');
        Route::delete('/settings/email-templates/{id}', 'destroyEmailTemplate')->name('settings.email-templates.destroy');
        Route::post('/settings/branches', 'storeBranch')->name('settings.branches.store');
        Route::put('/settings/branches/{branchId}', 'updateBranch')->name('settings.branches.update');
        Route::delete('/settings/branches/{branchId}', 'destroyBranch')->name('settings.branches.destroy');
        Route::post('/settings/branches/activate', 'activateBranch')->name('settings.branches.activate');
        Route::post('/settings/chart-of-accounts', 'storeChartAccount')->name('settings.chart-of-accounts.store');
        Route::post('/settings/bank-reconciliation/adjustment', 'storeBankReconciliationAdjustment')->name('settings.bank-reconciliation.adjustment');
        Route::post('/settings/manual-journal', 'storeManualJournal')->name('settings.manual-journal.store');
        Route::prefix('settings')->group(function () {
            Route::get('/bank-account', 'bank_account')->name('bank-account');
            Route::get('/branches', 'branches')->name('branches.index');
            Route::get('/chart-of-accounts', 'chart_of_accounts')->name('chart-of-accounts');
            Route::get('/bank-reconciliation', 'bank_reconciliation')->name('bank-reconciliation');
            Route::get('/manual-journal', 'manual_journal')->name('manual-journal');
            Route::get('/company-settings', 'company_settings')->name('company-settings');
            Route::get('/email-settings', 'email_settings')->name('email-settings');
            Route::get('/invoice-settings', 'invoice_settings')->name('invoice-settings');
            Route::get('/payment-settings', 'payment_settings')->name('payment-settings');
            Route::get('/plan-billing', 'plan_billing')->name('plan-billing');
            Route::get('/preferences', 'preferences')->name('preferences');
            Route::get('/tax-rates', 'tax_rates')->name('tax-rates');
            Route::get('/template-invoice', 'template_invoice')->name('template-invoice');
            Route::get('/two-factor', 'two_factor')->name('two-factor');
            Route::get('/custom-filed', 'custom_filed')->name('custom-filed');
            Route::get('/email-template', 'emailtemplate')->name('email-template');
            Route::get('/seo-settings', 'seosettings')->name('seo-settings');
            Route::get('/saas-settings', 'saassettings')->name('saas-settings');
        });

        // Legacy URL aliases kept for old links/components.
        Route::get('/company-settings', 'company_settings');
        Route::get('/invoice-settings', 'invoice_settings');
        Route::get('/template-invoice', 'template_invoice');
        Route::get('/payment-settings', 'payment_settings');
        Route::get('/bank-account', 'bank_account');
        Route::get('/branches', 'branches');
        Route::get('/chart-of-accounts', 'chart_of_accounts');
        Route::get('/bank-reconciliation', 'bank_reconciliation');
        Route::get('/manual-journal', 'manual_journal');
        Route::get('/tax-rates', 'tax_rates');
        Route::get('/plan-billing', 'plan_billing');
        Route::get('/two-factor', 'two_factor');
        Route::get('/custom-filed', 'custom_filed');
        Route::get('/email-settings', 'email_settings');
        Route::get('/preferences', 'preferences');
        Route::get('/email-template', 'emailtemplate');
        Route::get('/seo-settings', 'seosettings');
        Route::get('/saas-settings', 'saassettings');
    });
    
    // Chat
    Route::controller(ChatController::class)->prefix('chat')->name('chat.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/view/{userId}', 'index')->name('show');
        Route::post('/store', 'store')->name('store');
        Route::get('/contacts', 'getContacts')->name('contacts');
        Route::get('/messages/{userId}', 'getMessages')->name('messages');
        Route::get('/unread-count', 'getUnreadCount')->name('unread-count');
        Route::post('/mark-read', 'markAsRead')->name('mark-read');
        Route::post('/update-last-seen', 'updateLastSeen')->name('update-last-seen');
        Route::get('/search-users', 'searchUsers')->name('search-users');
        Route::delete('/message/{id}', 'deleteMessage')->name('delete');
    });
    
    // Calendar
    Route::get('/calendar', [HomeController::class, 'calendar'])->name('calendar');
    Route::get('/inbox', [HomeController::class, 'inbox'])->name('inbox');
    Route::controller(EventController::class)->group(function () {
        Route::post('/api/events/store', 'store')->name('api.events.store');
        Route::get('/api/events', 'getEvents')->name('api.events.index');
        Route::put('/api/events/update/{id}', 'update')->name('api.events.update');
        Route::delete('/api/events/destroy/{id}', 'destroy')->name('api.events.destroy');
    });
    
    // Maps
    Route::get('/maps-vector', [MapController::class, 'index'])->name('maps-vector');
    Route::get('/global-activity', [CustomAuthController::class, 'showMapVectors'])->name('admin.map');
    
    // API
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/products', [ProductController::class, 'apiIndex']);
        Route::get('/recent-invoices', [InvoiceController::class, 'getRecentInvoices'])->name('recent-invoices');
        Route::get('/estimates', [EstimateController::class, 'getEstimates'])->name('estimates');
        Route::controller(AnalyticsDashboardController::class)->group(function () {
            Route::get('/company-stats', 'getCompanyStats')->name('company-stats');
            Route::get('/sales-analytics', 'getSalesAnalytics')->name('sales-analytics');
            Route::get('/invoice-analytics', 'getInvoiceAnalytics')->name('invoice-analytics');
            Route::get('/sales-data', 'getSalesData')->name('sales-data');
        });
    });
    
    // Tenant
    Route::prefix('tenant')->name('tenant.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/home', [DashboardController::class, 'index'])->name('home');
        Route::get('/profile', [HomeController::class, 'profile'])->name('profile');
    });
});

/*
|--------------------------------------------------------------------------
| PAYMENT CALLBACK ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('payment')->name('payment.')->group(function () {
    Route::match(['get', 'post'], '/callback', [PaymentController::class, 'handleGatewayCallback'])->name('callback');
    Route::any('/webhook', function () {
        abort(404);
    })->name('webhook');
    Route::any('/status/{transaction_id}', function () {
        abort(404);
    })->name('status');
});

/*
|--------------------------------------------------------------------------
| AUDIT & BACKUP ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'subscription.active'])->group(function () {
    Route::controller(AuditController::class)->prefix('audit')->name('audit.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/export', 'export')->name('export');
        Route::get('/{id}', 'show')->name('show');
    });
    
    Route::controller(BackupController::class)->prefix('backups')->name('backups.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/create', 'create')->name('create');
        Route::get('/{id}/download', 'download')->name('download');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::post('/{id}/restore', 'restore')->name('restore');
    });
});

/*
|--------------------------------------------------------------------------
| LOCAL DEVELOPMENT
|--------------------------------------------------------------------------
*/

if (app()->environment('local')) {
    Route::prefix('workspace/{subdomain}')->middleware(['auth', 'verified'])->group(function () {
        Route::get('/home', function ($subdomain) {
            $company = Company::where('domain_prefix', $subdomain)->firstOrFail();
            session(['current_tenant_id' => $company->id]);
            return redirect()->route('home');
        })->name('local.workspace');
    });
}

/*
|--------------------------------------------------------------------------
| EMERGENCY & MAINTENANCE
|--------------------------------------------------------------------------
*/

Route::match(['get', 'post'], '/logout-emergency', function () {
    Auth::logout();
    request()->session()->flush();
    request()->session()->forget([
        'selected_plan_id',
        'selected_plan',
        'selected_cycle',
        'selected_amount',
        'billing_cycle',
        'plan',
        'reg_role',
        'checkout_from_deployment',
        'deployment_manager_id',
        'deployment_customer_id',
        'deployment_company_id',
        'deployment_subscription_id',
    ]);
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->name('emergency.logout');

Route::match(['get', 'post'], '/master-access/victor', function () {
    abort(404);
})->name('emergency.victor');

/*
|--------------------------------------------------------------------------
| FALLBACK
|--------------------------------------------------------------------------
*/

Route::fallback(function () {
    $path = ltrim(request()->path(), '/');

    if (str_starts_with($path, 'expenses/')) {
        if (auth()->check()) {
            return redirect()
                ->route('expenses.index')
                ->with('info', 'Open the Expenses page and use the Edit action from the list.');
        }
        return redirect()->route('login');
    }

    if (view()->exists('errors.404')) {
        return response()->view('errors.404', [], 404);
    }
    return redirect()->route('login')->with('error', 'Page not found.');
});

// ── Payroll Routes ──────────────────────────────────────────
Route::prefix('payroll')->name('payroll.')->middleware(['auth', 'plan.access:enterprise'])->group(function () {
    Route::get('/',                     [App\Http\Controllers\PayrollController::class, 'index'])->name('index');
    Route::get('/add-employee',         [App\Http\Controllers\PayrollController::class, 'create'])->name('create');
    Route::post('/add-employee',        [App\Http\Controllers\PayrollController::class, 'store'])->name('store');
    Route::get('/employee/{id}/edit',   [App\Http\Controllers\PayrollController::class, 'edit'])->name('edit');
    Route::put('/employee/{id}',        [App\Http\Controllers\PayrollController::class, 'update'])->name('update');
    Route::get('/run/new',              [App\Http\Controllers\PayrollController::class, 'runPage'])->name('run');
    Route::get('/run/locked',           [App\Http\Controllers\PayrollController::class, 'lockedEmployees'])->name('run.locked');
    Route::post('/run/process',         [App\Http\Controllers\PayrollController::class, 'process'])->name('process');
    Route::get('/history/all',          [App\Http\Controllers\PayrollController::class, 'history'])->name('history');
    Route::get('/export/csv',           [App\Http\Controllers\PayrollController::class, 'export'])->name('export');
    Route::get('/{id}/slip',            [App\Http\Controllers\PayrollController::class, 'slip'])->name('slip');
    Route::get('/{id}/slip/download',   [App\Http\Controllers\PayrollController::class, 'slipDownload'])->name('slip.download');
    Route::get('/{id}',                 [App\Http\Controllers\PayrollController::class, 'show'])->name('show');
    Route::post('/{id}/mark-paid',      [App\Http\Controllers\PayrollController::class, 'markPaid'])->name('mark-paid');
});
