@if(app()->bound('impersonate') && app('impersonate')->isImpersonating())
    <div class="sticky top-0 z-50 w-full bg-amber-500 text-amber-950">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-2 text-sm">
            <div class="flex items-center gap-2">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.2 16c-.77 1.33.19 3 1.73 3z" />
                </svg>
                <span>Impersonating <strong>{{ auth()->user()->name ?? auth()->user()->email }}</strong></span>
            </div>
            <a href="{{ route('impersonate.leave') }}"
               class="inline-flex items-center gap-1.5 rounded-md bg-amber-950/10 px-2.5 py-1 text-xs font-semibold hover:bg-amber-950/20">
                Exit impersonation
            </a>
        </div>
    </div>
@endif
