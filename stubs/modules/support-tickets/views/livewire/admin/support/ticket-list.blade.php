<div class="space-y-6">
    <x-ui-kit::page-header title="Support Tickets" subtitle="Manage all user support requests." />

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ds::stat-tile label="Total" :value="$stats['total']" :countUp="false" />
        <x-ds::stat-tile label="Open" :value="$stats['open']" :countUp="false" />
        <x-ds::stat-tile label="In Progress" :value="$stats['in_progress']" :countUp="false" />
        <x-ds::stat-tile label="Resolved" :value="$stats['resolved']" :countUp="false" />
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-ds::input
            type="search"
            icon="magnifying-glass"
            wire:model.live.debounce.300ms="search"
            placeholder="Search tickets..."
            aria-label="Search tickets"
        />
        <x-ds::input as="select" wire:model.live="statusFilter" aria-label="Status">
            @foreach ($statusOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-ds::input>
        <x-ds::input as="select" wire:model.live="priorityFilter" aria-label="Priority">
            @foreach ($priorityOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-ds::input>
        <x-ds::input as="select" wire:model.live="categoryFilter" aria-label="Category">
            @foreach ($categoryOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-ds::input>
    </div>

    <x-ds::table :columns="['Subject', 'From', 'Status', 'Priority', 'Category', 'Updated', ['label' => '', 'align' => 'right']]">
        @forelse ($tickets as $ticket)
            <x-ds::table-row wire:key="ticket-{{ $ticket->id }}">
                <x-ds::table-cell>
                    <div class="font-medium text-fg">{{ $ticket->subject }}</div>
                    <div class="text-meta text-fg-muted">{{ $ticket->replies_count }} {{ Str::plural('reply', $ticket->replies_count) }}</div>
                </x-ds::table-cell>
                <x-ds::table-cell>{{ $ticket->user->name ?? 'Unknown user' }}</x-ds::table-cell>
                <x-ds::table-cell :nowrap="true">
                    <x-ds::badge :tone="$ticket->statusTone()">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</x-ds::badge>
                </x-ds::table-cell>
                <x-ds::table-cell :nowrap="true">
                    <x-ds::badge :tone="$ticket->priorityTone()">{{ ucfirst($ticket->priority) }}</x-ds::badge>
                </x-ds::table-cell>
                <x-ds::table-cell :nowrap="true">{{ ucfirst(str_replace('_', ' ', $ticket->category)) }}</x-ds::table-cell>
                <x-ds::table-cell :nowrap="true">{{ $ticket->updated_at->diffForHumans() }}</x-ds::table-cell>
                <x-ds::table-cell align="right">
                    <x-ds::button
                        variant="ghost"
                        size="sm"
                        :href="route('admin.support.show', $ticket->ulid)"
                        wire:navigate
                        aria-label="View {{ $ticket->subject }}"
                    >View</x-ds::button>
                </x-ds::table-cell>
            </x-ds::table-row>
        @empty
            <tr>
                <td colspan="7">
                    <x-ds::empty-state title="No tickets found" description="Try adjusting your filters." :bare="true" />
                </td>
            </tr>
        @endforelse
    </x-ds::table>

    {{ $tickets->links() }}
</div>
