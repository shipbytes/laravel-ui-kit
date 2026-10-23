<div class="space-y-8">
    <x-ui-kit::page-header title="Changelog" subtitle="New features, improvements and fixes, newest first." />

    @if ($counts->sum() > 0)
        {{-- Links, not tabs: each filter is its own URL, so a filtered view can be shared. --}}
        <x-ds::tabs label="Filter by category" :navigation="true">
            <x-ds::tab :href="route('changelog.public')" :active="$active === ''" :count="$counts->sum()" wire:navigate>All</x-ds::tab>
            @foreach ($categories as $key => $label)
                @if (($counts[$key] ?? 0) > 0)
                    <x-ds::tab
                        :href="route('changelog.public', ['category' => $key])"
                        :active="$active === $key"
                        :count="$counts[$key]"
                        wire:navigate
                    >{{ $label }}</x-ds::tab>
                @endif
            @endforeach
        </x-ds::tabs>
    @endif

    @forelse ($grouped as $month => $entries)
        <section class="space-y-4" aria-labelledby="month-{{ $loop->index }}" wire:key="month-{{ $month }}">
            <h2 id="month-{{ $loop->index }}" class="text-section font-semibold text-fg-muted">{{ $month }}</h2>

            @foreach ($entries as $entry)
                <x-ds::panel body="plain" wire:key="entry-{{ $entry->id }}">
                    <article>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <x-ds::badge :tone="$entry->categoryTone()">{{ $entry->category_label }}</x-ds::badge>
                            <time datetime="{{ $entry->published_at->toDateString() }}" class="text-meta text-fg-muted">{{ $entry->published_at->format('M j, Y') }}</time>
                        </div>
                        <h3 class="mt-2 text-heading font-semibold text-fg">{{ $entry->title }}</h3>
                        {{-- Unescaped on purpose: ChangelogForm runs the body through
                             mews/purifier when it saves, so only its allow-list of tags
                             (p, strong, em, lists, links, h3, h4) ever reaches the table.
                             An entry written some other way must be purified the same. --}}
                        <div class="mt-2 space-y-3 text-body text-fg-body [&_a]:text-accent [&_a]:underline [&_h3]:font-semibold [&_h3]:text-fg [&_h4]:font-semibold [&_h4]:text-fg [&_ol]:list-decimal [&_ol]:pl-5 [&_strong]:font-semibold [&_strong]:text-fg [&_ul]:list-disc [&_ul]:pl-5">
                            {!! $entry->body !!}
                        </div>
                    </article>
                </x-ds::panel>
            @endforeach
        </section>
    @empty
        <x-ds::empty-state
            icon="megaphone"
            title="{{ $active === '' ? 'Nothing published yet' : 'Nothing in this category yet' }}"
            description="{{ $active === '' ? 'New features and fixes will be listed here as they ship.' : 'Pick All to see every change.' }}"
        />
    @endforelse
</div>
