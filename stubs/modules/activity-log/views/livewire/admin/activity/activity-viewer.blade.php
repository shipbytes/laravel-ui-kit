<div class="space-y-6">
    <x-ui-kit::page-header title="Activity Log" subtitle="Audit trail of actions performed across the application." />

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
        <x-ds::input as="select" wire:model.live="logName" label="Log stream">
            <option value="">All log streams</option>
            @foreach ($logNames as $name)
                <option value="{{ $name }}">{{ $name }}</option>
            @endforeach
        </x-ds::input>

        <x-ds::input wire:model.live.debounce.400ms="causerEmail" label="User email" placeholder="Filter by user email" />

        <x-ds::input type="date" wire:model.live="dateFrom" label="From" />

        <x-ds::input type="date" wire:model.live="dateTo" label="To" />

        <div>
            <x-ds::button variant="ghost" wire:click="clearFilters">Clear</x-ds::button>
        </div>
    </div>

    <x-ds::table :columns="['When', 'Who', 'Event', 'Subject', 'Description']">
        @forelse ($activities as $activity)
            <x-ds::table-row wire:key="activity-{{ $activity->id }}" class="align-top">
                <x-ds::table-cell :nowrap="true">
                    <div class="text-fg">{{ $activity->created_at->format('M d, Y') }}</div>
                    <div class="text-meta text-fg-muted tabular-nums">{{ $activity->created_at->format('H:i:s') }}</div>
                </x-ds::table-cell>
                <x-ds::table-cell>
                    @if ($activity->causer)
                        <div class="font-medium text-fg">{{ $activity->causer->name ?? $activity->causer->email ?? '—' }}</div>
                        <div class="text-meta text-fg-muted">{{ $activity->causer->email ?? '' }}</div>
                    @else
                        <span class="text-fg-muted">system</span>
                    @endif
                </x-ds::table-cell>
                <x-ds::table-cell :nowrap="true">
                    <div>{{ $activity->event ?? '—' }}</div>
                    <x-ds::badge variant="outline" class="mt-1 font-mono">{{ $activity->log_name ?? 'default' }}</x-ds::badge>
                </x-ds::table-cell>
                <x-ds::table-cell :nowrap="true">
                    @if ($activity->subject_type)
                        <span class="font-mono text-meta">{{ class_basename($activity->subject_type) }}#{{ $activity->subject_id }}</span>
                    @else
                        —
                    @endif
                </x-ds::table-cell>
                <x-ds::table-cell>{{ $activity->description }}</x-ds::table-cell>
            </x-ds::table-row>
        @empty
            <tr>
                <td colspan="5">
                    <x-ds::empty-state title="No activity records match these filters." :bare="true" />
                </td>
            </tr>
        @endforelse
    </x-ds::table>

    {{ $activities->links() }}
</div>
