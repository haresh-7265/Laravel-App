<x-mail::message>
# New Contact Form Submission

You have received a new message from the contact form.

---

**From:** {{ $data['name'] }} ({{ $data['email'] }})

**Subject:** {{ $data['subject'] }}

---

### Message

{{ $data['message'] }}

---

<x-mail::button :url="'mailto:' . $data['email']">
Reply to {{ $data['name'] }}
</x-mail::button>

<small>This message was sent from the contact form on {{ config('app.name') }}.</small>
</x-mail::message>
