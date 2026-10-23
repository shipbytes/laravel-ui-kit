@props(['status'])

@if ($status)
    <x-ds::alert tone="success" {{ $attributes }}>
        {{ $status }}
    </x-ds::alert>
@endif
