<div class="max-w-5xl space-y-6">
    <div>
        <x-ds::button variant="ghost" size="sm" :href="route('admin.support.index')" wire:navigate>
            <x-ds::icon name="arrow-left" size="4" />
            Back to tickets
        </x-ds::button>
    </div>

    <x-ui-kit::page-header :title="$ticket->subject" />

    @if (session()->has('success'))
        <x-ds::alert tone="success">{{ session('success') }}</x-ds::alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ds::panel body="plain">
                <div class="flex items-center gap-3">
                    <x-ds::avatar :name="$ticket->user?->name" :decorative="true" />
                    <div class="min-w-0">
                        <div class="text-body font-semibold text-fg">{{ $ticket->user->name ?? 'Unknown user' }}</div>
                        <div class="truncate text-meta text-fg-muted">{{ $ticket->user->email ?? '' }}</div>
                    </div>
                </div>

                <dl class="mt-4 grid grid-cols-1 gap-3 border-t border-divider pt-4 text-meta sm:grid-cols-2">
                    <div>
                        <dt class="text-fg-muted">Created</dt>
                        <dd class="font-medium text-fg">{{ $ticket->created_at->format('M d, Y \a\t g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-fg-muted">Category</dt>
                        <dd class="font-medium text-fg">{{ ucfirst(str_replace('_', ' ', $ticket->category)) }}</dd>
                    </div>
                    @if ($ticket->ip_address)
                        <div>
                            <dt class="text-fg-muted">IP Address</dt>
                            <dd class="font-medium text-fg">{{ $ticket->ip_address }}</dd>
                        </div>
                    @endif
                    @if ($ticket->closed_at)
                        <div>
                            <dt class="text-fg-muted">Closed</dt>
                            <dd class="font-medium text-fg">{{ $ticket->closed_at->format('M d, Y \a\t g:i A') }}</dd>
                        </div>
                    @endif
                </dl>
            </x-ds::panel>

            <section class="space-y-3" aria-labelledby="ticket-conversation">
                <h2 id="ticket-conversation" class="text-section font-semibold text-fg">Conversation</h2>

                @foreach ($ticket->replies as $ticketReply)
                    <x-ds::panel body="plain" wire:key="reply-{{ $ticketReply->id }}">
                        <div class="flex gap-3">
                            <x-ds::avatar size="sm" :name="$ticketReply->user?->name" :decorative="true" />
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span class="text-body font-semibold text-fg">{{ $ticketReply->user->name ?? 'Unknown user' }}</span>
                                    @if ($ticketReply->is_admin_reply)
                                        <x-ds::badge tone="accent">Admin</x-ds::badge>
                                    @endif
                                    <span class="text-meta text-fg-muted">{{ $ticketReply->created_at->format('M d, Y \a\t g:i A') }}</span>
                                </div>
                                <div class="mt-1 whitespace-pre-wrap text-body text-fg-body">{{ $ticketReply->message }}</div>
                            </div>
                        </div>
                    </x-ds::panel>
                @endforeach
            </section>

            <x-ds::panel title="Reply to User" body="plain">
                <form wire:submit="submitReply" class="space-y-4">
                    <x-ds::input
                        as="textarea"
                        wire:model="reply"
                        :rows="4"
                        placeholder="Type your reply to the user..."
                        aria-label="Reply to user"
                        :error="$errors->first('reply')"
                    />
                    <x-ds::button type="submit" wire:loading.attr="disabled" wire:target="submitReply">
                        <span wire:loading.remove wire:target="submitReply">Send Reply</span>
                        <span wire:loading wire:target="submitReply">Sending...</span>
                    </x-ds::button>
                </form>
            </x-ds::panel>
        </div>

        <div>
            <x-ds::panel body="plain">
                <div class="space-y-4">
                    <x-ds::input as="select" label="Status" wire:model.live="newStatus" wire:change="updateStatus">
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </x-ds::input>

                    <x-ds::input as="select" label="Priority" wire:model.live="newPriority" wire:change="updatePriority">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </x-ds::input>
                </div>
            </x-ds::panel>
        </div>
    </div>
</div>
