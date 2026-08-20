{{--
    Renders the brand logo, falling back to an initial badge when no logo
    file has been dropped in yet (fresh installs ship none).

    @include('layouts.partials.brand-mark', ['markClass' => 'h-6 w-auto', 'badgeClass' => 'size-6'])
--}}
@php
    $brandLogo = config('ui-kit.brand.logo');
    $brandIsRemote = is_string($brandLogo) && str_starts_with($brandLogo, 'http');
    $brandLogoExists = $brandIsRemote
        || ($brandLogo && file_exists(public_path(ltrim($brandLogo, '/'))));
@endphp
@if ($brandLogoExists)
    <img src="{{ $brandIsRemote ? $brandLogo : asset($brandLogo) }}" alt="{{ config('ui-kit.brand.name') }}" class="{{ $markClass ?? 'h-6 w-auto' }}">
@else
    <span class="flex {{ $badgeClass ?? 'size-6' }} shrink-0 items-center justify-center rounded-lg bg-brand-600 text-xs font-bold text-white">
        {{ strtoupper(mb_substr(config('ui-kit.brand.name') ?: 'A', 0, 1)) }}
    </span>
@endif
