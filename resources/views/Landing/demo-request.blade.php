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

                <form method="POST" action="{{ route('demo.request.store') }}" id="demo-request-form">
                    @csrf

                    <h3 class="mb-4" style="color:#1a1a2e;font-weight:700;">Request Your Free Demo</h3>

                    <div class="form-row" style="display:flex;gap:1rem;flex-wrap:wrap;">
                        <div class="form-group" style="flex:1;min-width:200px;">
                            <label for="full_name">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" id="full_name" class="form-control @error('full_name') is-invalid @enderror"
                                   value="{{ old('full_name') }}" placeholder="John Doe" required maxlength="100">
                            @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group" style="flex:1;min-width:200px;">
                            <label for="company_name">Company Name <span class="text-danger">*</span></label>
                            <input type="text" name="company_name" id="company_name" class="form-control @error('company_name') is-invalid @enderror"
                                   value="{{ old('company_name') }}" placeholder="Acme Ltd." required maxlength="150">
                            @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-row" style="display:flex;gap:1rem;flex-wrap:wrap;">
                        <div class="form-group" style="flex:1;min-width:200px;">
                            <label for="email">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" placeholder="you@company.com" required maxlength="150">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group" style="flex:1;min-width:200px;">
                            <label for="phone">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone') }}" placeholder="+234 800 000 0000" required maxlength="30">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-row" style="display:flex;gap:1rem;flex-wrap:wrap;">
                        <div class="form-group" style="flex:1;min-width:200px;">
                            <label for="country">Country <span class="text-danger">*</span></label>
                            <input type="text" name="country" id="country" class="form-control @error('country') is-invalid @enderror"
                                   value="{{ old('country') }}" placeholder="Nigeria" required maxlength="100">
                            @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group" style="flex:1;min-width:200px;">
                            <label for="business_type">Business Type</label>
                            <select name="business_type" id="business_type" class="form-control @error('business_type') is-invalid @enderror">
                                <option value="">-- Select --</option>
                                @foreach(['Retail', 'Wholesale', 'Manufacturing', 'Services', 'Healthcare', 'Education', 'Hospitality', 'Agriculture', 'Technology', 'Finance', 'Other'] as $type)
                                    <option value="{{ $type }}" {{ old('business_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                            @error('business_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="number_of_users">Number of Users</label>
                        <input type="number" name="number_of_users" id="number_of_users" class="form-control @error('number_of_users') is-invalid @enderror"
                               value="{{ old('number_of_users', 1) }}" min="1" max="10000">
                        @error('number_of_users')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="purpose">Purpose / What do you want to explore? <span class="text-danger">*</span></label>
                        <textarea name="purpose" id="purpose" rows="4" class="form-control @error('purpose') is-invalid @enderror"
                                  placeholder="E.g. I want to test the inventory management, sales reports and accounting features for my retail business..."
                                  required maxlength="1000">{{ old('purpose') }}</textarea>
                        @error('purpose')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="padding:0.8rem 2rem;font-size:1rem;font-weight:600;">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Demo Request
                    </button>
                </form>
            </div>

        </div>
    </div>
</section>

@endsection
