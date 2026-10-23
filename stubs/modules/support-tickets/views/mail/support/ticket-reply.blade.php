<x-mail::message>
@if ($toAdmin)
# New reply on a ticket

**{{ $reply->user->name ?? 'The user' }}** replied to **{{ $ticket->subject }}**.
@else
# New reply to your ticket

Hi {{ $ticket->user->name }}, there is a new reply to your ticket **{{ $ticket->subject }}**.
@endif

@foreach (preg_split('/\R/', $reply->message) as $line)
> {{ $line }}
@endforeach

<x-mail::button :url="$url">
{{ $toAdmin ? 'Open the ticket' : 'View the conversation' }}
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
