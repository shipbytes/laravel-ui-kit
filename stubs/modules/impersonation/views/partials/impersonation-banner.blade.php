@if(app()->bound('impersonate') && app('impersonate')->isImpersonating())
    <div class="sticky top-0 z-50 w-full border-b border-border bg-surface px-4 py-2">
        <x-ds::alert tone="warning" class="mx-auto max-w-7xl">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <span>Impersonating <strong>{{ auth()->user()->name ?? auth()->user()->email }}</strong></span>
                <x-ds::button variant="secondary" size="sm" :href="route('impersonate.leave')">
                    Exit impersonation
                </x-ds::button>
            </div>
        </x-ds::alert>
    </div>
@endif
