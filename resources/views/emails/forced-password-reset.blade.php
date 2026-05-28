@component('mail::message')
# Security Notice: Password Reset Required

Hello {{ $user->name }},

An administrator has flagged your account for a forced password reset due to suspicious activity or security policy enforcement.

To regain access to your account, you must reset your password. You can request a reset link using the button below or by visiting the login page and choosing "Forgot Password".

@component('mail::button', ['url' => route('password.request')])
Reset Password
@endcomponent

If you did not request this or have any concerns, please contact our support team.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
