@component('mail::message')
# Demo Request Auto-Approved

A new demo request has been submitted on **SmartProbook** and was automatically approved. The applicant has been sent their demo credentials.

| Field | Value |
|---|---|
| **Name** | {{ $demoRequest->full_name }} |
| **Company** | {{ $demoRequest->company_name }} |
| **Business Type** | {{ $demoRequest->business_type ?? 'N/A' }} |
| **Email** | {{ $demoRequest->email }} |
| **Phone** | {{ $demoRequest->phone ?? 'N/A' }} |
| **Country** | {{ $demoRequest->country ?? 'N/A' }} |
| **Users** | {{ $demoRequest->number_of_users }} |
| **Purpose** | {{ $demoRequest->purpose ?? 'N/A' }} |
| **Submitted** | {{ $demoRequest->created_at->format('D, d M Y H:i') }} |

@component('mail::button', ['url' => config('app.url') . '/superadmin/demo-requests/' . $demoRequest->id, 'color' => 'primary'])
View Demo Request
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
