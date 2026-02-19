/*
|--------------------------------------------------------------------------
| EMAIL VIEW: resources/views/emails/approved.blade.php
|--------------------------------------------------------------------------
| Professional welcome for verified managers.
| domain => env('SESSION_DOMAIN', null)
*/

@component('mail::message')
# Account Verified!

Hello {{ $user->name }},

We are pleased to inform you that your application as a **Deployment Manager** has been approved. Your account is now fully verified, and your administrative workspace has been provisioned.

@component('mail::button', ['url' => url('/login')])
Access My Dashboard
@endcomponent

### Next Steps:
* Log in using your registered email and password.
* Complete your business profile setup.
* Begin managing your assigned deployments.

If you encounter a "Pending" message upon login, please clear your browser cache or log out and back in to refresh your access tokens.

Thanks,<br>
{{ config('app.name') }} Administration
@endcomponent