# SmartProbook Accounting Feature Gap Audit

Last updated: 2026-04-27

This document maps the current codebase against the requested "full accounting app"
feature list. Status values:

- `Present`: clear code/routes/controllers/views already exist
- `Partial`: some support exists, but the feature is incomplete, shallow, or not
  fully integrated
- `Not Found`: no clear implementation was found in the current codebase scan

## 1. Core Accounting Engine

| Feature | Status | Notes |
|---|---|---|
| Double-entry ledger enforcement | Present | `LedgerService`, `JournalService`, `transactions` posting paths |
| Chart of accounts management | Present | `AccountController`, accounts model/report usage |
| Journal entries (manual/auto) | Present | Manual journal routes in `SettingController`; auto-posting in `LedgerService` |
| Reversing journal entries | Partial | Some operational re-post/delete logic exists, but no clear generic reversing journal workflow |
| Recurring journal entries | Partial | `RecurringTransactionController` exists, but not a dedicated recurring journal module |
| Trial balance | Present | `TrialBalanceController`, `TrialBalanceExport` |
| Balance sheet | Present | `BalanceSheetController`, `BalanceSheetExport` |
| Profit & loss | Present | Profit/loss routes and reports exist |
| Cash flow statement | Present | `CashFlowController` |
| Retained earnings | Present | Calculated inside balance sheet logic |
| General ledger drill-down | Present | `GeneralLedgerController` |
| Audit trail / activity logs | Present | `AuditController`, `ActivityLogController`, activity views |
| Locked accounting periods | Present | `PeriodCloseController`, `AccountingPeriod` model, close views |
| Fiscal year closing | Partial | Period close exists, but full fiscal-year close workflow is not obvious |
| Opening balances | Present | Customer/supplier/account opening balance support exists |
| Multi-currency | Partial | Currency formatting/config exists; full transactional multi-currency is not obvious |
| Exchange rate management | Not Found | No clear exchange-rate admin module found |
| Revaluation / unrealized gains-losses | Not Found | No clear accounting revaluation engine found |
| Consolidation adjustments | Not Found | No explicit consolidation adjustment workflow found |

## 2. Banking & Cash Management

| Feature | Status | Notes |
|---|---|---|
| Bank account management | Present | Bank/account models and payment integration |
| Bank reconciliation | Not Found | No clear reconciliation workflow/controller found |
| Statement import (CSV/OFX/MT940) | Not Found | No bank statement import module found |
| Auto-match reconciliation rules | Not Found | No matching rules engine found |
| Cashbook | Partial | Payments/cash reporting exist, but no clear dedicated cashbook module |
| Petty cash management | Partial | Petty cash account usage exists; dedicated workflow unclear |
| Fund transfers between accounts | Partial | Some internal movement/account support exists, but no clear transfer module |
| Cheque management | Not Found | No clear cheque register/workflow found |
| Bank charges handling | Not Found | No dedicated bank charge workflow found |
| Loan / overdraft tracking | Not Found | No clear module found |

## 3. Sales / Receivables

| Feature | Status | Notes |
|---|---|---|
| Quotations / estimates | Present | `EstimateController`, quotation routes/views |
| Sales orders | Partial | Sales exist, but dedicated order lifecycle is not clear |
| Invoicing | Present | `InvoiceController`, invoice views/routes |
| Credit notes | Present | Credit note routes/report hooks exist |
| Debit notes | Present | Purchase debit note/report hooks exist |
| Customer statements | Present | Customer statement/report views exist |
| Receipts / payment allocation | Present | `PaymentController`, customer payment allocation logic exists |
| A/R aging report | Present | Collections hub / A/R report routes and views exist |
| Customer price lists | Not Found | No dedicated price list module found |
| Discounts / promotions | Partial | Discounts exist on transactions; promo engine not evident |
| Sales returns | Present | Sales return / credit note posting exists |
| Customer deposits / advances | Partial | Customer payments/opening allocation exists; dedicated deposit workflow unclear |
| Subscription / recurring billing | Present | Subscription system and recurring invoices exist |

## 4. Purchases / Payables

| Feature | Status | Notes |
|---|---|---|
| Purchase requisitions | Not Found | No requisition workflow found |
| RFQ / vendor quotations | Not Found | No RFQ module found |
| Purchase orders | Partial | `PurchaseOrderViewController` exists, but full PO lifecycle unclear |
| Goods received notes | Not Found | No GRN workflow found |
| Vendor bills | Partial | Purchases function as bills, but dedicated bill workflow is unclear |
| Debit/credit adjustments | Present | Debit notes/credit adjustments routes and reports exist |
| Vendor payments | Present | Supplier payment flows exist |
| A/P aging report | Present | Collections hub / supplier reporting exists |
| Supplier statements | Present | Supplier statement views/controllers exist |
| Prepayments / deposits | Partial | Supplier payment support exists; dedicated prepayment module unclear |
| Purchase returns | Present | Purchase return logic exists |

## 5. Inventory / Warehouse

| Feature | Status | Notes |
|---|---|---|
| Item master / SKU management | Present | `ProductController`, SKU support |
| Multiple warehouses | Partial | Branch stock exists; true warehouse entity/workflow not obvious |
| Stock transfers | Present | Transfer audit and stock transfer logic exist |
| Stock adjustments | Present | Inventory history/stock-in flows exist |
| Batch / lot tracking | Not Found | No clear lot tracking found |
| Serial number tracking | Not Found | No clear serial tracking found |
| Barcode support | Not Found | No clear barcode module found |
| Reorder levels | Not Found | No clear reorder threshold workflow found |
| Stock valuation (FIFO/Weighted Avg) | Not Found | No valuation method engine found |
| Landed cost allocation | Not Found | No landed cost allocation found |
| Assemblies / kits | Not Found | No explicit kit/assembly module found |
| Bill of materials (BOM) | Not Found | No BOM module found |
| Manufacturing / production orders | Not Found | No production order module found |
| Stock counts / cycle counts | Partial | Inventory history/audits exist; dedicated count workflow unclear |

## 6. Fixed Assets

| Feature | Status | Notes |
|---|---|---|
| Asset register | Present | `FixedAssetController` |
| Asset categories | Partial | Likely available inside fixed assets, but not fully verified |
| Depreciation schedules | Partial | Fixed asset support exists; full schedule engine not confirmed |
| Multiple depreciation methods | Not Found | Not clearly found |
| Asset disposal | Partial | Fixed asset module exists; disposal workflow not fully verified |
| Asset transfer | Partial | Transfer support not clearly confirmed |
| Revaluation | Not Found | No explicit asset revaluation workflow found |
| Maintenance logs | Not Found | No maintenance log workflow found |

## 7. Payroll / HR

| Feature | Status | Notes |
|---|---|---|
| Employee records | Present | `PayrollController` employee routes |
| Payroll processing | Present | Payroll run/process routes |
| Salary structures | Partial | Payroll exists, but salary structure module not fully verified |
| Allowances / deductions | Partial | Payroll support suggests this, but full module not confirmed |
| Pension / tax / statutory deductions | Partial | Tax/payroll support exists, but full deduction engine not confirmed |
| Payslip generation | Present | Payroll slip routes |
| Leave management | Not Found | No leave module found |
| Attendance / time tracking | Not Found | No attendance module found |
| Expense claims | Present | `ExpenseClaimController` |
| Payroll journal posting | Partial | Likely possible, but not clearly verified in scan |

## 8. Budgeting / Planning

| Feature | Status | Notes |
|---|---|---|
| Budget creation | Present | `BudgetController` |
| Budget by department/project | Partial | Budgeting exists; deeper slicing unclear |
| Budget vs actual reporting | Partial | Likely partial from budgets and reports |
| Forecasting | Not Found | No dedicated forecasting module found |
| Scenario planning | Not Found | No scenario planning workflow found |

## 9. Projects / Job Costing

| Feature | Status | Notes |
|---|---|---|
| Project setup | Present | `ProjectManagementController` |
| Budget by project | Partial | Project + budget modules exist, integration unclear |
| Expense tracking by project | Partial | Project profitability/expense reporting exists, depth unclear |
| Revenue by project | Partial | Project profitability views exist |
| Profitability by project | Present | Project profitability routes/views exist |
| Timesheet billing | Not Found | No timesheet billing found |
| Milestone billing | Not Found | No milestone billing workflow found |

## 10. Enterprise / Advanced

| Feature | Status | Notes |
|---|---|---|
| Multi-company | Present | Company/tenant scoped architecture exists |
| Multi-branch | Present | Active branch and branch-scoped reporting exist |
| Consolidated reporting | Partial | Some enterprise reporting exists; true consolidation not confirmed |
| Intercompany transactions | Not Found | No explicit intercompany transaction engine found |
| Department / class tracking | Not Found | No clear department/class accounting dimension found |
| Cost centers | Not Found | No clear cost center module found |
| Approval workflows | Present | `FinanceApprovalController`, period approvals |
| Maker/checker controls | Partial | Approval flow exists; full maker/checker coverage not confirmed |
| Role-based permissions | Present | `RoleController`, permission views |
| Segregation of duties | Partial | Roles exist, but explicit SoD rule engine not found |

## 11. Reporting / Analytics

| Feature | Status | Notes |
|---|---|---|
| Custom report builder | Not Found | No general report builder found |
| Report scheduling | Not Found | No explicit scheduled reporting workflow found |
| Dashboard KPIs | Present | Dashboard controllers and KPI widgets exist |
| Drill-down reports | Present | General ledger, collections, inventory history, etc. |
| Comparative periods | Present | Profit/loss comparison routes exist |
| Departmental reporting | Not Found | No clear department reporting dimension found |
| Export PDF/Excel/CSV | Present | Multiple export/download flows exist |
| Financial ratios | Not Found | No ratio report module found |
| Cash flow forecasting | Not Found | Cash flow exists; forecasting not found |
| Executive dashboards | Present | Super admin/deployment dashboards exist |

## 12. Compliance / Audit / Security

| Feature | Status | Notes |
|---|---|---|
| Full audit logs | Present | Audit/activity modules exist |
| User login history | Partial | Session/device helpers exist; explicit login history UI not confirmed |
| Two-factor authentication | Not Found | No clear 2FA module found |
| IP/device restrictions | Partial | Device/session support exists; full restrictions not confirmed |
| Approval history | Present | Approvals and period-close approval data exist |
| Data encryption | Partial | Framework-level hashing/session protection exists; explicit data-encryption module not found |
| Backup / restore | Partial | Backup routes/controller exist; restore not clearly verified |
| Soft delete / recovery | Partial | Some models use soft deletes; general recovery UX varies |
| Tax/VAT reports | Present | `TaxCenterController`, `TaxFilingController` |
| Regulatory reporting | Partial | Tax filings/compliance views exist; broader regulatory packs unclear |

## 13. Automation / Productivity

| Feature | Status | Notes |
|---|---|---|
| Recurring invoices/bills | Present | Recurring transaction/invoice support exists |
| Scheduled reports | Not Found | No explicit report scheduler found |
| Email/SMS/WhatsApp sending | Partial | Email support exists; SMS/WhatsApp not clearly implemented |
| Approval automation | Partial | Approval flows exist; rule automation depth unclear |
| Rule-based transaction categorization | Not Found | No categorization rules engine found |
| Workflow triggers | Partial | Some events/notifications exist |
| Notifications / reminders | Present | Notifications subsystem exists |

## 14. Ecosystem / Platform

| Feature | Status | Notes |
|---|---|---|
| Public API | Partial | Some API endpoints exist, but not a full public API platform |
| Webhooks | Partial | Generic webhook route exists; productized webhook system unclear |
| Developer docs | Not Found | No developer docs set found in repo |
| Zapier/Make integrations | Not Found | No direct integrations found |
| Payment gateway integrations | Present | Paystack/Flutterwave/Stripe config and flows exist |
| Bank feed integrations | Not Found | No bank feed integration found |
| Mobile apps | Not Found | No mobile app code found |
| White-label / reseller mode | Partial | Multi-tenant/domain/deployment manager support exists |

## 15. Migration / Adoption Tools

| Feature | Status | Notes |
|---|---|---|
| Import customers/vendors/items | Present | Import tooling/views exist for customers and products |
| Import opening balances | Present | Customer/supplier opening balance imports/views exist |
| Import transactions | Partial | Some imports exist; generic transaction import not confirmed |
| QuickBooks/Xero migration wizard | Not Found | No migration wizard found |
| Bulk update tools | Partial | Several bulk operations/imports exist |
| Data validation checks | Partial | Validation exists across modules; dedicated migration validation suite not found |

## Bonus Differentiators

| Feature | Status | Notes |
|---|---|---|
| AI transaction categorization | Not Found | No clear implementation found |
| AI anomaly/fraud detection | Partial | AI anomaly references/controllers exist, unclear operational depth |
| AI financial insights | Partial | AI quick agent and analytics helpers exist |
| OCR receipt scanning | Not Found | No receipt OCR module found |
| Natural-language report search | Partial | AI quick agent exists, but not a full NL report search system |
| Embedded payments | Partial | Payment gateway integrations exist |
| Embedded lending / financing | Not Found | No lending module found |
| Marketplace / plugins | Partial | Plugin references exist, but productized marketplace unclear |
| Industry-specific editions | Partial | Plan/deployment structure could support editions, but not clearly implemented |

## Safest Build Order For Missing Pieces

If the goal is to turn this into a stronger "full accounting app" without breaking what
already works, the safest implementation order is:

1. Banking foundation
   - bank reconciliation
   - statement import
   - cashbook
   - transfers

2. Inventory control
   - warehouses
   - serial/lot tracking
   - reorder levels
   - stock valuation

3. Payables and procurement depth
   - requisitions
   - RFQs
   - purchase orders
   - GRNs
   - vendor bill workflow

4. Enterprise controls
   - cost centers
   - departments/classes
   - maker/checker hardening
   - locked period/fiscal close hardening

5. Automation and integrations
   - scheduled reports
   - public API hardening
   - webhooks
   - bank feeds

6. Advanced finance
   - exchange rates
   - multi-currency postings
   - revaluation
   - consolidation adjustments

## Recommendation

Do not try to add every missing item in one release. The current codebase already spans
sales, purchases, inventory, reporting, approvals, payroll, tax, fixed assets, projects,
and subscriptions. The right move is to pick one implementation tranche at a time and ship
it with data migration, tests, and UI wiring.
