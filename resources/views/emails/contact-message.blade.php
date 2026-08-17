<x-mail::message>
# New contact form message

**From:** {{ $senderName }} ({{ $senderEmail }})

**Subject:** {{ $messageSubject }}

{{ $messageBody }}

<x-mail::button :url="'mailto:' . $senderEmail">
Reply to {{ $senderName }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
