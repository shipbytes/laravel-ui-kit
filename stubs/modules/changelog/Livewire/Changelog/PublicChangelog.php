<?php

namespace App\Livewire\Changelog;

use App\Models\ChangelogEntry;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The public changelog: every published entry, newest first, grouped by
 * month, with a category filter that lives in the query string
 * (/changelog?category=fix) so a filtered view can be linked to.
 */
#[Layout('layouts.user-shell')]
class PublicChangelog extends Component
{
    #[Url(except: '')]
    public string $category = '';

    public function render()
    {
        $categories = ChangelogEntry::categories();

        // An unknown category in the URL shows everything rather than nothing.
        $active = array_key_exists($this->category, $categories) ? $this->category : '';

        $entries = ChangelogEntry::published()
            ->when($active !== '', fn ($q) => $q->where('category', $active))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        // Every category's count in one query, for the filter.
        $counts = ChangelogEntry::published()
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->map(fn ($total) => (int) $total);

        return view('livewire.changelog.public-changelog', [
            'grouped' => $entries->groupBy(fn (ChangelogEntry $entry) => $entry->published_at->format('F Y')),
            'categories' => $categories,
            'counts' => $counts,
            'active' => $active,
        ]);
    }
}
