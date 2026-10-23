<div class="max-w-2xl space-y-6">
    @php
        // Text, not markup. The input escapes `help` when it prints it, so the tag
        // list is decoded once here and reaches the page escaped exactly once.
        $bodyHelp = html_entity_decode('You can use basic HTML: &lt;p&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;li&gt;, &lt;a href="..."&gt;, &lt;h3&gt;, &lt;h4&gt;');
    @endphp

    <div>
        <x-ds::button variant="ghost" size="sm" :href="route('admin.changelog.index')" wire:navigate>
            <x-ds::icon name="arrow-left" size="4" />
            Back to changelog
        </x-ds::button>
    </div>

    <x-ui-kit::page-header
        :title="$entry ? 'Edit Changelog Entry' : 'New Changelog Entry'"
        :subtitle="$entry ? 'Update this changelog entry.' : 'Create a new changelog entry to share with users.'"
    />

    <form wire:submit="save" class="space-y-6">
        <x-ds::input
            wire:model="title"
            id="title"
            label="Title"
            placeholder="e.g. New Modern Template Available"
            :error="$errors->first('title')"
        />

        <x-ds::input as="select" wire:model="category" id="category" label="Category" :error="$errors->first('category')">
            <option value="feature">New Feature</option>
            <option value="improvement">Improvement</option>
            <option value="fix">Bug Fix</option>
            <option value="other">Other</option>
        </x-ds::input>

        <x-ds::input
            as="textarea"
            wire:model="body"
            id="body"
            label="Body"
            :rows="10"
            placeholder="Describe the changes..."
            :help="$bodyHelp"
            :error="$errors->first('body')"
        />

        <x-ds::input
            type="date"
            wire:model="published_at"
            id="published_at"
            label="Publish Date"
            class="max-w-xs"
            :error="$errors->first('published_at')"
        />

        {{-- No `name`, so no hidden "0" input: wire:model owns the value. --}}
        <x-ds::switch
            wire:model="is_published"
            label="Published"
            help="Takes effect when you save. Drafts are not shown to users."
            :checked="$is_published"
            :submitUnchecked="false"
        />

        <div class="flex items-center gap-3">
            <x-ds::button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $entry ? 'Update Entry' : 'Create Entry' }}</span>
                <span wire:loading wire:target="save">Saving...</span>
            </x-ds::button>
            <x-ds::button variant="ghost" :href="route('admin.changelog.index')" wire:navigate>Cancel</x-ds::button>
        </div>
    </form>
</div>
