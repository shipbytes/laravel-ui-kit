<x-mail::message>
# Thanks for getting in touch

Hi {{ $submission->name }},

We have your message about **{{ $submission->subject }}** and will reply to this address. Here is a copy of what you sent:

@foreach (preg_split('/\R/', $submission->message) as $line)
> {{ $line }}
@endforeach

{{ config('app.name') }}
</x-mail::message>
