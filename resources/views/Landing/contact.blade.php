@extends('layout.landing_nav')

@section('content')

<!-- ===== CONTACT PAGE HEADER ===== -->
<section class="contact-header">
    <div class="container text-center">
        <h6 class="section-label">Liaison Office</h6>
        <h1 class="section-title">Connect with <span>Intelligence.</span></h1>
        <p class="section-subtitle">Have a complex inquiry regarding Enterprise Licensing or Regional Compliance? Our liaison team is ready to assist your organization.</p>
    </div>
</section>

<!-- ===== CONTACT FORM SECTION ===== -->
<section class="contact-section">
    <div class="container">
        <div class="contact-wrapper">
            <!-- Left Panel: Contact Info -->
            <div class="contact-info-panel">
                <div class="info-content">
                    <h2>Global HQ</h2>
                    <p>Strategically positioned in the heart of the Enugu Tech Hub to drive financial innovation across West Africa.</p>
                    
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h6>Address</h6>
                            <p>Enugu Tech Hub, Independence Layout, Enugu, Nigeria</p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h6>Email</h6>
                            <a href="mailto:compliance@smatbook.com">compliance@smatbook.com</a>
                        </div>
                    </div>

                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h6>Phone</h6>
                            <a href="tel:+234800728626226">+234 (0) 800 SMARTPROBOOK</a>
                        </div>
                    </div>
                </div>

                <div class="support-status">
                    <h6>Technical Support Status</h6>
                    <div class="status-indicator">
                        <span class="status-dot"></span>
                        <p>All Systems Operational <br> Response time: &lt; 2 hrs</p>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Contact Form -->
            <div class="contact-form-panel">
                @if(session('success'))
                    <div class="alert alert-success mb-4">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger mb-4">{{ session('error') }}</div>
                @endif

                <form action="{{ route('contact.store') }}" method="POST" class="contact-form">
                    @csrf

                    <!-- Name and Email Row -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fullname">Full Name</label>
                            <input type="text" id="fullname" name="fullname" class="form-input" placeholder="Victor Don" value="{{ old('fullname') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Work Email</label>
                            <input type="email" id="email" name="email" class="form-input" placeholder="v.don@enterprise.com" value="{{ old('email') }}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="company_name">Company / Organization</label>
                        <input type="text" id="company_name" name="company_name" class="form-input" placeholder="SmartProbook Global" value="{{ old('company_name') }}">
                    </div>

                    <!-- Inquiry Department -->
                    <div class="form-group">
                        <label for="department">Inquiry Department</label>
                        <select id="department" name="department" class="form-input" required>
                            <option value="">Select a department</option>
                            <option value="licensing" {{ old('department') === 'licensing' ? 'selected' : '' }}>Enterprise Solutions & Licensing</option>
                            <option value="governance" {{ old('department') === 'governance' ? 'selected' : '' }}>Legal & Corporate Governance</option>
                            <option value="technical" {{ old('department') === 'technical' ? 'selected' : '' }}>Technical API Support</option>
                            <option value="partnerships" {{ old('department') === 'partnerships' ? 'selected' : '' }}>Strategic Partnerships</option>
                        </select>
                    </div>

                    <!-- Message -->
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" class="form-input" rows="6" placeholder="Briefly describe your business requirement..." required>{{ old('message') }}</textarea>
                    </div>

                    <!-- Checkbox -->
                    <div class="form-checkbox">
                        <input type="checkbox" id="agreement" name="agreement" required>
                        <label for="agreement">I agree to the SmartProbook <a href="{{ route('landing.policy') }}">Corporate Policy</a> regarding data handling and communication.</label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-submit">Establish Connection</button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- ===== ADDITIONAL INFO SECTION ===== -->
<section class="contact-info-section">
    <div class="container">
        <div class="info-grid">
            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Business Hours</h3>
                <p>Monday - Friday<br>09:00 AM - 06:00 PM WAT</p>
            </div>

            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>Support Team</h3>
                <p>Available 24/7 for<br>Enterprise Clients</p>
            </div>

            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <h3>Response Time</h3>
                <p>Average response<br>within 2 hours</p>
            </div>

            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-globe"></i>
                </div>
                <h3>Global Coverage</h3>
                <p>Serving enterprises<br>across Africa & Beyond</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== CONTACT PAGE STYLES ===== -->
<style>
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

    .container {
        max-width: 1200px;
        margin: 0 auto;
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
    }

    /* ===== CONTACT INFO PANEL ===== */
    .contact-info-panel {
        background: linear-gradient(135deg, var(--dark) 0%, #1e3a5f 100%);
        color: white;
        padding: 60px 50px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
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
    }

    .contact-item a:hover {
        color: var(--accent-gold);
    }

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
    }

    .status-dot {
        width: 12px;
        height: 12px;
        background: #10b981;
        border-radius: 50%;
        animation: pulse 2s infinite;
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
    }

    .contact-form {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--dark);
        margin-bottom: 10px;
        text-transform: capitalize;
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
    }

    .form-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(0, 98, 255, 0.1);
    }

    .form-input::placeholder {
        color: #cbd5e1;
    }

    /* ===== FORM CHECKBOX ===== */
    .form-checkbox {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 18px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #edf2f7;
    }

    .form-checkbox input[type="checkbox"] {
        width: 20px;
        height: 20px;
        margin-top: 3px;
        cursor: pointer;
        accent-color: var(--primary);
        flex-shrink: 0;
    }

    .form-checkbox label {
        font-size: 0.9rem;
        color: var(--slate);
        line-height: 1.6;
        margin: 0;
        cursor: pointer;
    }

    .form-checkbox a {
        color: var(--accent-red);
        text-decoration: none;
        font-weight: 700;
        transition: 0.3s ease;
    }

    .form-checkbox a:hover {
        text-decoration: underline;
    }

    /* ===== SUBMIT BUTTON ===== */
    .btn-submit {
        padding: 16px 40px;
        background: var(--grad-blue);
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
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 20px 40px rgba(0, 98, 255, 0.3);
        color: white;
    }

    /* ===== ADDITIONAL INFO SECTION ===== */
    .contact-info-section {
        padding: 100px 20px;
        background: linear-gradient(135deg, #f8fafc 0%, #f0f4ff 100%);
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
    }

    .info-card {
        padding: 50px 35px;
        background: white;
        border-radius: 20px;
        border: 1px solid #edf2f7;
        text-align: center;
        transition: 0.4s ease;
    }

    .info-card:hover {
        transform: translateY(-15px);
        box-shadow: 0 30px 70px rgba(0, 0, 0, 0.1);
        border-color: var(--primary);
    }

    .info-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 25px;
        border-radius: 50%;
        background: var(--light-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.3s ease;
    }

    .info-card:hover .info-icon {
        background: var(--grad-blue);
    }

    .info-icon i {
        font-size: 2rem;
        color: var(--primary);
        transition: 0.3s ease;
    }

    .info-card:hover .info-icon i {
        color: white;
    }

    .info-card h3 {
        font-size: 1.3rem;
        font-weight: 800;
        margin-bottom: 15px;
        color: var(--dark);
    }

    .info-card p {
        color: var(--slate);
        font-size: 0.95rem;
        line-height: 1.7;
        margin: 0;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
        .contact-wrapper {
            grid-template-columns: 1fr;
            gap: 0;
        }

        .contact-info-panel,
        .contact-form-panel {
            padding: 50px 40px;
        }

        .section-title {
            font-size: 2.4rem;
        }

        .info-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .contact-header {
            padding: 80px 20px;
            margin-top: 70px;
        }

        .contact-section {
            padding: 60px 20px;
        }

        .contact-info-panel,
        .contact-form-panel {
            padding: 40px 30px;
        }

        .section-title {
            font-size: 2rem;
        }

        .section-subtitle {
            font-size: 1rem;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .info-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .contact-item {
            margin-bottom: 25px;
        }

        .contact-info-panel h2 {
            font-size: 1.5rem;
        }
    }

    @media (max-width: 480px) {
        .contact-header {
            padding: 60px 15px;
        }

        .section-title {
            font-size: 1.6rem;
        }

        .contact-info-panel,
        .contact-form-panel {
            padding: 30px 20px;
        }

        .contact-item {
            gap: 15px;
        }

        .form-input {
            padding: 12px 15px;
        }

        .btn-submit {
            padding: 14px 30px;
            font-size: 0.85rem;
        }
    }
</style>

@endsection
