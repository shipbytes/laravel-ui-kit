{{-- ui-kit::page-header: the title block every admin screen opens with.
     The actions slot sits right of the title on a wide screen and wraps under
     it on a phone, where a title and two buttons do not fit on one line. --}}
@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-start justify-between gap-x-6 gap-y-4']) }}>
    <div class="min-w-0">
        <h1 class="text-title font-semibold text-fg">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-body text-fg-muted">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
