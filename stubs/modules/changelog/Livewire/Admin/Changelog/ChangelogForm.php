<?php

namespace App\Livewire\Admin\Changelog;

use App\Models\ChangelogEntry;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Mews\Purifier\Facades\Purifier;

class ChangelogForm extends Component
{
    public ?ChangelogEntry $entry = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string|min:10')]
    public string $body = '';

    #[Validate('required|in:feature,improvement,fix,other')]
    public string $category = 'feature';

    #[Validate('required|date')]
    public string $published_at = '';

    #[Validate('boolean')]
    public bool $is_published = false;

    public function mount(?string $ulid = null): void
    {
        if ($ulid) {
            $this->entry = ChangelogEntry::where('ulid', $ulid)->firstOrFail();
            $this->title = $this->entry->title;
            $this->body = $this->entry->body;
            $this->category = $this->entry->category;
            $this->published_at = $this->entry->published_at->format('Y-m-d');
            $this->is_published = $this->entry->is_published;
        } else {
            $this->published_at = now()->format('Y-m-d');
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'body' => Purifier::clean($this->body, [
                'HTML.Allowed' => 'p,br,strong,b,em,i,u,ul,ol,li,a[href],h3,h4',
                'AutoFormat.RemoveEmpty' => true,
            ]),
            'category' => $this->category,
            'published_at' => $this->published_at,
            'is_published' => $this->is_published,
        ];

        if ($this->entry) {
            $this->entry->update($data);
            session()->flash('success', 'Changelog entry updated.');
        } else {
            ChangelogEntry::create($data);
            session()->flash('success', 'Changelog entry created.');
        }

        $this->redirect(route('admin.changelog.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.changelog.changelog-form')
            ->layout('layouts.admin-sidebar');
    }
}
