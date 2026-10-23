<x-mail::message>
# New support ticket

**{{ $ticket->user->name }}** ({{ $ticket->user->email }}) opened a ticket.

**Subject:** {{ $ticket->subject }}<br>
**Category:** {{ $ticket->categoryLabel() }}

@foreach (preg_split('/\R/', $opening) as $line)
> {{ $line }}
@endforeach

<x-mail::button :url="$url">
Open the ticket
</x-mail::button>

Answer from the admin panel so the reply stays on the ticket. Replying to this email writes to {{ $ticket->user->name }} directly.

{{ config('app.name') }}
</x-mail::message>
