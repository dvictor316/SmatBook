@component('mail::message')
# Your SmartProbook Demo is Ready!

Hi **{{ $demoRequest->full_name }}**,

Great news! Your SmartProbook demo request has been approved. Your temporary demo environment is set up and ready to explore.

---

## Your Demo Login Credentials

| | |
|---|---|
| **Login URL** | [{{ $loginUrl }}]({{ $loginUrl }}) |
| **Email** | {{ $loginEmail }} |
| **Password** | `{{ $plainPassword }}` |
| **Access Expires** | {{ $demoRequest->expires_at ? $demoRequest->expires_at->format('D, d M Y H:i') . ' (48 hours)' : 'N/A' }} |

---

> **Important:** This is a **DEMO environment** seeded with sample data. Do not enter any real business data. The account will be automatically deactivated after 48 hours.

@component('mail::button', ['url' => $loginUrl, 'color' => 'success'])
Log In to Your Demo
@endcomponent

**What to explore:**
- Dashboard & real-time analytics
- Sales, Invoicing & POS
- Inventory management
- Financial reports & accounting

If you have questions, reply to this email — we're happy to help!

Thanks,<br>
The SmartProbook Team
@endcomponent
