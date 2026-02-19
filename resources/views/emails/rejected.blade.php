/*
|--------------------------------------------------------------------------
| EMAIL VIEW: resources/views/emails/rejected.blade.php
|--------------------------------------------------------------------------
| Professional rejection notice with dynamic reason.
| domain => env('SESSION_DOMAIN', null)
*/

@component('mail::message')
# Application Update

Hello {{ $user->name }},

Thank you for your interest in our platform. After reviewing your application details, we regret to inform you that we are unable to approve your account at this time.

**Status:** Declined  
**Reason for Decision:** > {{ $reason }}

### What does this mean?
Your account has been restricted from accessing manager-level features. If you believe this decision was made in error or if you have additional documentation to provide, please reply to this email or contact our compliance team.

Best regards,<br>
{{ config('app.name') }} Compliance Team
@endcomponent