<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tax_jurisdictions')) {
            Schema::table('tax_jurisdictions', function (Blueprint $table) {
                if (!Schema::hasColumn('tax_jurisdictions', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
                }
                if (!Schema::hasColumn('tax_jurisdictions', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('company_id')->index();
                }
                if (!Schema::hasColumn('tax_jurisdictions', 'branch_id')) {
                    $table->string('branch_id')->nullable()->after('user_id')->index();
                }
                if (!Schema::hasColumn('tax_jurisdictions', 'branch_name')) {
                    $table->string('branch_name')->nullable()->after('branch_id');
                }
                if (!Schema::hasColumn('tax_jurisdictions', 'filing_frequency')) {
                    $table->string('filing_frequency', 50)->nullable()->after('currency_code');
                }
                if (!Schema::hasColumn('tax_jurisdictions', 'filing_deadline_days')) {
                    $table->unsignedInteger('filing_deadline_days')->nullable()->after('filing_frequency');
                }
                if (!Schema::hasColumn('tax_jurisdictions', 'tax_authority_name')) {
                    $table->string('tax_authority_name')->nullable()->after('filing_deadline_days');
                }
                if (!Schema::hasColumn('tax_jurisdictions', 'tax_authority_reference')) {
                    $table->string('tax_authority_reference')->nullable()->after('tax_authority_name');
                }
                if (!Schema::hasColumn('tax_jurisdictions', 'tax_authority_email')) {
                    $table->string('tax_authority_email')->nullable()->after('tax_authority_reference');
                }
                if (!Schema::hasColumn('tax_jurisdictions', 'tax_authority_phone')) {
                    $table->string('tax_authority_phone')->nullable()->after('tax_authority_email');
                }
                if (!Schema::hasColumn('tax_jurisdictions', 'portal_url')) {
                    $table->string('portal_url')->nullable()->after('tax_authority_phone');
                }
                if (!Schema::hasColumn('tax_jurisdictions', 'registration_threshold')) {
                    $table->decimal('registration_threshold', 18, 2)->default(0)->after('portal_url');
                }
                if (!Schema::hasColumn('tax_jurisdictions', 'is_default')) {
                    $table->boolean('is_default')->default(false)->after('registration_threshold');
                }
                if (!Schema::hasColumn('tax_jurisdictions', 'metadata')) {
                    $table->json('metadata')->nullable()->after('is_default');
                }
            });
        }

        if (Schema::hasTable('tax_codes')) {
            Schema::table('tax_codes', function (Blueprint $table) {
                if (!Schema::hasColumn('tax_codes', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('tax_jurisdiction_id')->index();
                }
                if (!Schema::hasColumn('tax_codes', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('company_id')->index();
                }
                if (!Schema::hasColumn('tax_codes', 'branch_id')) {
                    $table->string('branch_id')->nullable()->after('user_id')->index();
                }
                if (!Schema::hasColumn('tax_codes', 'branch_name')) {
                    $table->string('branch_name')->nullable()->after('branch_id');
                }
                if (!Schema::hasColumn('tax_codes', 'country_code')) {
                    $table->string('country_code', 3)->nullable()->after('branch_name')->index();
                }
                if (!Schema::hasColumn('tax_codes', 'name')) {
                    $table->string('name')->nullable()->after('code');
                }
                if (!Schema::hasColumn('tax_codes', 'category')) {
                    $table->string('category', 64)->default('indirect')->after('type');
                }
                if (!Schema::hasColumn('tax_codes', 'calculation_method')) {
                    $table->string('calculation_method', 32)->default('exclusive')->after('category');
                }
                if (!Schema::hasColumn('tax_codes', 'is_compound')) {
                    $table->boolean('is_compound')->default(false)->after('calculation_method');
                }
                if (!Schema::hasColumn('tax_codes', 'compound_order')) {
                    $table->unsignedInteger('compound_order')->default(0)->after('is_compound');
                }
                if (!Schema::hasColumn('tax_codes', 'effective_from')) {
                    $table->date('effective_from')->nullable()->after('compound_order');
                }
                if (!Schema::hasColumn('tax_codes', 'effective_to')) {
                    $table->date('effective_to')->nullable()->after('effective_from');
                }
                if (!Schema::hasColumn('tax_codes', 'filing_frequency')) {
                    $table->string('filing_frequency', 50)->nullable()->after('effective_to');
                }
                if (!Schema::hasColumn('tax_codes', 'filing_deadline_days')) {
                    $table->unsignedInteger('filing_deadline_days')->nullable()->after('filing_frequency');
                }
                if (!Schema::hasColumn('tax_codes', 'report_template')) {
                    $table->string('report_template')->nullable()->after('filing_deadline_days');
                }
                if (!Schema::hasColumn('tax_codes', 'ledger_output_account_code')) {
                    $table->string('ledger_output_account_code')->nullable()->after('report_template');
                }
                if (!Schema::hasColumn('tax_codes', 'ledger_input_account_code')) {
                    $table->string('ledger_input_account_code')->nullable()->after('ledger_output_account_code');
                }
                if (!Schema::hasColumn('tax_codes', 'ledger_payable_account_code')) {
                    $table->string('ledger_payable_account_code')->nullable()->after('ledger_input_account_code');
                }
                if (!Schema::hasColumn('tax_codes', 'ledger_receivable_account_code')) {
                    $table->string('ledger_receivable_account_code')->nullable()->after('ledger_payable_account_code');
                }
                if (!Schema::hasColumn('tax_codes', 'ledger_expense_account_code')) {
                    $table->string('ledger_expense_account_code')->nullable()->after('ledger_receivable_account_code');
                }
                if (!Schema::hasColumn('tax_codes', 'registration_threshold')) {
                    $table->decimal('registration_threshold', 18, 2)->default(0)->after('ledger_expense_account_code');
                }
                if (!Schema::hasColumn('tax_codes', 'supports_reverse_charge')) {
                    $table->boolean('supports_reverse_charge')->default(false)->after('registration_threshold');
                }
                if (!Schema::hasColumn('tax_codes', 'is_zero_rated')) {
                    $table->boolean('is_zero_rated')->default(false)->after('supports_reverse_charge');
                }
                if (!Schema::hasColumn('tax_codes', 'is_exempt')) {
                    $table->boolean('is_exempt')->default(false)->after('is_zero_rated');
                }
                if (!Schema::hasColumn('tax_codes', 'recoverability_rate')) {
                    $table->decimal('recoverability_rate', 8, 4)->default(100)->after('is_exempt');
                }
                if (!Schema::hasColumn('tax_codes', 'applies_to')) {
                    $table->json('applies_to')->nullable()->after('recoverability_rate');
                }
                if (!Schema::hasColumn('tax_codes', 'metadata')) {
                    $table->json('metadata')->nullable()->after('applies_to');
                }
            });
        }

        if (Schema::hasTable('withholding_rules')) {
            Schema::table('withholding_rules', function (Blueprint $table) {
                if (!Schema::hasColumn('withholding_rules', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('tax_jurisdiction_id')->index();
                }
                if (!Schema::hasColumn('withholding_rules', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('company_id')->index();
                }
                if (!Schema::hasColumn('withholding_rules', 'branch_id')) {
                    $table->string('branch_id')->nullable()->after('user_id')->index();
                }
                if (!Schema::hasColumn('withholding_rules', 'branch_name')) {
                    $table->string('branch_name')->nullable()->after('branch_id');
                }
                if (!Schema::hasColumn('withholding_rules', 'country_code')) {
                    $table->string('country_code', 3)->nullable()->after('branch_name')->index();
                }
                if (!Schema::hasColumn('withholding_rules', 'service_type')) {
                    $table->string('service_type')->nullable()->after('name');
                }
                if (!Schema::hasColumn('withholding_rules', 'certificate_prefix')) {
                    $table->string('certificate_prefix')->nullable()->after('service_type');
                }
                if (!Schema::hasColumn('withholding_rules', 'payable_account_code')) {
                    $table->string('payable_account_code')->nullable()->after('account_code');
                }
                if (!Schema::hasColumn('withholding_rules', 'receivable_account_code')) {
                    $table->string('receivable_account_code')->nullable()->after('payable_account_code');
                }
                if (!Schema::hasColumn('withholding_rules', 'effective_from')) {
                    $table->date('effective_from')->nullable()->after('receivable_account_code');
                }
                if (!Schema::hasColumn('withholding_rules', 'effective_to')) {
                    $table->date('effective_to')->nullable()->after('effective_from');
                }
                if (!Schema::hasColumn('withholding_rules', 'metadata')) {
                    $table->json('metadata')->nullable()->after('effective_to');
                }
            });
        }

        if (Schema::hasTable('tax_filings')) {
            Schema::table('tax_filings', function (Blueprint $table) {
                if (!Schema::hasColumn('tax_filings', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('tax_jurisdiction_id')->index();
                }
                if (!Schema::hasColumn('tax_filings', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('company_id')->index();
                }
                if (!Schema::hasColumn('tax_filings', 'branch_id')) {
                    $table->string('branch_id')->nullable()->after('user_id')->index();
                }
                if (!Schema::hasColumn('tax_filings', 'branch_name')) {
                    $table->string('branch_name')->nullable()->after('branch_id');
                }
                if (!Schema::hasColumn('tax_filings', 'country_code')) {
                    $table->string('country_code', 3)->nullable()->after('branch_name')->index();
                }
                if (!Schema::hasColumn('tax_filings', 'filing_type')) {
                    $table->string('filing_type', 64)->default('vat')->after('name');
                }
                if (!Schema::hasColumn('tax_filings', 'filing_frequency')) {
                    $table->string('filing_frequency', 50)->nullable()->after('filing_type');
                }
                if (!Schema::hasColumn('tax_filings', 'currency_code')) {
                    $table->string('currency_code', 3)->nullable()->after('filing_frequency');
                }
                if (!Schema::hasColumn('tax_filings', 'branch_scope')) {
                    $table->string('branch_scope', 32)->default('branch')->after('currency_code');
                }
                if (!Schema::hasColumn('tax_filings', 'tax_due')) {
                    $table->decimal('tax_due', 18, 2)->default(0)->after('total_tax');
                }
                if (!Schema::hasColumn('tax_filings', 'tax_credit')) {
                    $table->decimal('tax_credit', 18, 2)->default(0)->after('tax_due');
                }
                if (!Schema::hasColumn('tax_filings', 'tax_refund')) {
                    $table->decimal('tax_refund', 18, 2)->default(0)->after('tax_credit');
                }
                if (!Schema::hasColumn('tax_filings', 'adjustments_total')) {
                    $table->decimal('adjustments_total', 18, 2)->default(0)->after('tax_refund');
                }
                if (!Schema::hasColumn('tax_filings', 'credits_total')) {
                    $table->decimal('credits_total', 18, 2)->default(0)->after('adjustments_total');
                }
                if (!Schema::hasColumn('tax_filings', 'approved_by')) {
                    $table->unsignedBigInteger('approved_by')->nullable()->after('submitted_by');
                }
                if (!Schema::hasColumn('tax_filings', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('submitted_at');
                }
                if (!Schema::hasColumn('tax_filings', 'remitted_by')) {
                    $table->unsignedBigInteger('remitted_by')->nullable()->after('approved_at');
                }
                if (!Schema::hasColumn('tax_filings', 'remitted_at')) {
                    $table->timestamp('remitted_at')->nullable()->after('remitted_by');
                }
                if (!Schema::hasColumn('tax_filings', 'remittance_reference')) {
                    $table->string('remittance_reference')->nullable()->after('remitted_at');
                }
            });
        }

        if (!Schema::hasTable('tax_account_mappings')) {
            Schema::create('tax_account_mappings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('branch_id')->nullable()->index();
                $table->string('branch_name')->nullable();
                $table->foreignId('tax_jurisdiction_id')->nullable()->constrained('tax_jurisdictions')->nullOnDelete();
                $table->foreignId('tax_code_id')->nullable()->constrained('tax_codes')->nullOnDelete();
                $table->string('country_code', 3)->nullable()->index();
                $table->string('tax_type', 64);
                $table->string('role', 64);
                $table->string('account_code', 64);
                $table->string('account_name')->nullable();
                $table->boolean('is_required')->default(true);
                $table->date('effective_from')->nullable();
                $table->date('effective_to')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'tax_code_id', 'role', 'branch_id'], 'tax_account_mappings_scope_unique');
            });
        }

        if (!Schema::hasTable('tax_filing_lines')) {
            Schema::create('tax_filing_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tax_filing_id')->constrained('tax_filings')->cascadeOnDelete();
                $table->string('line_key', 120);
                $table->string('label');
                $table->string('tax_type', 64)->nullable();
                $table->decimal('taxable_base', 18, 2)->default(0);
                $table->decimal('tax_amount', 18, 2)->default(0);
                $table->decimal('adjustment_amount', 18, 2)->default(0);
                $table->decimal('credit_amount', 18, 2)->default(0);
                $table->decimal('net_amount', 18, 2)->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['tax_filing_id', 'line_key']);
            });
        }

        if (!Schema::hasTable('tax_audit_logs')) {
            Schema::create('tax_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id_numeric')->nullable()->index();
                $table->string('branch_id')->nullable()->index();
                $table->string('branch_name')->nullable();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('auditable_type');
                $table->unsignedBigInteger('auditable_id')->nullable();
                $table->string('action', 100);
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_audit_logs');
        Schema::dropIfExists('tax_filing_lines');
        Schema::dropIfExists('tax_account_mappings');

        if (Schema::hasTable('tax_filings')) {
            Schema::table('tax_filings', function (Blueprint $table) {
                foreach ([
                    'company_id', 'user_id', 'branch_id', 'branch_name', 'country_code',
                    'filing_type', 'filing_frequency', 'currency_code', 'branch_scope',
                    'tax_due', 'tax_credit', 'tax_refund', 'adjustments_total', 'credits_total',
                    'approved_by', 'approved_at', 'remitted_by', 'remitted_at', 'remittance_reference',
                ] as $column) {
                    if (Schema::hasColumn('tax_filings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('withholding_rules')) {
            Schema::table('withholding_rules', function (Blueprint $table) {
                foreach ([
                    'company_id', 'user_id', 'branch_id', 'branch_name', 'country_code',
                    'service_type', 'certificate_prefix', 'payable_account_code', 'receivable_account_code',
                    'effective_from', 'effective_to', 'metadata',
                ] as $column) {
                    if (Schema::hasColumn('withholding_rules', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('tax_codes')) {
            Schema::table('tax_codes', function (Blueprint $table) {
                foreach ([
                    'company_id', 'user_id', 'branch_id', 'branch_name', 'country_code', 'name',
                    'category', 'calculation_method', 'is_compound', 'compound_order',
                    'effective_from', 'effective_to', 'filing_frequency', 'filing_deadline_days',
                    'report_template', 'ledger_output_account_code', 'ledger_input_account_code',
                    'ledger_payable_account_code', 'ledger_receivable_account_code',
                    'ledger_expense_account_code', 'registration_threshold', 'supports_reverse_charge',
                    'is_zero_rated', 'is_exempt', 'recoverability_rate', 'applies_to', 'metadata',
                ] as $column) {
                    if (Schema::hasColumn('tax_codes', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('tax_jurisdictions')) {
            Schema::table('tax_jurisdictions', function (Blueprint $table) {
                foreach ([
                    'company_id', 'user_id', 'branch_id', 'branch_name', 'filing_frequency',
                    'filing_deadline_days', 'tax_authority_name', 'tax_authority_reference',
                    'tax_authority_email', 'tax_authority_phone', 'portal_url',
                    'registration_threshold', 'is_default', 'metadata',
                ] as $column) {
                    if (Schema::hasColumn('tax_jurisdictions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
