<div class="max-w-3xl space-y-6">
    <div>
        <x-ds::button variant="ghost" size="sm" :href="route(config('admin.route_name_prefix').'users.index')" wire:navigate>
            <x-ds::icon name="arrow-left" size="4" />
            All users
        </x-ds::button>
    </div>

    <div class="flex items-center gap-4">
        <x-ds::avatar size="lg" :name="$user->name" :decorative="true" />
        <x-ui-kit::page-header :title="$user->name" :subtitle="$user->email" class="min-w-0 flex-1" />
    </div>

    <x-ds::panel body="rows">
        <x-ds::panel-row>
            <span class="w-36 shrink-0 text-fg-muted">Joined</span>
            <span class="text-fg">{{ $user->created_at->format('F j, Y') }}</span>
        </x-ds::panel-row>
        <x-ds::panel-row>
            <span class="w-36 shrink-0 text-fg-muted">Email verified</span>
            <span class="text-fg">{{ $user->email_verified_at?->format('F j, Y') ?? 'Not verified' }}</span>
        </x-ds::panel-row>
    </x-ds::panel>
</div>
