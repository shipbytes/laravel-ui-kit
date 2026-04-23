<div>
    <div class="max-w-2xl">
        <div class="mb-6">
            <a href="{{ route('admin.changelog.index') }}" wire:navigate class="text-sm/6 font-medium text-zinc-500 dark:text-zinc-400 hover:text-zinc-950 dark:hover:text-zinc-50 flex items-center gap-2">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to changelog
            </a>
        </div>

        <h1 class="text-2xl/8 font-semibold text-zinc-950 dark:text-zinc-50 sm:text-xl/8">
            {{ $entry ? 'Edit Changelog Entry' : 'New Changelog Entry' }}
        </h1>
        <p class="mt-2 text-sm/6 text-zinc-500 dark:text-zinc-400">
            {{ $entry ? 'Update this changelog entry.' : 'Create a new changelog entry to share with users.' }}
        </p>

        <form wire:submit="save" class="mt-8 space-y-6">
            <div>
                <label for="title" class="block text-sm font-medium text-zinc-950 dark:text-zinc-50 mb-2">Title</label>
                <input
                    wire:model="title"
                    type="text"
                    id="title"
                    placeholder="e.g. New Modern Template Available"
                    class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                @error('title') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="category" class="block text-sm font-medium text-zinc-950 dark:text-zinc-50 mb-2">Category</label>
                <select
                    wire:model="category"
                    id="category"
                    class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="feature">New Feature</option>
                    <option value="improvement">Improvement</option>
                    <option value="fix">Bug Fix</option>
                    <option value="other">Other</option>
                </select>
                @error('category') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="body" class="block text-sm font-medium text-zinc-950 dark:text-zinc-50 mb-2">Body</label>
                <p class="text-xs text-zinc-400 mb-2">You can use basic HTML: &lt;p&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;li&gt;, &lt;a href="..."&gt;, &lt;h3&gt;, &lt;h4&gt;</p>
                <textarea
                    wire:model="body"
                    id="body"
                    rows="10"
                    placeholder="Describe the changes..."
                    class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-xs text-zinc-950 dark:text-zinc-50 placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-y font-mono"
                ></textarea>
                @error('body') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="published_at" class="block text-sm font-medium text-zinc-950 dark:text-zinc-50 mb-2">Publish Date</label>
                <input
                    wire:model="published_at"
                    type="date"
                    id="published_at"
                    class="block w-full max-w-xs rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                @error('published_at') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="button"
                    wire:click="$toggle('is_published')"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 {{ $is_published ? 'bg-indigo-600' : 'bg-zinc-200 dark:bg-zinc-700' }}"
                    role="switch"
                    aria-checked="{{ $is_published ? 'true' : 'false' }}"
                >
                    <span class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_published ? 'translate-x-5' : 'translate-x-0' }}"></span>
                </button>
                <label class="text-sm font-medium text-zinc-950 dark:text-zinc-50">
                    {{ $is_published ? 'Published' : 'Draft' }}
                </label>
            </div>

            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center gap-x-2 rounded-lg border border-transparent bg-zinc-950 dark:bg-indigo-600 text-white text-sm font-semibold px-3 py-1.5 hover:bg-zinc-800 dark:hover:bg-indigo-500 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="save">{{ $entry ? 'Update Entry' : 'Create Entry' }}</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
                <a
                    href="{{ route('admin.changelog.index') }}"
                    wire:navigate
                    class="text-sm/6 font-medium text-zinc-500 dark:text-zinc-400 hover:text-zinc-950 dark:hover:text-zinc-50"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
