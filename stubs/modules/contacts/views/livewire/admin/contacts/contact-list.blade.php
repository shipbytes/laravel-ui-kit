<div class="space-y-6">
    <x-ui-kit::page-header title="Contact Messages" subtitle="View and manage contact form submissions." />

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ds::stat-tile label="Total" :value="$stats['total']" :countUp="false" />
        <x-ds::stat-tile label="Unread" :value="$stats['unread']" :countUp="false" />
        <x-ds::stat-tile label="Replied" :value="$stats['replied']" :countUp="false" />
        <x-ds::stat-tile label="Archived" :value="$stats['archived']" :countUp="false" />
    </div>

    @if (session('success'))
        <x-ds::alert tone="success">{{ session('success') }}</x-ds::alert>
    @endif
    @if (session('error'))
        <x-ds::alert tone="danger">{{ session('error') }}</x-ds::alert>
    @endif

    <div class="grid max-w-2xl grid-cols-1 gap-3 sm:grid-cols-2">
        <x-ds::input
            type="search"
            icon="magnifying-glass"
            wire:model.live.debounce.300ms="search"
            placeholder="Search by name, email or subject..."
            aria-label="Search messages"
        />
        <x-ds::input as="select" wire:model.live="statusFilter" aria-label="Status">
            <option value="all">All Messages</option>
            <option value="unread">Unread</option>
            <option value="read">Read</option>
            <option value="replied">Replied</option>
            <option value="archived">Archived</option>
        </x-ds::input>
    </div>

    @if (count($selected) > 0)
        <div class="flex flex-wrap items-center gap-2 rounded-control border border-border bg-accent-wash px-4 py-2">
            <span class="mr-2 text-body font-medium text-fg">{{ count($selected) }} {{ str('message')->plural(count($selected)) }} selected</span>
            <x-ds::button variant="ghost" size="sm" wire:click="markSelectedAs('read')">Mark read</x-ds::button>
            <x-ds::button variant="ghost" size="sm" wire:click="markSelectedAs('unread')">Mark unread</x-ds::button>
            <x-ds::button variant="ghost" size="sm" wire:click="archiveSelected">Archive</x-ds::button>
            <x-ds::button
                variant="ghost"
                size="sm"
                tone="danger"
                wire:click="deleteSelected"
                wire:confirm="Are you sure you want to delete {{ count($selected) }} selected messages?"
            >Delete</x-ds::button>
        </div>
    @endif

    {{-- No `columns`: the select-all box lives in the header, so the head is ours. --}}
    <x-ds::table>
        <thead class="bg-surface-subtle">
            <tr>
                <th scope="col" class="w-12 px-4 py-3">
                    <x-ds::checkbox label="Select all" :hideLabel="true" wire:model.live="selectAll" />
                </th>
                <th scope="col" class="px-4 py-3 text-left text-overline uppercase text-fg-muted">Message</th>
                <th scope="col" class="px-4 py-3 text-left text-overline uppercase text-fg-muted">Status</th>
                <th scope="col" class="px-4 py-3 text-left text-overline uppercase text-fg-muted">Received</th>
                <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
            </tr>
        </thead>

        @forelse ($submissions as $submission)
            <x-ds::table-row wire:key="submission-{{ $submission->id }}">
                <x-ds::table-cell>
                    <x-ds::checkbox
                        :label="'Select '.$submission->subject"
                        :hideLabel="true"
                        wire:model.live="selected"
                        :value="$submission->id"
                    />
                </x-ds::table-cell>
                <x-ds::table-cell>
                    <div @class([
                        'text-fg',
                        'font-semibold' => $submission->status === 'unread',
                        'font-medium' => $submission->status !== 'unread',
                    ])>{{ $submission->subject }}</div>
                    <div class="text-meta text-fg-muted">
                        {{ $submission->name }}
                        <span class="max-sm:hidden">&lt;{{ $submission->email }}&gt;</span>
                    </div>
                </x-ds::table-cell>
                <x-ds::table-cell :nowrap="true">
                    <div class="flex items-center gap-1.5">
                        <x-ds::badge :tone="$submission->statusTone()">{{ ucfirst($submission->status) }}</x-ds::badge>
                        @if ($submission->archived_at)
                            <x-ds::badge>Archived</x-ds::badge>
                        @endif
                    </div>
                </x-ds::table-cell>
                <x-ds::table-cell :nowrap="true">{{ $submission->created_at->diffForHumans() }}</x-ds::table-cell>
                <x-ds::table-cell align="right">
                    <x-ds::button
                        variant="ghost"
                        size="sm"
                        wire:click="openModal({{ $submission->id }})"
                        aria-label="Open {{ $submission->subject }}"
                    >Open</x-ds::button>
                </x-ds::table-cell>
            </x-ds::table-row>
        @empty
            <tr>
                <td colspan="5">
                    <x-ds::empty-state title="No messages found" description="Try adjusting your filters." :bare="true" />
                </td>
            </tr>
        @endforelse
    </x-ds::table>

    {{ $submissions->links() }}

    {{-- The modal's ✕, backdrop and Escape write false to showModal. Entangled,
         that alone would leave viewingId and the reply draft on the server, so a
         close that started in the browser also calls closeModal(). A close that
         started on the server (archive, delete) has already cleared viewingId,
         and is not sent back. --}}
    <div
        x-data="{ showModal: $wire.entangle('showModal') }"
        x-init="$watch('showModal', open => { if (! open && $wire.viewingId !== null) $wire.closeModal() })"
    >
        <x-ds::modal open="showModal" size="2xl" :title="$viewingSubmission?->subject">
            @if ($viewingSubmission)
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-meta text-fg-muted">
                        <x-ds::badge :tone="$viewingSubmission->statusTone()">{{ ucfirst($viewingSubmission->status) }}</x-ds::badge>
                        <span class="font-medium text-fg">{{ $viewingSubmission->name }}</span>
                        <span>&lt;{{ $viewingSubmission->email }}&gt;</span>
                        <span aria-hidden="true">&middot;</span>
                        <span>{{ $viewingSubmission->created_at->format('M d, Y \a\t g:i A') }}</span>
                        @if ($viewingSubmission->ip_address)
                            <span aria-hidden="true">&middot;</span>
                            <span>IP: {{ $viewingSubmission->ip_address }}</span>
                        @endif
                    </div>

                    <div class="whitespace-pre-wrap text-body text-fg-body">{{ $viewingSubmission->message }}</div>

                    <div class="border-t border-divider pt-4">
                        @if ($viewingSubmission->replied_at)
                            <div class="rounded-control border border-border bg-success-wash p-4">
                                <div class="mb-2 flex flex-wrap items-center gap-2 text-meta">
                                    <span class="font-medium text-fg">Your Reply</span>
                                    <span class="text-fg-muted">{{ $viewingSubmission->replied_at->format('M d, Y \a\t g:i A') }}</span>
                                </div>
                                <div class="whitespace-pre-wrap text-body text-fg-body">{{ $viewingSubmission->reply }}</div>
                            </div>
                        @else
                            <div class="space-y-3">
                                <x-ds::input
                                    as="textarea"
                                    wire:model="replyText"
                                    id="reply-text"
                                    :rows="4"
                                    label="Reply to {{ $viewingSubmission->name }}"
                                    placeholder="Type your reply... (min 10 characters)"
                                    :error="$errors->first('replyText')"
                                />
                                <div class="flex justify-end">
                                    <x-ds::button
                                        size="sm"
                                        wire:click="submitReply({{ $viewingSubmission->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="submitReply"
                                    >
                                        <span wire:loading.remove wire:target="submitReply">Send Reply</span>
                                        <span wire:loading wire:target="submitReply">Sending...</span>
                                    </x-ds::button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <x-slot:footer>
                @if ($viewingSubmission)
                    <div class="flex w-full flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($senderIsUser && $this->supportTicketsInstalled())
                                <x-ds::button
                                    variant="secondary"
                                    size="sm"
                                    wire:click="copyToTicket({{ $viewingSubmission->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="copyToTicket"
                                >
                                    <span wire:loading.remove wire:target="copyToTicket">Copy to Ticket</span>
                                    <span wire:loading wire:target="copyToTicket">Copying...</span>
                                </x-ds::button>
                            @endif

                            <x-ds::button variant="secondary" size="sm" wire:click="toggleReadStatus({{ $viewingSubmission->id }})">
                                {{ $viewingSubmission->status === 'unread' ? 'Mark Read' : 'Mark Unread' }}
                            </x-ds::button>

                            @if ($viewingSubmission->archived_at)
                                <x-ds::button variant="secondary" size="sm" wire:click="unarchiveSubmission({{ $viewingSubmission->id }})">Unarchive</x-ds::button>
                            @else
                                <x-ds::button variant="secondary" size="sm" wire:click="archiveSubmission({{ $viewingSubmission->id }})">Archive</x-ds::button>
                            @endif
                        </div>

                        <x-ds::button
                            variant="secondary"
                            size="sm"
                            tone="danger"
                            wire:click="deleteSubmission({{ $viewingSubmission->id }})"
                            wire:confirm="Are you sure you want to delete this contact submission?"
                        >Delete</x-ds::button>
                    </div>
                @endif
            </x-slot:footer>
        </x-ds::modal>
    </div>
</div>
