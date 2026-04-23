<div>
    <div class="mb-8">
        <h1 class="text-2xl/8 font-semibold text-zinc-950 dark:text-zinc-50 sm:text-xl/8">Support Tickets</h1>
        <p class="mt-2 text-sm/6 text-zinc-500 dark:text-zinc-400">Manage all user support requests.</p>

        <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-x-8 gap-y-6">
            <div>
                <div class="text-sm/6 font-medium text-zinc-500 dark:text-zinc-400">Total</div>
                <div class="mt-2 text-3xl/8 font-semibold text-zinc-950 dark:text-zinc-50">{{ $stats['total'] }}</div>
            </div>
            <div>
                <div class="text-sm/6 font-medium text-zinc-500 dark:text-zinc-400">Open</div>
                <div class="mt-2 text-3xl/8 font-semibold text-zinc-950 dark:text-zinc-50">{{ $stats['open'] }}</div>
            </div>
            <div>
                <div class="text-sm/6 font-medium text-zinc-500 dark:text-zinc-400">In Progress</div>
                <div class="mt-2 text-3xl/8 font-semibold text-zinc-950 dark:text-zinc-50">{{ $stats['in_progress'] }}</div>
            </div>
            <div>
                <div class="text-sm/6 font-medium text-zinc-500 dark:text-zinc-400">Resolved</div>
                <div class="mt-2 text-3xl/8 font-semibold text-zinc-950 dark:text-zinc-50">{{ $stats['resolved'] }}</div>
            </div>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="Search tickets..."
            class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
        <select wire:model.live="statusFilter" class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @foreach($statusOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="priorityFilter" class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @foreach($priorityOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="categoryFilter" class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @foreach($categoryOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <ul class="divide-y divide-zinc-950/10 dark:divide-white/10">
        @forelse($tickets as $ticket)
            <li>
                <a href="{{ route('admin.support.show', $ticket->ulid) }}" wire:navigate class="block hover:bg-zinc-950/[2.5%] dark:hover:bg-white/[2.5%] transition-colors">
                    <div class="flex items-center justify-between gap-4 py-4 px-2">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-1">
                                <h3 class="text-sm/6 font-semibold text-zinc-950 dark:text-zinc-50 truncate">{{ $ticket->subject }}</h3>
                                <span @class([
                                    'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                                    'bg-blue-50 text-blue-700 ring-blue-600/20' => $ticket->status === 'open',
                                    'bg-amber-50 text-amber-700 ring-amber-600/20' => $ticket->status === 'in_progress',
                                    'bg-green-50 text-green-700 ring-green-600/20' => $ticket->status === 'resolved',
                                    'bg-zinc-50 text-zinc-600 ring-zinc-500/20' => $ticket->status === 'closed',
                                ])>
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                </span>
                                <span @class([
                                    'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                                    'bg-zinc-50 text-zinc-600 ring-zinc-500/20' => $ticket->priority === 'low',
                                    'bg-blue-50 text-blue-700 ring-blue-600/20' => $ticket->priority === 'medium',
                                    'bg-red-50 text-red-700 ring-red-600/20' => $ticket->priority === 'high',
                                ])>
                                    {{ ucfirst($ticket->priority) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                <span>{{ $ticket->user->name ?? 'Unknown user' }}</span>
                                <span>&middot;</span>
                                <span>{{ ucfirst(str_replace('_', ' ', $ticket->category)) }}</span>
                                <span>&middot;</span>
                                <span>{{ $ticket->replies_count }} {{ Str::plural('reply', $ticket->replies_count) }}</span>
                                <span>&middot;</span>
                                <span>{{ $ticket->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <svg class="size-5 text-zinc-400 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </a>
            </li>
        @empty
            <li class="py-16 text-center">
                <h3 class="text-base/6 font-semibold text-zinc-950 dark:text-zinc-50 mb-1">No tickets found</h3>
                <p class="text-sm/6 text-zinc-500 dark:text-zinc-400">Try adjusting your filters.</p>
            </li>
        @endforelse
    </ul>

    @if($tickets->hasPages())
        <div class="mt-6">
            {{ $tickets->links() }}
        </div>
    @endif
</div>
