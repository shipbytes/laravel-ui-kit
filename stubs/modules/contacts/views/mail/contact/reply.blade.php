<x-mail::message>
Hi {{ $submission->name }},

{{ $submission->reply }}

---

On {{ $submission->created_at->format('j F Y') }} you wrote:

@foreach (preg_split('/\R/', $submission->message) as $line)
> {{ $line }}
@endforeach

{{ config('app.name') }}
</x-mail::message>
