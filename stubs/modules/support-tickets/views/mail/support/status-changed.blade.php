<x-mail::message>
# Your ticket was updated

Hi {{ $ticket->user->name }},

The status of your ticket **{{ $ticket->subject }}** changed from {{ $oldLabel }} to **{{ $newLabel }}**.

@if ($isClosed)
If you still need help, you can reopen it from the ticket page.
@endif

<x-mail::button :url="$url">
View the ticket
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
