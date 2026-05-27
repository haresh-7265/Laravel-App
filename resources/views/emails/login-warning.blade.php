<x-mail::message>
# Security Alert: Multiple Failed Login Attempts

Hello,

This is a warning that there have been **5 consecutive failed login attempts** for your account ({{ $email }}).

As a security precaution, your account has been temporarily locked for **15 minutes**.

**Attempt Details:**
* **IP Address:** {{ $ip }}
* **Time:** {{ $time }}

If this was you, you can try logging in again after the lockout period expires.

If you did not make these attempts, someone may be trying to access your account. We recommend that you update your password to a strong, unique one immediately once your account is unlocked.

<x-mail::button :url="config('app.url')">
Visit Store
</x-mail::button>

Thanks,<br>
{{ config('app.name') }} Security Team
</x-mail::message>
