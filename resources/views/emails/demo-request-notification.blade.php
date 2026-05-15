@component('mail::message')
# New Demo Request Received

A new demo request has been submitted on **SmartProbook**.

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
Review & Approve / Reject
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
