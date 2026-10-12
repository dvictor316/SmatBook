<footer class="master-footer">
    <div class="footer-content">
        <div class="footer-grid">
            <div class="footer-col">
                <a href="{{ url('/') }}" class="footer-logo">SMARTPRO<span>BOOK</span></a>
                <p>Accounting, sales, inventory, payroll, tax, and operational tools built for growing businesses and institutions.</p>
            </div>

            <div class="footer-col">
                <h5>Ecosystem</h5>
                <ul class="footer-links">
                    <li><a href="{{ route('landing.about') }}" class="footer-link">Intelligence Solutions</a></li>
                    <li><a href="{{ route('landing.team') }}" class="footer-link">Other Projects</a></li>
                    <li><a href="{{ url('/#product-video') }}" class="footer-link">Product Video</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h5>Governance</h5>
                <ul class="footer-links">
                    <li><a href="{{ route('landing.policy') }}" class="footer-link">Company Policy</a></li>
                    <li><a href="{{ route('landing.policy') }}#section-7" class="footer-link">Data &amp; Security</a></li>
                    <li><a href="{{ route('landing.policy') }}#section-1" class="footer-link">Terms &amp; Conditions</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h5>Services</h5>
                <ul class="footer-links">
                    <li><a href="{{ route('membership-plans') }}" class="footer-link">Enterprise Solutions</a></li>
                    <li><a href="{{ route('landing.about') }}" class="footer-link">Risk Management</a></li>
                    <li><a href="{{ route('landing.contact') }}" class="footer-link">Consulting</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h5>Contact</h5>
                <p>12 Independence Layout<br>Enugu, Nigeria</p>
                <ul class="footer-links">
                    <li><a href="tel:+2348064646306" class="footer-link">+234 806 464 6306</a></li>
                    <li><a href="mailto:donvictorlive@gmail.com" class="footer-link">donvictorlive@gmail.com</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-divider"></div>

        <div class="footer-bottom">
            <p>&copy; {{ now()->year }} SmartProbook. All rights reserved.</p>
        </div>
    </div>
</footer>
