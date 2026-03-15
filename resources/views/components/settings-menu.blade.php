<div class="widget settings-menu mb-0">
    <ul>
        <li class="nav-item">
            <a href="{{ route('settings.index') }}" class="nav-link {{ Request::routeIs('settings.index') ? 'active' : '' }}">
                <i class="fe fe-user"></i> <span>Account Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('company-settings') }}"
                class="nav-link {{ Request::routeIs('company-settings') ? 'active' : '' }}">
                <i class="fe fe-settings"></i> <span>Company Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('invoice-settings') }}"
                class="nav-link {{ Request::routeIs('invoice-settings') ? 'active' : '' }}">
                <i class="fe fe-file"></i> <span>Invoice Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('template-invoice') }}"
                class="nav-link {{ Request::routeIs('template-invoice') ? 'active' : '' }}">
                <i class="fe fe-layers"></i> <span>Invoice Templates</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('payment-settings') }}"
                class="nav-link {{ Request::routeIs('payment-settings') ? 'active' : '' }}">
                <i class="fe fe-credit-card"></i> <span>Payment Methods</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('bank-account') }}" class="nav-link {{ Request::routeIs('bank-account') ? 'active' : '' }}">
                <i class="fe fe-aperture"></i> <span>Bank Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('chart-of-accounts') }}" class="nav-link {{ Request::routeIs('chart-of-accounts') ? 'active' : '' }}">
                <i class="fe fe-grid"></i> <span>Chart of Accounts</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('manual-journal') }}" class="nav-link {{ Request::routeIs('manual-journal') ? 'active' : '' }}">
                <i class="fe fe-edit-3"></i> <span>Manual Journal</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('tax-rates') }}" class="nav-link {{ Request::routeIs('tax-rates') ? 'active' : '' }}">
                <i class="fe fe-file-text"></i> <span>Tax Rates</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('plan-billing') }}" class="nav-link {{ Request::routeIs('plan-billing') ? 'active' : '' }}">
                <i class="fe fe-credit-card"></i> <span>Plan & Billing</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('two-factor') }}" class="nav-link {{ Request::routeIs('two-factor') ? 'active' : '' }}">
                <i class="fe fe-aperture"></i> <span>Two Factor</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('custom-filed') }}" class="nav-link {{ Request::routeIs('custom-filed') ? 'active' : '' }}">
                <i class="fe fe-file-text"></i> <span>Custom Fields</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('email-settings') }}"
                class="nav-link {{ Request::routeIs('email-settings') ? 'active' : '' }}">
                <i class="fe fe-mail"></i> <span>Email Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('preferences') }}" class="nav-link {{ Request::routeIs('preferences') ? 'active' : '' }}">
                <i class="fe fe-settings"></i> <span>Preference Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('email-template') }}" class="nav-link {{ Request::routeIs('email-template') ? 'active' : '' }}">
                <i class="fe fe-airplay"></i> <span>Email Templates</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('seo-settings') }}" class="nav-link {{ Request::routeIs('seo-settings') ? 'active' : '' }}">
                <i class="fe fe-send"></i> <span>SEO Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('saas-settings') }}" class="nav-link {{ Request::routeIs('saas-settings') ? 'active' : '' }}">
                <i class="fe fe-target"></i> <span>SaaS Settings</span>
            </a>
        </li>
    </ul>
</div>
