@props(['user'])

@if(app()->bound('impersonate')
    && auth()->user()
    && method_exists(auth()->user(), 'canImpersonate') && auth()->user()->canImpersonate()
    && method_exists($user, 'canBeImpersonated') && $user->canBeImpersonated()
    && auth()->id() !== $user->getKey())
    <a href="{{ route('impersonate', $user->getKey()) }}"
       class="inline-flex items-center gap-1.5 rounded-md bg-amber-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-amber-500"
       onclick="return confirm('Login as {{ $user->name ?? $user->email }}?');">
        Login as
    </a>
@endif
