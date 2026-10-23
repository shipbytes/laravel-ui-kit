<x-mail::message>
# New contact message

**{{ $submission->name }}** ({{ $submission->email }}) wrote about **{{ $submission->subject }}**.

@foreach (preg_split('/\R/', $submission->message) as $line)
> {{ $line }}
@endforeach

<x-mail::button :url="$url">
Open the inbox
</x-mail::button>

Reply from the inbox to keep the answer with the message. Replying to this email writes to {{ $submission->name }} directly.

{{ config('app.name') }}
</x-mail::message>
