@component('mail::message')
# Update on Your SmartProbook Demo Request

Hi **{{ $demoRequest->full_name }}**,

Thank you for your interest in SmartProbook.

After reviewing your demo request for **{{ $demoRequest->company_name }}**, we are unable to approve it at this time.

@if($demoRequest->admin_note)
**Reason:** {{ $demoRequest->admin_note }}
@endif

We encourage you to visit our website to learn more about our plans, or to [contact our team]({{ config('app.url') }}/contact-us) if you have any questions.

Thanks,<br>
The SmartProbook Team
@endcomponent
