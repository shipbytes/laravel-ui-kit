<div>
    <div class="mb-8">
        <h1 class="text-2xl/8 font-semibold text-zinc-950 dark:text-zinc-50 sm:text-xl/8">Contact Messages</h1>
        <p class="mt-2 text-sm/6 text-zinc-500 dark:text-zinc-400">View and manage contact form submissions.</p>

        <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-x-8 gap-y-6">
            <div>
                <div class="text-sm/6 font-medium text-zinc-500 dark:text-zinc-400">Total</div>
                <div class="mt-2 text-3xl/8 font-semibold text-zinc-950 dark:text-zinc-50">{{ $stats['total'] }}</div>
            </div>
            <div>
                <div class="text-sm/6 font-medium text-zinc-500 dark:text-zinc-400">Unread</div>
                <div class="mt-2 text-3xl/8 font-semibold text-zinc-950 dark:text-zinc-50">{{ $stats['unread'] }}</div>
            </div>
            <div>
                <div class="text-sm/6 font-medium text-zinc-500 dark:text-zinc-400">Replied</div>
                <div class="mt-2 text-3xl/8 font-semibold text-zinc-950 dark:text-zinc-50">{{ $stats['replied'] }}</div>
            </div>
            <div>
                <div class="text-sm/6 font-medium text-zinc-500 dark:text-zinc-400">Archived</div>
                <div class="mt-2 text-3xl/8 font-semibold text-zinc-950 dark:text-zinc-50">{{ $stats['archived'] }}</div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/30 dark:border-green-900 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:border-red-900 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl">
        <input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="Search by name, email or subject..."
            class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
        <select wire:model.live="statusFilter" class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="all">All Messages</option>
            <option value="unread">Unread</option>
            <option value="read">Read</option>
            <option value="replied">Replied</option>
            <option value="archived">Archived</option>
        </select>
    </div>

    @if(count($selected) > 0)
        <div class="mb-4 flex items-center gap-3 rounded-lg border border-indigo-200 bg-indigo-50 dark:bg-indigo-900/30 dark:border-indigo-900 px-4 py-3">
            <span class="text-sm font-medium text-indigo-700 dark:text-indigo-200">{{ count($selected) }} {{ str('message')->plural(count($selected)) }} selected</span>
            <span class="text-indigo-300">|</span>
            <button wire:click="markSelectedAs('read')" class="text-sm font-medium text-indigo-700 dark:text-indigo-200 hover:text-indigo-900">Mark Read</button>
            <button wire:click="markSelectedAs('unread')" class="text-sm font-medium text-indigo-700 dark:text-indigo-200 hover:text-indigo-900">Mark Unread</button>
            <button wire:click="archiveSelected" class="text-sm font-medium text-indigo-700 dark:text-indigo-200 hover:text-indigo-900">Archive</button>
            <button
                wire:click="deleteSelected"
                wire:confirm="Are you sure you want to delete {{ count($selected) }} selected messages?"
                class="text-sm font-medium text-red-600 hover:text-red-800"
            >Delete</button>
        </div>
    @endif

    <ul class="divide-y divide-zinc-950/10 dark:divide-white/10">
        <li class="py-3 px-1">
            <label class="flex items-center gap-4">
                <input type="checkbox" wire:model.live="selectAll" class="size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Select all</span>
            </label>
        </li>

        @forelse($submissions as $submission)
            <li wire:key="submission-{{ $submission->id }}">
                <div class="flex items-center gap-4 py-4 px-1 hover:bg-zinc-950/[2.5%] dark:hover:bg-white/[2.5%] transition-colors">
                    <input
                        type="checkbox"
                        wire:model.live="selected"
                        value="{{ $submission->id }}"
                        class="size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 shrink-0"
                    >

                    <button wire:click="openModal({{ $submission->id }})" class="flex flex-1 items-center gap-4 text-left min-w-0">
                        <div class="w-2 shrink-0">
                            @if($submission->status === 'unread')
                                <div class="size-2 rounded-full bg-indigo-600"></div>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="{{ $submission->status === 'unread' ? 'font-semibold' : 'font-medium' }} text-sm text-zinc-950 dark:text-zinc-50 truncate">{{ $submission->name }}</span>
                                <span class="text-xs text-zinc-400 truncate hidden sm:inline">&lt;{{ $submission->email }}&gt;</span>
                                @if($submission->status === 'replied')
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 shrink-0">Replied</span>
                                @elseif($submission->status === 'unread')
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-600/20 shrink-0">Unread</span>
                                @endif
                                @if($submission->archived_at)
                                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-500 ring-1 ring-inset ring-zinc-500/20 shrink-0">Archived</span>
                                @endif
                            </div>
                            <div class="mt-0.5 text-sm text-zinc-700 dark:text-zinc-300 truncate {{ $submission->status === 'unread' ? 'font-medium' : '' }}">{{ $submission->subject }}</div>
                            <div class="mt-0.5 text-xs text-zinc-400 truncate">{{ Str::limit($submission->message, 80) }}</div>
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            <span class="text-xs text-zinc-400 hidden sm:block">{{ $submission->created_at->diffForHumans() }}</span>
                            <svg class="size-5 text-zinc-400 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </button>
                </div>
            </li>
        @empty
            <li class="py-16 text-center">
                <h3 class="text-base/6 font-semibold text-zinc-950 dark:text-zinc-50 mb-1">No messages found</h3>
                <p class="text-sm/6 text-zinc-500 dark:text-zinc-400">Try adjusting your filters.</p>
            </li>
        @endforelse
    </ul>

    @if($submissions->hasPages())
        <div class="mt-6">
            {{ $submissions->links() }}
        </div>
    @endif

    @if($showModal && $viewingSubmission)
        <div
            class="fixed inset-0 z-50 overflow-y-auto"
            x-data
            x-on:keydown.escape.window="$wire.closeModal()"
        >
            <div class="fixed inset-0 bg-black/40" wire:click="closeModal"></div>

            <div class="relative flex min-h-full items-start justify-center p-4 pt-16">
                <div class="relative w-full max-w-2xl rounded-xl bg-white dark:bg-zinc-900 shadow-xl">
                    <div class="flex items-start justify-between border-b border-zinc-950/10 dark:border-white/10 px-6 py-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-lg font-semibold text-zinc-950 dark:text-zinc-50">{{ $viewingSubmission->subject }}</h2>
                                @if($viewingSubmission->status === 'replied')
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Replied</span>
                                @elseif($viewingSubmission->status === 'unread')
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-600/20">Unread</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 ring-1 ring-inset ring-zinc-500/20">Read</span>
                                @endif
                            </div>
                            <div class="mt-1 flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400 flex-wrap">
                                <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $viewingSubmission->name }}</span>
                                <span>&lt;{{ $viewingSubmission->email }}&gt;</span>
                                <span>&middot;</span>
                                <span>{{ $viewingSubmission->created_at->format('M d, Y \a\t g:i A') }}</span>
                                @if($viewingSubmission->ip_address)
                                    <span>&middot;</span>
                                    <span>IP: {{ $viewingSubmission->ip_address }}</span>
                                @endif
                            </div>
                        </div>
                        <button wire:click="closeModal" class="ml-4 shrink-0 rounded-lg p-1.5 text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="px-6 py-4">
                        <div class="text-sm text-zinc-700 dark:text-zinc-200 whitespace-pre-wrap leading-relaxed">{{ $viewingSubmission->message }}</div>
                    </div>

                    <div class="border-t border-zinc-950/5 dark:border-white/5 px-6 py-4">
                        @if($viewingSubmission->replied_at)
                            <div class="rounded-lg border border-green-200 bg-green-50/50 p-4 dark:bg-green-900/20 dark:border-green-900">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-medium text-green-700 dark:text-green-200">Your Reply</span>
                                    <span class="text-xs text-green-600 dark:text-green-300">{{ $viewingSubmission->replied_at->format('M d, Y \a\t g:i A') }}</span>
                                </div>
                                <div class="text-sm text-green-800 dark:text-green-100 whitespace-pre-wrap">{{ $viewingSubmission->reply }}</div>
                            </div>
                        @else
                            <label for="reply-text" class="block text-xs font-medium text-zinc-700 dark:text-zinc-200 mb-1.5">Reply to {{ $viewingSubmission->name }}</label>
                            <textarea
                                wire:model="replyText"
                                id="reply-text"
                                rows="4"
                                placeholder="Type your reply... (min 10 characters)"
                                class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 placeholder:text-zinc-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-y"
                            ></textarea>
                            @error('replyText')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <div class="mt-2 flex justify-end">
                                <button
                                    wire:click="submitReply({{ $viewingSubmission->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="submitReply"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-950 dark:bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-zinc-700 dark:hover:bg-indigo-500 disabled:opacity-50"
                                >
                                    <span wire:loading.remove wire:target="submitReply">Send Reply</span>
                                    <span wire:loading wire:target="submitReply">Sending...</span>
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-between border-t border-zinc-950/10 dark:border-white/10 px-6 py-4">
                        <div class="flex items-center gap-2">
                            @if($senderIsUser && $this->supportTicketsInstalled())
                                <button
                                    wire:click="copyToTicket({{ $viewingSubmission->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="copyToTicket"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-1.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800 disabled:opacity-50"
                                >
                                    <span wire:loading.remove wire:target="copyToTicket">Copy to Ticket</span>
                                    <span wire:loading wire:target="copyToTicket">Copying...</span>
                                </button>
                            @endif

                            <button
                                wire:click="toggleReadStatus({{ $viewingSubmission->id }})"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-1.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800"
                            >
                                {{ $viewingSubmission->status === 'unread' ? 'Mark Read' : 'Mark Unread' }}
                            </button>

                            @if($viewingSubmission->archived_at)
                                <button
                                    wire:click="unarchiveSubmission({{ $viewingSubmission->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-1.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800"
                                >
                                    Unarchive
                                </button>
                            @else
                                <button
                                    wire:click="archiveSubmission({{ $viewingSubmission->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-1.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800"
                                >
                                    Archive
                                </button>
                            @endif
                        </div>

                        <button
                            wire:click="deleteSubmission({{ $viewingSubmission->id }})"
                            wire:confirm="Are you sure you want to delete this contact submission?"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-transparent px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
