<div>
    <div class="mb-8">
        <h1 class="text-2xl/8 font-semibold text-zinc-950 dark:text-zinc-50 sm:text-xl/8">Activity Log</h1>
        <p class="mt-2 text-sm/6 text-zinc-500 dark:text-zinc-400">Audit trail of actions performed across the application.</p>
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <select wire:model.live="logName"
            class="rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All log streams</option>
            @foreach($logNames as $name)
                <option value="{{ $name }}">{{ $name }}</option>
            @endforeach
        </select>

        <input type="text" wire:model.live.debounce.400ms="causerEmail" placeholder="Filter by user email"
            class="rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">

        <input type="date" wire:model.live="dateFrom"
            class="rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">

        <input type="date" wire:model.live="dateTo"
            class="rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">

        <button wire:click="clearFilters" type="button"
            class="rounded-lg bg-white dark:bg-zinc-800 px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 ring-1 ring-zinc-950/10 dark:ring-white/10 hover:bg-zinc-50 dark:hover:bg-zinc-700">
            Clear
        </button>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-950/10 dark:border-white/10">
        <table class="w-full divide-y divide-zinc-950/5 dark:divide-white/5">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">When</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Log</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Causer</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Event</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Subject</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-950/5 dark:divide-white/5">
                @forelse($activities as $activity)
                    <tr class="align-top">
                        <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                            <div>{{ $activity->created_at->format('M d, Y') }}</div>
                            <div class="text-[11px]">{{ $activity->created_at->format('H:i:s') }}</div>
                        </td>
                        <td class="px-4 py-3 text-xs text-zinc-700 dark:text-zinc-200">
                            <span class="inline-flex rounded-md bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 font-mono">{{ $activity->log_name ?? 'default' }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-950 dark:text-zinc-50">
                            @if($activity->causer)
                                <div class="font-medium">{{ $activity->causer->name ?? $activity->causer->email ?? '—' }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $activity->causer->email ?? '' }}</div>
                            @else
                                <span class="text-xs text-zinc-400">system</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-zinc-700 dark:text-zinc-200">{{ $activity->event ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-zinc-700 dark:text-zinc-200">
                            @if($activity->subject_type)
                                <span class="font-mono">{{ class_basename($activity->subject_type) }}#{{ $activity->subject_id }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-200">{{ $activity->description }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            No activity records match these filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $activities->links() }}</div>
</div>
