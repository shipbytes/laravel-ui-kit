@props(['user'])

@if(app()->bound('impersonate')
    && auth()->user()
    && method_exists(auth()->user(), 'canImpersonate') && auth()->user()->canImpersonate()
    && method_exists($user, 'canBeImpersonated') && $user->canBeImpersonated()
    && auth()->id() !== $user->getKey())
    <x-ds::button
        variant="secondary"
        size="sm"
        :href="route('impersonate', $user->getKey())"
        onclick="return confirm('Login as {{ $user->name ?? $user->email }}?');"
    >
        Login as
    </x-ds::button>
@endif
