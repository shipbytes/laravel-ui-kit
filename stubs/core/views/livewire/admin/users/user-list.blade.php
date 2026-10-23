<div class="space-y-6">
    <x-ui-kit::page-header title="Users" :subtitle="$users->total().' total'" />

    <div class="max-w-sm">
        <x-ds::input
            type="search"
            icon="magnifying-glass"
            wire:model.live.debounce.300ms="search"
            placeholder="Search by name or email..."
            aria-label="Search users"
        />
    </div>

    <x-ds::table :columns="['Name', 'Email', 'Verified', 'Joined', ['label' => '', 'align' => 'right']]">
        @forelse ($users as $user)
            <x-ds::table-row wire:key="user-{{ $user->id }}">
                <x-ds::table-cell>
                    <div class="flex items-center gap-3">
                        <x-ds::avatar size="sm" :name="$user->name" :src="\Shipbytes\UiKit\Support\UiKit::avatarUrl($user)" :decorative="true" />
                        <span class="font-medium text-fg">{{ $user->name }}</span>
                        @if (\Shipbytes\UiKit\Support\UiKit::isAdmin($user))
                            <x-ds::badge tone="accent">Admin</x-ds::badge>
                        @endif
                    </div>
                </x-ds::table-cell>
                <x-ds::table-cell>{{ $user->email }}</x-ds::table-cell>
                <x-ds::table-cell :nowrap="true">
                    <x-ds::badge :tone="$user->email_verified_at ? 'success' : 'neutral'">{{ $user->email_verified_at ? 'Verified' : 'Pending' }}</x-ds::badge>
                </x-ds::table-cell>
                <x-ds::table-cell :nowrap="true">{{ $user->created_at->format('M j, Y') }}</x-ds::table-cell>
                <x-ds::table-cell align="right">
                    <x-ds::button
                        variant="ghost"
                        size="sm"
                        :href="route(config('admin.route_name_prefix').'users.show', $user)"
                        wire:navigate
                        aria-label="View {{ $user->name }}"
                    >View</x-ds::button>
                </x-ds::table-cell>
            </x-ds::table-row>
        @empty
            <tr>
                <td colspan="5">
                    <x-ds::empty-state title="No users found" :description="$search !== '' ? 'Nothing matches that name or email.' : null" :bare="true" />
                </td>
            </tr>
        @endforelse
    </x-ds::table>

    {{ $users->links() }}
</div>
