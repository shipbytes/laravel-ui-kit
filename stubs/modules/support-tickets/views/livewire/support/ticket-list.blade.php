<div class="space-y-6">
    <x-ui-kit::page-header title="Support" subtitle="Your questions to us, and our answers.">
        <x-slot:actions>
            <x-ds::button :href="route('support.create')" wire:navigate>
                <x-ds::icon name="plus" size="4" />
                New ticket
            </x-ds::button>
        </x-slot:actions>
    </x-ui-kit::page-header>

    @if (session()->has('success'))
        <x-ds::alert tone="success">{{ session('success') }}</x-ds::alert>
    @endif

    @if ($hasAny)
        <div class="max-w-56">
            <x-ds::input as="select" label="Status" wire:model.live="statusFilter">
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ds::input>
        </div>
    @endif

    @if ($tickets->isEmpty())
        @if ($hasAny)
            <x-ds::empty-state
                icon="funnel"
                title="No {{ strtolower($statusOptions[$statusFilter] ?? '') }} tickets"
                description="Pick another status to see the rest."
            />
        @else
            <x-ds::empty-state
                icon="lifebuoy"
                title="No tickets yet"
                description="Ask a question or report a problem, and we'll answer here and by email."
            >
                <x-slot:action>
                    <x-ds::button :href="route('support.create')" wire:navigate>New ticket</x-ds::button>
                </x-slot:action>
            </x-ds::empty-state>
        @endif
    @else
        <x-ds::table :columns="['Subject', 'Status', 'Priority', 'Updated', ['label' => '', 'align' => 'right']]">
            @foreach ($tickets as $ticket)
                <x-ds::table-row wire:key="ticket-{{ $ticket->id }}">
                    <x-ds::table-cell>
                        <div class="font-medium text-fg">{{ $ticket->subject }}</div>
                        <div class="text-meta text-fg-muted">{{ $ticket->categoryLabel() }} · {{ $ticket->replies_count }} {{ Str::plural('message', $ticket->replies_count) }}</div>
                    </x-ds::table-cell>
                    <x-ds::table-cell :nowrap="true">
                        <x-ds::badge :tone="$ticket->statusTone()">{{ $ticket->statusLabel() }}</x-ds::badge>
                    </x-ds::table-cell>
                    <x-ds::table-cell :nowrap="true">
                        <x-ds::badge :tone="$ticket->priorityTone()">{{ ucfirst($ticket->priority) }}</x-ds::badge>
                    </x-ds::table-cell>
                    <x-ds::table-cell :nowrap="true">{{ $ticket->updated_at->diffForHumans() }}</x-ds::table-cell>
                    <x-ds::table-cell align="right">
                        <x-ds::button
                            variant="ghost"
                            size="sm"
                            :href="route('support.show', $ticket->ulid)"
                            wire:navigate
                            aria-label="View {{ $ticket->subject }}"
                        >View</x-ds::button>
                    </x-ds::table-cell>
                </x-ds::table-row>
            @endforeach
        </x-ds::table>

        {{ $tickets->links() }}
    @endif
</div>
