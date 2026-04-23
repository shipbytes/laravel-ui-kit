<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-boldtext text-zinc-950 dark:text-zinc-50">Users</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $users->total() }} total</p>
        </div>
        <div class="w-72">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search by name or email..."
                   class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-zinc-950 ring-1 ring-zinc-300 placeholder:text-zinc-400 focus:ring-2 focus:ring-indigo-600 dark:bg-zinc-900 dark:text-zinc-100 dark:ring-white/10">
        </div>
    </div>

    <div class="overflow-hidden rounded-xl ring-1 ring-zinc-950/5 dark:ring-white/5">
        <table class="w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Verified</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Joined</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">&nbsp;</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                @forelse ($users as $user)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                        <td class="px-4 py-3 text-sm font-medium text-zinc-950 dark:text-zinc-100">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if ($user->email_verified_at)
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Verified</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $user->created_at->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route(config('admin.route_name_prefix').'users.show', $user) }}" wire:navigate class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">View →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-zinc-500">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</div>
