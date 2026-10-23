<div class="max-w-3xl space-y-6">
    <div>
        <x-ds::button variant="ghost" size="sm" :href="route('support.index')" wire:navigate>
            <x-ds::icon name="arrow-left" size="4" />
            All tickets
        </x-ds::button>
    </div>

    <x-ui-kit::page-header
        :title="$ticket->subject"
        subtitle="{{ $ticket->categoryLabel() }} · opened {{ $ticket->created_at->format('M j, Y') }}"
    >
        <x-slot:actions>
            <x-ds::badge :tone="$ticket->statusTone()">{{ $ticket->statusLabel() }}</x-ds::badge>
            @can('update', $ticket)
                @if ($ticket->isClosed())
                    <x-ds::button variant="secondary" size="sm" wire:click="reopenTicket">Reopen ticket</x-ds::button>
                @else
                    <x-ds::button variant="ghost" size="sm" wire:click="closeTicket">Close ticket</x-ds::button>
                @endif
            @endcan
        </x-slot:actions>
    </x-ui-kit::page-header>

    @if (session()->has('success'))
        <x-ds::alert tone="success">{{ session('success') }}</x-ds::alert>
    @endif

    <section class="space-y-3" aria-labelledby="ticket-conversation">
        <h2 id="ticket-conversation" class="text-section font-semibold text-fg">Conversation</h2>

        @foreach ($ticket->replies as $ticketReply)
            <x-ds::panel body="plain" wire:key="reply-{{ $ticketReply->id }}">
                <div class="flex gap-3">
                    <x-ds::avatar size="sm" :name="$ticketReply->user?->name" :src="\Shipbytes\UiKit\Support\UiKit::avatarUrl($ticketReply->user)" :decorative="true" />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span class="text-body font-semibold text-fg">{{ $ticketReply->user->name ?? 'Unknown user' }}</span>
                            @if ($ticketReply->is_admin_reply)
                                <x-ds::badge tone="accent">Support team</x-ds::badge>
                            @endif
                            <span class="text-meta text-fg-muted">{{ $ticketReply->created_at->format('M j, Y \a\t g:i A') }}</span>
                        </div>
                        <div class="mt-1 whitespace-pre-wrap break-words text-body text-fg-body">{{ $ticketReply->message }}</div>
                    </div>
                </div>
            </x-ds::panel>
        @endforeach
    </section>

    @if ($ticket->isClosed())
        <x-ds::panel body="plain">
            <p class="text-body text-fg-muted">This ticket is {{ strtolower($ticket->statusLabel()) }}. Reopen it to add a reply.</p>
        </x-ds::panel>
    @else
        <x-ds::panel title="Add a reply" body="plain">
            <form wire:submit="submitReply" class="space-y-4">
                @error('limit')
                    <x-ds::alert tone="danger">{{ $message }}</x-ds::alert>
                @enderror

                <x-ds::input
                    as="textarea"
                    wire:model="reply"
                    :rows="4"
                    maxlength="5000"
                    placeholder="Write your reply..."
                    aria-label="Your reply"
                    :error="$errors->first('reply')"
                />
                <div class="flex justify-end">
                    <x-ds::button type="submit" wire:loading.attr="disabled" wire:target="submitReply">
                        <span wire:loading.remove wire:target="submitReply">Send reply</span>
                        <span wire:loading wire:target="submitReply">Sending...</span>
                    </x-ds::button>
                </div>
            </form>
        </x-ds::panel>
    @endif
</div>
