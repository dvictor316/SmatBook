<style>
    .settings-menu {
        border-radius: 22px;
    }

    .settings-menu ul {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .settings-menu .nav-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 16px;
        color: #243a63;
        font-weight: 700;
        font-size: 0.92rem;
        background: rgba(248, 251, 255, 0.9);
        border: 1px solid rgba(191, 219, 254, 0.65);
        transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .settings-menu .nav-link i {
        width: 16px;
        min-width: 16px;
        text-align: center;
        color: #2563eb;
        font-size: 0.82rem;
    }

    .settings-menu .nav-link span {
        color: inherit;
        line-height: 1.35;
    }

    .settings-menu .nav-link:hover,
    .settings-menu .nav-link.active {
        color: #5b3df5;
        background: linear-gradient(135deg, rgba(238, 242, 255, 0.95) 0%, rgba(248, 250, 252, 0.98) 100%);
        border-color: rgba(167, 139, 250, 0.32);
        box-shadow: 0 10px 20px rgba(91, 61, 245, 0.08);
        transform: translateX(1px);
    }

    .settings-menu .menu-group-label {
        margin: 10px 4px 2px;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }
</style>

<div class="widget settings-menu mb-0">
    <ul>
        <li class="menu-group-label">Workspace</li>
        <li class="nav-item">
            <a href="{{ route('settings.index') }}" class="nav-link {{ Request::routeIs('settings.index') ? 'active' : '' }}">
                <i class="fas fa-user"></i> <span>Account Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('company-settings') }}"
                class="nav-link {{ Request::routeIs('company-settings') ? 'active' : '' }}">
                <i class="fas fa-building"></i> <span>Company Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('invoice-settings') }}"
                class="nav-link {{ Request::routeIs('invoice-settings') ? 'active' : '' }}">
                <i class="fas fa-file-invoice"></i> <span>Invoice Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('template-invoice') }}"
                class="nav-link {{ Request::routeIs('template-invoice') ? 'active' : '' }}">
                <i class="fas fa-layer-group"></i> <span>Invoice Templates</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('payment-settings') }}"
                class="nav-link {{ Request::routeIs('payment-settings') ? 'active' : '' }}">
                <i class="fas fa-credit-card"></i> <span>Payment Methods</span>
            </a>
        </li>
        <li class="menu-group-label">Banking & Accounting</li>
        <li class="nav-item">
            <a href="{{ route('bank-account') }}" class="nav-link {{ Request::routeIs('bank-account') ? 'active' : '' }}">
                <i class="fas fa-university"></i> <span>Bank Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('branches.index') }}" class="nav-link {{ Request::routeIs('branches.index') ? 'active' : '' }}">
                <i class="fas fa-code-branch"></i> <span>Branches</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('chart-of-accounts') }}" class="nav-link {{ Request::routeIs('chart-of-accounts') ? 'active' : '' }}">
                <i class="fas fa-th-large"></i> <span>Chart of Accounts</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('bank-reconciliation') }}" class="nav-link {{ Request::routeIs('bank-reconciliation') ? 'active' : '' }}">
                <i class="fas fa-arrows-rotate"></i> <span>Bank Reconciliation</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('bank-statement-imports') }}" class="nav-link {{ Request::routeIs('bank-statement-imports') ? 'active' : '' }}">
                <i class="fas fa-file-import"></i> <span>Statement Imports</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('manual-journal') }}" class="nav-link {{ Request::routeIs('manual-journal') ? 'active' : '' }}">
                <i class="fas fa-pen-to-square"></i> <span>Manual Journal</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('tax-rates') }}" class="nav-link {{ Request::routeIs('tax-rates') ? 'active' : '' }}">
                <i class="fas fa-file-lines"></i> <span>Tax Rates</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('plan-billing') }}" class="nav-link {{ Request::routeIs('plan-billing') ? 'active' : '' }}">
                <i class="fas fa-wallet"></i> <span>Plan & Billing</span>
            </a>
        </li>
        <li class="menu-group-label">Security & Communication</li>
        <li class="nav-item">
            <a href="{{ route('two-factor') }}" class="nav-link {{ Request::routeIs('two-factor') ? 'active' : '' }}">
                <i class="fas fa-shield-halved"></i> <span>Two Factor</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('custom-filed') }}" class="nav-link {{ Request::routeIs('custom-filed') ? 'active' : '' }}">
                <i class="fas fa-sliders"></i> <span>Custom Fields</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('email-settings') }}"
                class="nav-link {{ Request::routeIs('email-settings') ? 'active' : '' }}">
                <i class="fas fa-envelope"></i> <span>Email Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('preferences') }}" class="nav-link {{ Request::routeIs('preferences') ? 'active' : '' }}">
                <i class="fas fa-sliders-h"></i> <span>Preference Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('email-template') }}" class="nav-link {{ Request::routeIs('email-template') ? 'active' : '' }}">
                <i class="fas fa-paper-plane"></i> <span>Email Templates</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('seo-settings') }}" class="nav-link {{ Request::routeIs('seo-settings') ? 'active' : '' }}">
                <i class="fas fa-magnifying-glass-chart"></i> <span>SEO Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('saas-settings') }}" class="nav-link {{ Request::routeIs('saas-settings') ? 'active' : '' }}">
                <i class="fas fa-bullseye"></i> <span>SaaS Settings</span>
            </a>
        </li>
    </ul>
</div>
