<div>
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl/8 font-semibold text-zinc-950 dark:text-zinc-50 sm:text-xl/8">Changelog</h1>
            <p class="mt-2 text-sm/6 text-zinc-500 dark:text-zinc-400">Manage changelog entries visible to users.</p>
        </div>
        <a
            href="{{ route('admin.changelog.create') }}"
            wire:navigate
            class="inline-flex items-center gap-2 rounded-lg bg-zinc-950 dark:bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:hover:bg-indigo-500"
        >
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            New Entry
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/30 dark:border-green-900 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center">
        <input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="Search by title..."
            class="block w-full max-w-md rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
        <select
            wire:model.live="categoryFilter"
            class="rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
            <option value="all">All Categories</option>
            <option value="feature">New Feature</option>
            <option value="improvement">Improvement</option>
            <option value="fix">Bug Fix</option>
            <option value="other">Other</option>
        </select>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-950/10 dark:border-white/10">
        <table class="w-full divide-y divide-zinc-950/5 dark:divide-white/5 table-fixed">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="w-auto px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Title</th>
                    <th class="w-28 px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Status</th>
                    <th class="w-28 px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-950/5 dark:divide-white/5">
                @forelse($entries as $entry)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-900/50">
                        <td class="px-4 py-3">
                            <span class="text-sm font-medium text-zinc-950 dark:text-zinc-50">{{ $entry->title }}</span>
                            <div class="mt-1 flex items-center gap-2">
                                @php
                                    $badgeClasses = match($entry->category) {
                                        'feature' => 'bg-indigo-50 text-indigo-700',
                                        'improvement' => 'bg-amber-50 text-amber-700',
                                        'fix' => 'bg-emerald-50 text-emerald-700',
                                        default => 'bg-zinc-50 text-zinc-700',
                                    };
                                @endphp
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $badgeClasses }}">
                                    {{ $entry->category_label }}
                                </span>
                                <span class="text-xs text-zinc-400">{{ $entry->published_at->format('M d, Y') }} &middot; {{ $entry->published_at->diffForHumans() }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if($entry->is_published)
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-green-50 text-green-700">Published</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-zinc-100 text-zinc-600">Draft</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a
                                    href="{{ route('admin.changelog.edit', $entry->ulid) }}"
                                    wire:navigate
                                    class="text-xs font-medium text-indigo-600 hover:text-indigo-700"
                                >
                                    Edit
                                </a>
                                <button
                                    wire:click="deleteEntry({{ $entry->id }})"
                                    wire:confirm="Are you sure you want to delete this changelog entry?"
                                    class="text-xs font-medium text-red-600 hover:text-red-700"
                                >
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            No changelog entries found. Create your first one!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $entries->links() }}
    </div>
</div>
