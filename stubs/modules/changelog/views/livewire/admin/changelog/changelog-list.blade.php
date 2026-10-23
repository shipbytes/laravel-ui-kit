<div class="space-y-6">
    <x-ui-kit::page-header title="Changelog" subtitle="Manage changelog entries visible to users.">
        <x-slot:actions>
            <x-ds::button :href="route('admin.changelog.create')" wire:navigate>
                <x-ds::icon name="plus" size="4" />
                New Entry
            </x-ds::button>
        </x-slot:actions>
    </x-ui-kit::page-header>

    @if (session('success'))
        <x-ds::alert tone="success">{{ session('success') }}</x-ds::alert>
    @endif

    <div class="grid max-w-2xl grid-cols-1 gap-3 sm:grid-cols-2">
        <x-ds::input
            type="search"
            icon="magnifying-glass"
            wire:model.live.debounce.300ms="search"
            placeholder="Search by title..."
            aria-label="Search entries"
        />
        <x-ds::input as="select" wire:model.live="categoryFilter" aria-label="Category">
            <option value="all">All Categories</option>
            <option value="feature">New Feature</option>
            <option value="improvement">Improvement</option>
            <option value="fix">Bug Fix</option>
            <option value="other">Other</option>
        </x-ds::input>
    </div>

    <x-ds::table :columns="['Title', 'Category', 'Status', 'Date', ['label' => '', 'align' => 'right']]">
        @forelse ($entries as $entry)
            <x-ds::table-row wire:key="entry-{{ $entry->id }}">
                <x-ds::table-cell><span class="font-medium text-fg">{{ $entry->title }}</span></x-ds::table-cell>
                <x-ds::table-cell :nowrap="true">
                    <x-ds::badge :tone="$entry->categoryTone()">{{ $entry->category_label }}</x-ds::badge>
                </x-ds::table-cell>
                <x-ds::table-cell :nowrap="true">
                    <x-ds::badge :tone="$entry->is_published ? 'success' : 'neutral'">{{ $entry->is_published ? 'Published' : 'Draft' }}</x-ds::badge>
                </x-ds::table-cell>
                <x-ds::table-cell :nowrap="true">{{ $entry->published_at->format('M d, Y') }}</x-ds::table-cell>
                <x-ds::table-cell align="right" :nowrap="true">
                    <x-ds::button
                        variant="ghost"
                        size="sm"
                        :href="route('admin.changelog.edit', $entry->ulid)"
                        wire:navigate
                        aria-label="Edit {{ $entry->title }}"
                    >Edit</x-ds::button>
                    <x-ds::button
                        variant="ghost"
                        size="sm"
                        tone="danger"
                        wire:click="deleteEntry({{ $entry->id }})"
                        wire:confirm="Are you sure you want to delete this changelog entry?"
                        aria-label="Delete {{ $entry->title }}"
                    >Delete</x-ds::button>
                </x-ds::table-cell>
            </x-ds::table-row>
        @empty
            <tr>
                <td colspan="5">
                    @if ($search === '' && $categoryFilter === 'all')
                        <x-ds::empty-state title="No changelog entries yet" description="Create your first one!" :bare="true">
                            <x-slot:action>
                                <x-ds::button size="sm" :href="route('admin.changelog.create')" wire:navigate>New Entry</x-ds::button>
                            </x-slot:action>
                        </x-ds::empty-state>
                    @else
                        <x-ds::empty-state title="No changelog entries found" description="Try a different search or category." :bare="true" />
                    @endif
                </td>
            </tr>
        @endforelse
    </x-ds::table>

    {{ $entries->links() }}
</div>
