@extends('layout.landing_nav')

@section('content')

<!-- ===== POLICY PAGE STYLES ===== -->
<style>
    /* ===== RESET & BASE ===== */
    .sidebar, #sidebar, .sidebar-menu, .main-sidebar, aside {
        display: none !important;
        width: 0 !important;
    }
    .main-wrapper, .page-wrapper, #main-content {
        margin-left: 0 !important;
        padding-left: 0 !important;
        width: 100% !important;
        left: 0 !important;
    }
    body {
        padding-left: 0 !important;
        overflow-x: hidden;
    }

    /* ===== POLICY HEADER ===== */
    .policy-header {
        margin-top: 85px;
        padding: 100px 20px;
        background: linear-gradient(135deg, #f8fafc 0%, #f0f4ff 100%);
        text-align: center;
    }

    .policy-label {
        color: var(--accent-red);
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 3px;
        margin-bottom: 15px;
        display: block;
    }

    .policy-title {
        font-size: 3.2rem;
        font-weight: 900;
        margin-bottom: 25px;
        line-height: 1.1;
        letter-spacing: -1px;
    }

    .policy-title span {
        background: var(--grad-accent);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .policy-date {
        color: var(--slate);
        font-size: 0.95rem;
        font-weight: 500;
    }

    /* ===== POLICY SECTION ===== */
    .policy-section {
        padding: 100px 20px;
        background: white;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
    }

    /* ===== POLICY DOCUMENT ===== */
    .policy-document {
        background: white;
        border-radius: 30px;
        border: 1px solid #edf2f7;
        box-shadow: 0 40px 80px rgba(0, 0, 0, 0.08);
        padding: 80px;
    }

    /* ===== HEADINGS ===== */
    .policy-document h2 {
        color: var(--dark);
        font-weight: 900;
        font-size: 1.8rem;
        margin-top: 60px;
        margin-bottom: 25px;
        line-height: 1.2;
        letter-spacing: -0.5px;
        padding-bottom: 15px;
        border-bottom: 3px solid var(--grad-accent);
        background: linear-gradient(90deg, var(--primary) 0%, var(--accent-red) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .policy-document h2:first-child {
        margin-top: 0;
    }

    .policy-document h3 {
        color: var(--dark);
        font-size: 1.3rem;
        font-weight: 800;
        margin-top: 35px;
        margin-bottom: 18px;
        letter-spacing: -0.3px;
    }

    .policy-document h4 {
        color: var(--slate);
        font-size: 1.1rem;
        font-weight: 700;
        margin-top: 20px;
        margin-bottom: 15px;
    }

    /* ===== PARAGRAPHS & LISTS ===== */
    .policy-document p,
    .policy-document li {
        color: var(--slate);
        font-size: 1.05rem;
        line-height: 1.9;
        margin-bottom: 18px;
        text-align: justify;
    }

    .policy-document ul,
    .policy-document ol {
        margin-left: 25px;
        margin-bottom: 25px;
    }

    .policy-document li {
        margin-bottom: 12px;
    }

    .policy-document strong {
        color: var(--dark);
        font-weight: 800;
    }

    .policy-document em {
        color: var(--primary);
        font-style: italic;
    }

    /* ===== DISCLAIMER BOX ===== */
    .disclaimer-box {
        background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);
        border: 2px solid var(--accent-red);
        border-radius: 20px;
        padding: 40px;
        margin: 50px 0;
    }

    .disclaimer-box h4 {
        color: var(--accent-red);
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-size: 0.95rem;
        margin-bottom: 20px;
    }

    .disclaimer-box p {
        color: #7f1d1d;
        margin-bottom: 15px;
    }

    .disclaimer-box p:last-child {
        margin-bottom: 0;
    }

    /* ===== INFO BOX ===== */
    .info-box {
        background: linear-gradient(135deg, #f0f4ff 0%, #f8fafc 100%);
        border: 2px solid var(--primary);
        border-radius: 20px;
        padding: 40px;
        margin: 50px 0;
    }

    .info-box h4 {
        color: var(--primary);
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-size: 0.95rem;
        margin-bottom: 20px;
    }

    .info-box p {
        color: var(--slate);
        margin-bottom: 12px;
    }

    .info-box p:last-child {
        margin-bottom: 0;
    }

    /* ===== CONTACT BOX ===== */
    .contact-box {
        background: white;
        border: 1px solid #edf2f7;
        border-radius: 20px;
        padding: 35px;
        margin-top: 50px;
        text-align: center;
    }

    .contact-box p {
        color: var(--slate);
        font-size: 1rem;
        margin-bottom: 8px;
    }

    .contact-box a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 800;
        transition: 0.3s ease;
    }

    .contact-box a:hover {
        color: var(--accent-red);
        text-decoration: underline;
    }

    /* ===== TOC (TABLE OF CONTENTS) ===== */
    .toc {
        background: var(--light-bg);
        border-left: 5px solid var(--primary);
        border-radius: 15px;
        padding: 35px;
        margin-bottom: 50px;
    }

    .toc h4 {
        color: var(--dark);
        font-weight: 900;
        margin-bottom: 20px;
        text-transform: uppercase;
        font-size: 1rem;
        letter-spacing: 1px;
    }

    .toc ul {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .toc li {
        margin-bottom: 10px;
    }

    .toc a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
        transition: 0.3s ease;
    }

    .toc a:hover {
        color: var(--accent-red);
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
        .policy-document {
            padding: 60px 50px;
        }

        .policy-title {
            font-size: 2.5rem;
        }

        .policy-document h2 {
            font-size: 1.5rem;
        }

        .policy-document h3 {
            font-size: 1.2rem;
        }
    }

    @media (max-width: 768px) {
        .policy-header {
            margin-top: 70px;
            padding: 80px 20px;
        }

        .policy-title {
            font-size: 2rem;
        }

        .policy-section {
            padding: 80px 20px;
        }

        .policy-document {
            padding: 40px 30px;
            border-radius: 20px;
        }

        .policy-document h2 {
            font-size: 1.4rem;
            margin-top: 40px;
        }

        .policy-document h3 {
            font-size: 1.1rem;
        }

        .policy-document p,
        .policy-document li {
            font-size: 0.95rem;
            text-align: left;
        }

        .disclaimer-box,
        .info-box,
        .contact-box {
            padding: 30px;
        }

        .toc {
            padding: 25px;
        }
    }

    @media (max-width: 480px) {
        .policy-header {
            padding: 60px 15px;
        }

        .policy-title {
            font-size: 1.6rem;
            margin-bottom: 15px;
        }

        .policy-section {
            padding: 60px 15px;
        }

        .policy-document {
            padding: 30px 20px;
        }

        .policy-document h2 {
            font-size: 1.2rem;
            margin-top: 30px;
            padding-bottom: 10px;
        }

        .policy-document h3 {
            font-size: 1rem;
        }

        .policy-document p,
        .policy-document li {
            font-size: 0.9rem;
        }
    }
</style>

<!-- ===== POLICY HEADER ===== -->
<section class="policy-header">
    <div class="container">
        <h6 class="policy-label">Service Agreement & Terms of Use</h6>
        <h1 class="policy-title">SmartProbook Global <span>Terms of Service.</span></h1>
        <p class="policy-date">Last Updated: January 2026 | Version 1.0</p>
    </div>
</section>

<!-- ===== POLICY SECTION ===== -->
<section class="policy-section">
    <div class="container">
        <div class="policy-document">
            <!-- Table of Contents -->
            <div class="toc">
                <h4>📋 Table of Contents</h4>
                <ul>
                    <li><a href="#section-1">1. License and Restrictions</a></li>
                    <li><a href="#section-2">2. Your Content and Data</a></li>
                    <li><a href="#section-3">3. Professional Disclaimer & Limitation of Liability</a></li>
                    <li><a href="#section-4">4. Compliance with Laws</a></li>
                    <li><a href="#section-5">5. Subscription, Fees, and Taxes</a></li>
                    <li><a href="#section-6">6. Intellectual Property Rights</a></li>
                    <li><a href="#section-7">7. Confidentiality & Security</a></li>
                    <li><a href="#section-8">8. Termination of Service</a></li>
                    <li><a href="#section-9">9. Governing Law and Jurisdiction</a></li>
                    <li><a href="#section-10">10. Contact & Support</a></li>
                </ul>
            </div>

            <!-- Section 1 -->
            <h2 id="section-1">1. License and Restrictions</h2>
            <p>SmartProbook Global Infrastructure Inc. ("SmartProbook") grants you a personal, limited, non-exclusive, revocable, and non-transferable license to use our Software-as-a-Service (SaaS) platform, subject to the terms and conditions of this Agreement. You agree not to use, nor permit any third party to use, the Services in a manner that violates any applicable law, regulation, court order, or this Agreement.</p>
            
            <h3>1.1 Usage Restrictions</h3>
            <p>You shall not:</p>
            <ul>
                <li>Provide access to or give any part of the Services to any third party without written authorization;</li>
                <li>Reproduce, modify, copy, sell, lease, or trade the Services or any components thereof;</li>
                <li>Attempt to unauthorizedly access any other SmartProbook systems not included in your subscription tier;</li>
                <li>Engage in any form of reverse engineering, disassembly, or decompilation of the platform;</li>
                <li>Use the Services for competitive analysis or development of competing products;</li>
                <li>Circumvent security measures or attempt unauthorized system access.</li>
            </ul>

            <!-- Section 2 -->
            <h2 id="section-2">2. Your Content and Data</h2>
            <p>You are solely responsible for all materials, data, financial information, and documents ("Content") uploaded, posted, or stored through your use of the Services. You grant SmartProbook a worldwide, royalty-free, non-exclusive, revocable license to host, process, and use the Content only as necessary to provide you with the Services and comply with legal obligations.</p>
            
            <h3>2.1 Data Ownership</h3>
            <p>You retain all ownership rights to your Content. SmartProbook does not claim ownership of your financial data, customer information, or proprietary business documents. You are responsible for maintaining accurate and lawful Content.</p>

            <h3>2.2 Data Portability and Deletion</h3>
            <p>At any time during your active subscription, you may export your Content in standard formats (CSV, PDF, JSON). Upon termination of service or request, SmartProbook will maintain your data for a period of <strong>60 days</strong> to facilitate final extraction. After this period, all data will be permanently deleted from our production and backup servers through secure data destruction protocols compliant with <strong>NIST SP 800-88</strong> guidelines.</p>

            <h3>2.3 Data Backup & Recovery</h3>
            <p>SmartProbook maintains automated daily backups of all Content to ensure data integrity and disaster recovery. In the event of data loss due to system failure, SmartProbook will restore your data from the most recent backup at no additional cost.</p>

            <!-- Section 3 -->
            <div class="disclaimer-box">
                <h4>⚠️ Section 3. Professional Disclaimer & Limitation of Liability</h4>
                <p><strong>SmartProbook IS NOT A LICENSED ACCOUNTING FIRM, LAW FIRM, OR AUDITING ENTITY.</strong> SmartProbook is a financial management software tool designed to automate bookkeeping and reporting. Use of the Services does <em>not</em> create an accountant-client, attorney-client, or auditor-client relationship. SmartProbook employees are not qualified to provide professional tax, legal, or accounting advice.</p>
                <p><strong>DISCLAIMER OF WARRANTIES:</strong> The Services are provided "AS-IS" and "AS-AVAILABLE." To the maximum extent permitted by Nigerian law, SmartProbook, its affiliates, officers, directors, and third-party providers disclaim all warranties, express or implied, including warranties of merchantability, fitness for a particular purpose, and non-infringement.</p>
                <p><strong>LIMITATION OF LIABILITY:</strong> SmartProbook shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including but not limited to damages for loss of profits, goodwill, use, data, or other intangible losses arising from: (i) unauthorized access to or use of the Services; (ii) data corruption, security breaches, or loss of data; (iii) viruses, malware, spyware, or ransomware; (iv) third-party interference; or (v) any other cause beyond SmartProbook's reasonable control. <strong>Total liability shall not exceed fees paid in the preceding 12 months.</strong></p>
            </div>

            <!-- Section 4 -->
            <h2 id="section-4">4. Compliance with Laws and Regulations</h2>
            <p>You represent and warrant that your use of the Services will comply with all applicable local, state, federal, and international laws and regulations. For users in Nigeria, this includes but is not limited to:</p>
            <ul>
                <li><strong>Companies and Allied Matters Act (CAMA) 2020</strong> – Corporate registration and governance;</li>
                <li><strong>Finance Act 2023</strong> – Tax compliance and reporting requirements;</li>
                <li><strong>Nigeria Data Protection Regulation (NDPR)</strong> – Personal data protection;</li>
                <li><strong>Nigerian Financial Reporting Council (NFRC) Standards</strong> – Accounting standards compliance;</li>
                <li><strong>IESBA Code of Ethics</strong> – Professional conduct standards;</li>
                <li><strong>Central Bank of Nigeria (CBN) Guidelines</strong> – For fintech and digital financial services;</li>
                <li><strong>Anti-Money Laundering (AML) & Know Your Customer (KYC) Requirements</strong>.</li>
            </ul>

            <h3>4.1 AML/CFT Compliance</h3>
            <p>SmartProbook reserves the right to suspend any account or block transactions suspected of violating Anti-Money Laundering (AML) or Counter-Financing of Terrorism (CFT) regulations. SmartProbook may conduct periodic compliance audits and may require additional documentation or verification.</p>

            <!-- Section 5 -->
            <h2 id="section-5">5. Subscription, Fees, and Taxes</h2>
            <p>Service rates and subscription tiers are established at the time of registration. SmartProbook reserves the right to adjust pricing with <strong>30 days' advance written notice</strong> to affected users. Price changes will not apply retroactively to active subscriptions without consent.</p>

            <h3>5.1 Billing and Payment</h3>
            <p>All fees are billed on the schedule selected (monthly, quarterly, or annually). Invoices will be sent via email. Payment must be received by the due date specified. Late payments may result in service suspension.</p>

            <h3>5.2 Taxes</h3>
            <p>All quoted fees are exclusive of applicable taxes including but not limited to Value Added Tax (VAT), Withholding Tax, and other government levies. SmartProbook will calculate and collect taxes as required by applicable jurisdiction. You are responsible for any additional taxes or assessments.</p>

            <h3>5.3 Refunds</h3>
            <p>Annual and quarterly subscriptions are non-refundable except as required by law. Monthly subscriptions may be cancelled with 14 days' notice for a prorated refund of unused service days.</p>

            <!-- Section 6 -->
            <h2 id="section-6">6. Intellectual Property Rights</h2>
            <p>All intellectual property rights, including patents, copyrights, trademarks, and trade secrets related to the SmartProbook platform, software code, documentation, and all improvements remain the exclusive property of SmartProbook Global Infrastructure Inc. You are granted a limited license to use these materials solely for operating your subscription account.</p>

            <h3>6.1 User Feedback</h3>
            <p>Any feedback, suggestions, or feature requests you provide to SmartProbook may be used without compensation or attribution. You grant SmartProbook a perpetual, royalty-free license to all such feedback.</p>

            <!-- Section 7 -->
            <h2 id="section-7">7. Confidentiality & Data Security</h2>
            <p>SmartProbook implements industry-standard security measures including <strong>256-bit AES encryption</strong> for data in transit and at rest, regular security audits, and intrusion detection systems. However, no system is 100% secure. SmartProbook cannot guarantee absolute security against all attacks.</p>

            <h3>7.1 Data Privacy</h3>
            <p>Your use of SmartProbook is governed by our Privacy Policy, which details how we collect, use, and protect your personal and financial data. By using the Services, you consent to our Privacy Policy terms.</p>

            <h3>7.2 Confidential Information</h3>
            <p>Each party agrees to maintain confidentiality of the other's proprietary information and trade secrets. This obligation survives termination of this Agreement for a period of <strong>three (3) years</strong>.</p>

            <!-- Section 8 -->
            <h2 id="section-8">8. Termination of Service</h2>
            <p>Either party may terminate this Agreement at any time. Upon termination:</p>
            <ul>
                <li>Your access to the Services will be revoked immediately;</li>
                <li>You have 60 days to export your data;</li>
                <li>All data will be permanently deleted after the 60-day period;</li>
                <li>You remain liable for all charges incurred before termination;</li>
                <li>Provisions regarding limitations of liability, confidentiality, and governing law survive termination.</li>
            </ul>

            <h3>8.1 Suspension for Violation</h3>
            <p>SmartProbook may immediately suspend or terminate your account without notice if you violate this Agreement, engage in illegal activity, or pose a risk to SmartProbook's systems or other users.</p>

            <!-- Section 9 -->
            <h2 id="section-9">9. Governing Law and Jurisdiction</h2>
            <p>This Agreement is governed by and construed in accordance with the <strong>laws of the Federal Republic of Nigeria</strong>, without regard to its conflict of law principles. Both parties irrevocably submit to the exclusive jurisdiction of the <strong>High Court of Enugu State, Nigeria</strong> for resolution of any disputes.</p>

            <div class="info-box">
                <h4>⚖️ Dispute Resolution</h4>
                <p><strong>Arbitration Clause:</strong> Before initiating litigation, both parties agree to attempt resolution through good-faith negotiation. If negotiation fails, disputes shall be submitted to binding arbitration under the rules of the <strong>Nigerian Arbitration and Conciliation Act</strong>, with arbitration conducted in Enugu, Nigeria.</p>
            </div>

            <!-- Section 10 -->
            <h2 id="section-10">10. Contact & Support</h2>
            <p>For questions, concerns, or formal notice regarding these terms, please contact:</p>

            <div class="contact-box">
                <p><strong>SmartProbook Global Infrastructure Inc.</strong></p>
                <p>Enugu Tech Hub, Independence Layout<br>Enugu, Nigeria</p>
                <p><strong>Email:</strong> <a href="mailto:legal@smartprobook.com">legal@smartprobook.com</a></p>
                <p><strong>Compliance:</strong> <a href="mailto:compliance@smartprobook.com">compliance@smartprobook.com</a></p>
                <p><strong>Support:</strong> <a href="mailto:support@smartprobook.com">support@smartprobook.com</a></p>
                <p><strong>Phone:</strong> <a href="tel:+234800728626226">+234 (0) 800 SmartProbook</a></p>
            </div>

            <div class="info-box" style="margin-top: 50px;">
                <h4>✅ Acceptance of Terms</h4>
                <p>By using SmartProbook, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service in their entirety. If you do not agree to any portion of these terms, you may not use the Services.</p>
                <p><strong>Effective Date:</strong> January 1, 2026</p>
            </div>

        </div>
    </div>
</section>

@endsection
