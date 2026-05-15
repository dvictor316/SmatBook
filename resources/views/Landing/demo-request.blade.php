@extends('layout.landing_nav')

@section('title', 'Request a Demo | SmartProbook')

@section('content')

<section class="contact-header">
    <div class="container text-center">
        <h6 class="section-label">Live Demo</h6>
        <h1 class="section-title">Experience SmartProbook <span>First-Hand.</span></h1>
        <p class="section-subtitle">Request a controlled 48-hour demo environment, pre-loaded with sample data, so you can explore every feature before committing.</p>
    </div>
</section>

<section class="contact-section">
    <div class="container">
        <div class="contact-wrapper">

            {{-- Left info panel --}}
            <div class="contact-info-panel">
                <div class="info-content">
                    <h2>How It Works</h2>
                    <p>Fill in the form, and our team will review your request. Once approved, you'll receive login credentials by email with a 48-hour access window.</p>

                    <div class="contact-item">
                        <i class="fas fa-paper-plane"></i>
                        <div>
                            <h6>Step 1</h6>
                            <p>Submit your demo request using the form.</p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <i class="fas fa-user-check"></i>
                        <div>
                            <h6>Step 2</h6>
                            <p>Our team reviews and approves your request (usually within 24 hours).</p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <i class="fas fa-envelope-open-text"></i>
                        <div>
                            <h6>Step 3</h6>
                            <p>You receive an email with your login credentials and a direct access link.</p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h6>48-Hour Access</h6>
                            <p>Explore the full platform with sample data — sales, reports, inventory, accounting and more.</p>
                        </div>
                    </div>
                </div>

                <div class="support-status mt-4">
                    <h6>Important</h6>
                    <div class="status-indicator">
                        <span class="status-dot" style="background:#f39c12;"></span>
                        <p>Demo environments use <strong>sample data only</strong>. No real payment integrations are active. Access expires automatically after 48 hours.</p>
                    </div>
                </div>
            </div>

            {{-- Right form panel --}}
            <div class="contact-form-panel">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('demo.request.store') }}" id="demo-request-form" class="contact-form">
                    @csrf

                    <h3 style="color:#1a1a2e;font-weight:800;font-size:1.6rem;margin-bottom:8px;">Request Your Free Demo</h3>
                    <p style="color:#64748b;font-size:0.95rem;margin-bottom:8px;">Fill in the details below and we'll set up your personalised environment.</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" id="full_name" class="form-input @error('full_name') is-invalid @enderror"
                                   value="{{ old('full_name') }}" placeholder="John Doe" required maxlength="100">
                            @error('full_name')<div class="invalid-feedback d-block" style="color:#dc3545;font-size:.85rem;margin-top:4px;">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label for="company_name">Company Name <span class="text-danger">*</span></label>
                            <input type="text" name="company_name" id="company_name" class="form-input @error('company_name') is-invalid @enderror"
                                   value="{{ old('company_name') }}" placeholder="Acme Ltd." required maxlength="150">
                            @error('company_name')<div class="invalid-feedback d-block" style="color:#dc3545;font-size:.85rem;margin-top:4px;">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-input @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" placeholder="you@company.com" required maxlength="150">
                            @error('email')<div class="invalid-feedback d-block" style="color:#dc3545;font-size:.85rem;margin-top:4px;">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="phone" class="form-input @error('phone') is-invalid @enderror"
                                   value="{{ old('phone') }}" placeholder="+234 800 000 0000" required maxlength="30">
                            @error('phone')<div class="invalid-feedback d-block" style="color:#dc3545;font-size:.85rem;margin-top:4px;">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="country">Country <span class="text-danger">*</span></label>
                            <input type="text" name="country" id="country" class="form-input @error('country') is-invalid @enderror"
                                   value="{{ old('country') }}" placeholder="Nigeria" required maxlength="100">
                            @error('country')<div class="invalid-feedback d-block" style="color:#dc3545;font-size:.85rem;margin-top:4px;">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label for="business_type">Business Type</label>
                            <select name="business_type" id="business_type" class="form-input @error('business_type') is-invalid @enderror">
                                <option value="">-- Select --</option>
                                @foreach(['Retail', 'Wholesale', 'Manufacturing', 'Services', 'Healthcare', 'Education', 'Hospitality', 'Agriculture', 'Technology', 'Finance', 'Other'] as $type)
                                    <option value="{{ $type }}" {{ old('business_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                            @error('business_type')<div class="invalid-feedback d-block" style="color:#dc3545;font-size:.85rem;margin-top:4px;">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="number_of_users">Number of Users</label>
                        <input type="number" name="number_of_users" id="number_of_users" class="form-input @error('number_of_users') is-invalid @enderror"
                               value="{{ old('number_of_users', 1) }}" min="1" max="10000">
                        @error('number_of_users')<div class="invalid-feedback d-block" style="color:#dc3545;font-size:.85rem;margin-top:4px;">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="purpose">Purpose / What do you want to explore? <span class="text-danger">*</span></label>
                        <textarea name="purpose" id="purpose" rows="4" class="form-input @error('purpose') is-invalid @enderror"
                                  placeholder="E.g. I want to test the inventory management, sales reports and accounting features for my retail business..."
                                  required maxlength="1000">{{ old('purpose') }}</textarea>
                        @error('purpose')<div class="invalid-feedback d-block" style="color:#dc3545;font-size:.85rem;margin-top:4px;">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane me-2"></i> Submit Demo Request
                    </button>
                </form>
            </div>

        </div>
    </div>
</section>

<style>
    .contact-header,
    .contact-section,
    .contact-info-section,
    .contact-wrapper,
    .contact-info-panel,
    .contact-form-panel,
    .contact-item,
    .form-row,
    .form-group,
    .form-input,
    .btn-submit,
    .info-grid,
    .info-card {
        box-sizing: border-box;
    }

    /* ===== CONTACT HEADER ===== */
    .contact-header {
        padding: 100px 20px;
        background: linear-gradient(135deg, #f8fafc 0%, #f0f4ff 100%);
        margin-top: 85px;
    }

    .section-label {
        color: var(--accent-red);
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 3px;
        margin-bottom: 15px;
        display: block;
    }

    .section-title {
        font-size: 3.2rem;
        font-weight: 900;
        margin-bottom: 25px;
        line-height: 1.1;
        letter-spacing: -1px;
    }

    .section-title span {
        background: var(--grad-accent);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .section-subtitle {
        font-size: 1.15rem;
        color: var(--slate);
        max-width: 750px;
        margin: 0 auto;
        line-height: 1.8;
    }

    /* ===== CONTACT SECTION ===== */
    .contact-section {
        padding: 100px 20px;
        background: white;
    }

    .contact-wrapper {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 50px;
        align-items: stretch;
        border-radius: 30px;
        overflow: hidden;
        box-shadow: 0 50px 100px rgba(0, 0, 0, 0.12);
        background: white;
        border: 1px solid #edf2f7;
        width: 100%;
        max-width: 100%;
    }

    /* ===== CONTACT INFO PANEL ===== */
    .contact-info-panel {
        background: linear-gradient(135deg, var(--dark) 0%, #1e3a5f 100%);
        color: white;
        padding: 60px 50px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-width: 0;
    }

    .info-content h2 {
        font-size: 2rem;
        font-weight: 900;
        margin-bottom: 20px;
    }

    .info-content > p {
        color: #cbd5e1;
        font-size: 1rem;
        line-height: 1.8;
        margin-bottom: 40px;
    }

    .contact-item {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
        align-items: flex-start;
        min-width: 0;
    }

    .contact-item i {
        font-size: 1.5rem;
        color: var(--accent-gold);
        width: 50px;
        height: 50px;
        background: rgba(244, 164, 96, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .contact-item h6 {
        font-size: 0.9rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--accent-teal);
        margin-bottom: 5px;
    }

    .contact-item p,
    .contact-item a {
        color: #cbd5e1;
        font-size: 0.95rem;
        line-height: 1.6;
        text-decoration: none;
        transition: 0.3s ease;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .contact-item a:hover { color: var(--accent-gold); }

    /* ===== SUPPORT STATUS ===== */
    .support-status {
        background: rgba(255, 255, 255, 0.05);
        padding: 30px;
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .support-status h6 {
        font-size: 0.85rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--accent-gold);
        margin-bottom: 15px;
    }

    .status-indicator {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .status-dot {
        width: 12px;
        height: 12px;
        background: #10b981;
        border-radius: 50%;
        animation: pulse 2s infinite;
        flex-shrink: 0;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    .status-indicator p {
        color: #cbd5e1;
        font-size: 0.9rem;
        margin: 0;
        line-height: 1.6;
    }

    /* ===== CONTACT FORM ===== */
    .contact-form-panel {
        padding: 60px 50px;
        background: white;
        min-width: 0;
    }

    .contact-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .form-group label {
        font-weight: 700;
        font-size: 0.9rem;
        color: var(--dark);
        margin-bottom: 8px;
    }

    .form-input {
        padding: 14px 18px;
        border: 2px solid #edf2f7;
        border-radius: 12px;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.95rem;
        transition: 0.3s ease;
        background: white;
        color: var(--dark);
        width: 100%;
        max-width: 100%;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(0, 98, 255, 0.1);
    }

    .form-input::placeholder { color: #cbd5e1; }

    textarea.form-input { resize: vertical; }

    /* ===== SUBMIT BUTTON ===== */
    .btn-submit {
        padding: 16px 40px;
        background: linear-gradient(135deg, #0062ff 0%, #0047be 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 800;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        cursor: pointer;
        transition: 0.3s ease;
        box-shadow: 0 15px 30px rgba(0, 98, 255, 0.2);
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 20px 40px rgba(0, 98, 255, 0.3);
        color: white;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
        .contact-wrapper { grid-template-columns: 1fr; gap: 0; }
        .contact-info-panel, .contact-form-panel { padding: 50px 40px; }
        .section-title { font-size: 2.4rem; }
    }

    @media (max-width: 768px) {
        .contact-header { padding: 64px 16px 52px; margin-top: 70px; }
        .contact-section { padding: 44px 14px; }
        .contact-wrapper { border-radius: 24px; box-shadow: 0 24px 60px rgba(0,0,0,.10); }
        .contact-info-panel, .contact-form-panel { padding: 32px 22px; }
        .section-title { font-size: 2rem; line-height: 1.12; }
        .section-subtitle { font-size: 1rem; }
        .form-row { grid-template-columns: 1fr; }
        .contact-info-panel h2 { font-size: 1.5rem; }
        .support-status { padding: 22px 18px; }
    }

    @media (max-width: 480px) {
        .contact-header { padding: 52px 12px 40px; }
        .section-title { font-size: 1.55rem; letter-spacing: -0.5px; }
        .contact-info-panel, .contact-form-panel { padding: 26px 16px; }
        .contact-item { gap: 12px; margin-bottom: 20px; }
        .contact-item i { width: 42px; height: 42px; font-size: 1.1rem; }
        .form-input { padding: 12px 14px; font-size: 16px; }
        .btn-submit { padding: 14px 18px; font-size: 0.85rem; }
    }
</style>

@endsection
