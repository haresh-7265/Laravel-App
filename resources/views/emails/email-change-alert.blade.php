@component('mail::message')

# Security Alert: Email Address Changed

Hello,

This is a security notification to let you know that the email address associated with your account on **{{ config('app.name', 'Laravel App') }}** was recently changed.

- **Old Email Address:** {{ $oldEmail }}
- **New Email Address:** {{ $user->email }}

If you initiated this change, no further action is required. You will receive a separate email at your new address to verify it.

---

### Did you not request this change?

If this change was done without your authorization, your account may have been compromised. You can immediately revert this change and restore your old email address by clicking the button below:

@component('mail::button', ['url' => $cancelUrl, 'color' => 'error'])
Revert Email Change
@endcomponent

> [!WARNING]
> This link is only valid for **24 hours** from the time this email was sent.

If you are unable to use this link or require further assistance, please contact our support team immediately.

Thanks,  
**{{ config('app.name') }}**

@component('mail::subcopy')
If you did not make this request, please change your password immediately and secure your account.
@endcomponent

@endcomponent
