<div>
    <div class="max-w-5xl">
        <div class="mb-6">
            <a href="{{ route('admin.support.index') }}" wire:navigate class="text-sm/6 font-medium text-zinc-500 dark:text-zinc-400 hover:text-zinc-950 dark:hover:text-zinc-50 flex items-center gap-2">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to tickets
            </a>
        </div>

        @if (session()->has('success'))
            <div class="mb-6 rounded-lg bg-green-50 p-4 border border-green-200 dark:bg-green-900/30 dark:border-green-900">
                <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-lg border border-zinc-950/10 dark:border-white/10 p-6">
                    <h1 class="text-xl font-semibold text-zinc-950 dark:text-zinc-50 mb-4">{{ $ticket->subject }}</h1>

                    <div class="flex items-center gap-3 mb-4 pb-4 border-b border-zinc-950/5 dark:border-white/5">
                        <span class="size-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-sm font-medium text-zinc-600 dark:text-zinc-200">
                            {{ strtoupper(mb_substr($ticket->user->name ?? '?', 0, 1)) }}
                        </span>
                        <div>
                            <div class="text-sm font-semibold text-zinc-950 dark:text-zinc-50">{{ $ticket->user->name ?? 'Unknown user' }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $ticket->user->email ?? '' }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400">Created:</span>
                            <span class="text-zinc-950 dark:text-zinc-50 font-medium">{{ $ticket->created_at->format('M d, Y \a\t g:i A') }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400">Category:</span>
                            <span class="text-zinc-950 dark:text-zinc-50 font-medium">{{ ucfirst(str_replace('_', ' ', $ticket->category)) }}</span>
                        </div>
                        @if($ticket->ip_address)
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400">IP Address:</span>
                                <span class="text-zinc-950 dark:text-zinc-50 font-medium">{{ $ticket->ip_address }}</span>
                            </div>
                        @endif
                        @if($ticket->closed_at)
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400">Closed:</span>
                                <span class="text-zinc-950 dark:text-zinc-50 font-medium">{{ $ticket->closed_at->format('M d, Y \a\t g:i A') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-950/10 dark:border-white/10">
                    <div class="p-6">
                        <h2 class="text-base font-semibold text-zinc-950 dark:text-zinc-50 mb-4">Conversation</h2>
                        <div class="space-y-6">
                            @foreach($ticket->replies as $ticketReply)
                                <div class="flex gap-4 {{ $ticketReply->is_admin_reply ? 'bg-indigo-50/50 dark:bg-indigo-900/20 -mx-4 px-4 py-4 rounded-lg' : '' }}">
                                    <div class="shrink-0">
                                        <span class="size-10 rounded-full {{ $ticketReply->is_admin_reply ? 'bg-indigo-200 text-indigo-600' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-200' }} flex items-center justify-center text-sm font-medium">
                                            {{ strtoupper(mb_substr($ticketReply->user->name ?? '?', 0, 1)) }}
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-sm font-semibold text-zinc-950 dark:text-zinc-50">{{ $ticketReply->user->name ?? 'Unknown user' }}</span>
                                            @if($ticketReply->is_admin_reply)
                                                <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-700">
                                                    Admin
                                                </span>
                                            @endif
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $ticketReply->created_at->format('M d, Y \a\t g:i A') }}</span>
                                        </div>
                                        <div class="text-sm/6 text-zinc-950 dark:text-zinc-50 whitespace-pre-wrap">{{ $ticketReply->message }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="border-t border-zinc-950/10 dark:border-white/10 p-6">
                        <h3 class="text-sm font-semibold text-zinc-950 dark:text-zinc-50 mb-3">Reply to User</h3>
                        <form wire:submit="submitReply" class="space-y-4">
                            <textarea
                                wire:model="reply"
                                rows="4"
                                placeholder="Type your reply to the user..."
                                class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
                            ></textarea>
                            @error('reply') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center justify-center gap-x-2 rounded-lg border border-transparent bg-zinc-950 dark:bg-indigo-600 text-white text-sm font-semibold px-3 py-1.5 hover:bg-zinc-800 dark:hover:bg-indigo-500 disabled:opacity-50"
                            >
                                <span wire:loading.remove wire:target="submitReply">Send Reply</span>
                                <span wire:loading wire:target="submitReply">Sending...</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-lg border border-zinc-950/10 dark:border-white/10 p-6">
                    <h3 class="text-sm font-semibold text-zinc-950 dark:text-zinc-50 mb-4">Status</h3>
                    <select
                        wire:model.live="newStatus"
                        wire:change="updateStatus"
                        class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>

                <div class="rounded-lg border border-zinc-950/10 dark:border-white/10 p-6">
                    <h3 class="text-sm font-semibold text-zinc-950 dark:text-zinc-50 mb-4">Priority</h3>
                    <select
                        wire:model.live="newPriority"
                        wire:change="updatePriority"
                        class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
