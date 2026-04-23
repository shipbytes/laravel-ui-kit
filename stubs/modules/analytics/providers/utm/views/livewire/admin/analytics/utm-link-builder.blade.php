<div>
    <div class="mb-8">
        <h1 class="text-2xl/8 font-semibold text-zinc-950 dark:text-zinc-50 sm:text-xl/8">UTM Link Builder</h1>
        <p class="mt-2 text-sm/6 text-zinc-500 dark:text-zinc-400">Craft and store tagged campaign URLs.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/30 dark:border-green-900 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="createLink" class="mb-10 grid gap-4 sm:grid-cols-2 max-w-3xl">
        @foreach([
            ['linkName', 'Link name', 'e.g. Launch campaign — Twitter'],
            ['linkBaseUrl', 'Base URL', 'https://example.com'],
            ['linkSource', 'utm_source *', 'twitter'],
            ['linkMedium', 'utm_medium', 'social'],
            ['linkCampaign', 'utm_campaign', 'spring-launch'],
            ['linkTerm', 'utm_term', ''],
            ['linkContent', 'utm_content', ''],
        ] as [$prop, $label, $placeholder])
            <label class="block">
                <span class="block text-xs font-medium text-zinc-700 dark:text-zinc-200 mb-1">{{ $label }}</span>
                <input
                    wire:model="{{ $prop }}"
                    type="text"
                    placeholder="{{ $placeholder }}"
                    class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                @error($prop) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </label>
        @endforeach

        <div class="sm:col-span-2">
            <button
                type="submit"
                class="inline-flex items-center gap-x-2 rounded-lg bg-zinc-950 dark:bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-zinc-800 dark:hover:bg-indigo-500"
            >
                Save link
            </button>
        </div>
    </form>

    <div class="overflow-hidden rounded-lg border border-zinc-950/10 dark:border-white/10">
        <table class="w-full divide-y divide-zinc-950/5 dark:divide-white/5 table-fixed">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Full URL</th>
                    <th class="w-28 px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-950/5 dark:divide-white/5">
                @forelse($links as $link)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-zinc-950 dark:text-zinc-50">{{ $link->name }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">by {{ $link->creator->name ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-700 dark:text-zinc-200 break-all">{{ $link->full_url }}</td>
                        <td class="px-4 py-3 text-right">
                            <button
                                wire:click="deleteLink({{ $link->id }})"
                                wire:confirm="Delete this link?"
                                class="text-xs font-medium text-red-600 hover:text-red-700"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            No UTM links yet. Build one above.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $links->links() }}</div>
</div>
