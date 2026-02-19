<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller; // Generally not needed if extending base Controller
use App\Models\Estimate;             // <--- FIX 1: MUST import the Estimate Model
use App\Models\Invoice;              // Already correctly imported
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

// Removed: use App\Http\Controllers\EstimateController; 
// Explanation: If Estimate is a model, we don't need to import its controller here.

class PageController extends Controller
{
    /**
     * Display the main dashboard page with summary data.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Fetch latest invoices (Model: App\Models\Invoice)
        $invoices = Invoice::with('customer')->latest()->take(5)->get();
        
        // Fetch latest estimates (Model: App\Models\Estimate)
        // This is the line that required the 'use App\Models\Estimate;' statement.
        $estimates = Estimate::with('customer')->latest()->take(5)->get(); 
        
        // Fetch latest notifications for the authenticated user
        $notifications = DatabaseNotification::where('notifiable_id', Auth::id())
            // The notifiable_type should usually be dynamically resolved via the model's class name,
            // but the hardcoded string is retained for consistency with common setups.
            ->where('notifiable_type', 'App\\Models\\User')
            ->latest()
            ->take(5)
            ->get();

        return view('superadmin.dashboard', compact('invoices', 'plans', 'estimates', 'notifications'));
    }

    

    // --- Other methods returning views (retained original logic) ---

    public function account() { return view('UserManagement/delete-account-request'); }
    public function users() { return view('UserManagement/users'); }
    public function permission() { return view('UserManagement/permission'); }
    public function roles() { return view('UserManagement/roles-permission'); }
    public function contact() { return view('Support/contact-messages'); }
    public function details() { return view('Support/Tickets/ticket-details'); }
    public function closed() { return view('Support/Tickets/tickets-closed'); }
    public function kanban() { return view('Support/Tickets/tickets-kanban'); }
    public function list_closed() { return view('Support/Tickets/tickets-list-closed'); }
    public function list_open() { return view('Support/Tickets/tickets-list-open'); }
    public function list_pending() { return view('Support/Tickets/tickets-list-pending'); }
    public function list_resolved() { return view('Support/Tickets/tickets-list-resolved'); }
    public function list() { return view('Support/Tickets/tickets'); }
    public function open() { return view('Support/Tickets/tickets-open'); }
    public function pending() { return view('Support/Tickets/tickets-pending'); }
    public function resolved() { return view('Support/Tickets/tickets-resolved'); }
    public function tickets() { return view('Support/Tickets/tickets'); }
    public function companies() { return view('SuperAdmin/companies'); }
    public function domain_request() { return view('SuperAdmin/domain-request'); }
    public function domain() { return view('SuperAdmin/domain'); }
    public function packages() { return view('SuperAdmin/packages'); }
    //public function purchase_transaction() { return view('SuperAdmin/purchase-transaction'); }
    public function subscription() { return view('SuperAdmin/subscription'); }
    public function mail_pay_invoice() { return view('Signature/mail-pay-invoice'); }
    public function pay_online() { return view('Signature/pay-online'); }
    public function signature_invoice() { return view('Signature/signature-invoice'); }
    public function signature_list() { return view('Signature/signature-list'); }
    public function signature_preview_invoice() { return view('Signature/signature-preview-invoice'); }
    public function add_delivery_challans() { return view('Quotations/add-delivery-challans'); }
    public function add_quotations() { return view('Quotations/add-quotations'); }
    public function delivery_challans() { return view('Quotations/delivery-challans'); }
    public function edit_delivery_challans() { return view('Quotations/edit-delivery-challans'); }
    public function edit_quotations() { return view('Quotations/edit-quotations'); }
    public function quotations() { return view('Quotations/quotations'); }
    public function payment_summary() { return view('Reports/payment-summary'); }
    public function expense_report() { return view('Reports/Reports/expense-report'); }
    public function income_report() { return view('Reports/Reports/income-report'); }
    public function low_stock_report() { return view('Reports/Reports/low-stock-report'); }
    public function payment_report() { return view('Reports/Reports/payment-report'); }
    public function purchase_report() { return view('Reports/Reports/purchase-report'); }
    public function quotation_report() { return view('Reports/Reports/quotation-report'); }
    public function sales_report() { return view('Reports/Reports/sales-report'); }
    public function sales_return_report() { return view('Reports/Reports/sales-return-report'); }
    public function stock_report() { return view('Reports/Reports/stock-report'); }
    public function purchase_return() { return view('Reports/Reports/purchase-return'); }
    public function profit_loss_list() { return view('Reports/Reports/profit-loss-list'); }
    public function tax_purchase() { return view('Reports/Reports/tax-purchase'); }
    public function tax_sales() { return view('Reports/Reports/tax-sales'); }
    public function add_credit_notes() { return view('Sales/add-credit-notes'); }
    public function credit_notes() { return view('Sales/credit-notes'); }
    public function edit_credit_notes() { return view('Sales/edit-credit-notes'); }
    public function recurring_invoices() { return view('Sales/recurring-invoices'); }
    public function add_invoice() { return view('Sales/Invoices/add-invoice'); }
    public function cashreceipt_1() { return view('Sales/Invoices/cashreceipt-1'); }
    public function cashreceipt_2() { return view('Sales/Invoices/cashreceipt-2'); }
    public function cashreceipt_3() { return view('Sales/Invoices/cashreceipt-3'); }
    public function cashreceipt_4() { return view('Sales/Invoices/cashreceipt-4'); }
    public function edit_invoice() { return view('Sales/Invoices/edit-invoice'); }
    public function invoice_details_admin() { return view('Sales/Invoices/invoice-details-admin'); }
    public function invoice_details() { return view('Sales/Invoices/invoice-details'); }
    public function invoice_five() { return view('Sales/Invoices/invoice-five'); }
    public function invoice_four_a() { return view('Sales/Invoices/invoice-four-a'); }
    public function invoice_one_a() { return view('Sales/Invoices/invoice-one-a'); }
    public function invoice_template() { return view('Sales/Invoices/invoice-template'); }
    public function invoice_three() { return view('Sales/Invoices/invoice-three'); }
    public function invoice_two() { return view('Sales/Invoices/invoice-two'); }
    public function invoices_cancelled() { return view('Sales/Invoices/invoices-cancelled'); }
    public function invoices_draft() { return view('Sales/Invoices/invoices-draft'); }
    public function invoices_overdue() { return view('Sales/Invoices/invoices-overdue'); }
    public function invoices_paid() { return view('Sales/Invoices/invoices-paid'); }
    public function invoices_recurring() { return view('Sales/Invoices/invoices-recurring'); }
    public function invoices_refunded() { return view('Sales/Invoices/invoices-refunded'); }
    public function invoices_unpaid() { return view('Sales/Invoices/invoices-unpaid'); }
    public function bank_account() { return view('Settings/bank-account'); }
    public function company_settings() { return view('Settings/company-settings'); }
    public function email_settings() { return view('Settings/email-settings'); }
    public function invoice_settings() { return view('Settings/invoice-settings'); }
    public function payment_settings() { return view('Settings/payment-settings'); }
    public function plan_billing() { return view('Settings/plan-billing'); }
    public function preferences() { return view('Settings/preferences'); }
    public function settings() { return view('Settings/settings'); }
    public function tax_rates() { return view('Settings/tax-rates'); }
    public function template_invoice() { return view('Settings/template-invoice'); }
    public function two_factor() { return view('Settings/two-factor'); }
    public function custom_filed() { return view('Settings/custom-filed'); }
    public function accordions() { return view('UiInterface/BaseUi/accordions'); }
    public function alerts() { return view('UiInterface/BaseUi/alerts'); }
    public function avatar() { return view('UiInterface/BaseUi/avatar'); }
    public function badges() { return view('UiInterface/BaseUi/badges'); }
    public function buttongroup() { return view('UiInterface/BaseUi/buttongroup'); }
    public function breadcrumbs() { return view('UiInterface/BaseUi/breadcrumbs'); }
    public function buttons() { return view('UiInterface/BaseUi/buttons'); }
    public function cards() { return view('UiInterface/BaseUi/cards'); }
    public function carousel() { return view('UiInterface/BaseUi/carousel'); }
    public function dropdowns() { return view('UiInterface/BaseUi/dropdowns'); }
    public function grid() { return view('UiInterface/BaseUi/grid'); }
    public function images() { return view('UiInterface/BaseUi/images'); }
    public function lightbox() { return view('UiInterface/BaseUi/lightbox'); }
    public function media() { return view('UiInterface/BaseUi/media'); }
    public function modal() { return view('UiInterface/BaseUi/modal'); }
    public function offcanvas() { return view('UiInterface/BaseUi/offcanvas'); }
    public function pagination() { return view('UiInterface/BaseUi/pagination'); }
    public function placeholders() { return view('UiInterface/BaseUi/placeholders'); }
    public function popover() { return view('UiInterface/BaseUi/popover'); }
    public function progress() { return view('UiInterface/BaseUi/progress'); }
    public function rangeslider() { return view('UiInterface/BaseUi/rangeslider'); }
    public function spinners() { return view('UiInterface/BaseUi/spinners'); }
    public function sweetalerts() { return view('UiInterface/BaseUi/sweetalerts'); }
    public function tab() { return view('UiInterface/BaseUi/tab'); }
    public function toastr() { return view('UiInterface/BaseUi/toastr'); }
    public function tooltip() { return view('UiInterface/BaseUi/tooltip'); }
    public function typography() { return view('UiInterface/BaseUi/typography'); }
    public function video() { return view('UiInterface/BaseUi/video'); }
    public function chart_apex() { return view('UiInterface/Charts/chart-apex'); }
    public function chart_c3() { return view('UiInterface/Charts/chart-c3'); }
    public function chart_flot() { return view('UiInterface/Charts/chart-flot'); }
    public function chart_js() { return view('UiInterface/Charts/chart-js'); }
    public function chart_morris() { return view('UiInterface/Charts/chart-morris'); }
    public function chart_peity() { return view('UiInterface/Charts/chart-peity'); }
    public function clipboard() { return view('UiInterface/Elements/clipboard'); }
    public function counter() { return view('UiInterface/Elements/counter'); }
    public function drag_drop() { return view('UiInterface/Elements/drag-drop'); }
    public function form_wizard() { return view('UiInterface/Elements/form-wizard'); }
    public function horizontal_timeline() { return view('UiInterface/Elements/horizontal-timeline'); }
    public function notification() { return view('UiInterface/Elements/notification'); }
    public function rating() { return view('UiInterface/Elements/rating'); }
    public function ribbon() { return view('UiInterface/Elements/ribbon'); }
    public function scrollbar() { return view('UiInterface/Elements/scrollbar'); }
    public function stickynote() { return view('UiInterface/Elements/stickynote'); }
    public function text_editor() { return view('UiInterface/Elements/text-editor'); }
    public function timeline() { return view('UiInterface/Elements/timeline'); }
    public function form_basic_inputs() { return view('UiInterface/Forms/form-basic-inputs'); }
    public function form_fileupload() { return view('UiInterface/Forms/form-fileupload'); }
    public function form_horizontal() { return view('UiInterface/Forms/form-horizontal'); }
    public function form_input_groups() { return view('UiInterface/Forms/form-input-groups'); }
    public function form_select2() { return view('UiInterface/Forms/form-select2'); }
    public function form_vertical() { return view('UiInterface/Forms/form-vertical'); }
    public function form_mask() { return view('UiInterface/Forms/form-mask'); }
    public function form_validation() { return view('UiInterface/Forms/form-validation'); }
    public function icon_fontawesome() { return view('UiInterface/Icons/icon-fontawesome'); }
    public function icon_feather() { return view('UiInterface/Icons/icon-feather'); }
    public function icon_flag() { return view('UiInterface/Icons/icon-flag'); }
    public function icon_ionic() { return view('UiInterface/Icons/icon-ionic'); }
    public function icon_material() { return view('UiInterface/Icons/icon-material'); }
    public function icon_pe7() { return view('UiInterface/Icons/icon-pe7'); }
    public function icon_simpleline() { return view('UiInterface/Icons/icon-simpleline'); }
    public function icon_themify() { return view('UiInterface/Icons/icon-themify'); }
    public function icon_typicon() { return view('UiInterface/Icons/icon-typicon'); }
    public function icon_weather() { return view('UiInterface/Icons/icon-weather'); }
    public function data_tables() { return view('UiInterface/Tables/data-tables'); }
    public function tables_basic() { return view('UiInterface/Tables/tables-basic'); }
}