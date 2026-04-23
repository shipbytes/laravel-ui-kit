<?php

namespace App\Livewire\Admin\Analytics;

use App\Models\UtmLink;
use Livewire\Component;
use Livewire\WithPagination;

class UtmLinkBuilder extends Component
{
    use WithPagination;

    public string $linkName = '';

    public string $linkBaseUrl = '';

    public string $linkSource = '';

    public string $linkMedium = '';

    public string $linkCampaign = '';

    public string $linkTerm = '';

    public string $linkContent = '';

    public function mount(): void
    {
        $this->linkBaseUrl = config('app.url');
    }

    public function createLink(): void
    {
        $validated = $this->validate([
            'linkName' => ['required', 'string', 'max:255'],
            'linkBaseUrl' => ['required', 'url', 'max:2048'],
            'linkSource' => ['required', 'string', 'max:255'],
            'linkMedium' => ['nullable', 'string', 'max:255'],
            'linkCampaign' => ['nullable', 'string', 'max:255'],
            'linkTerm' => ['nullable', 'string', 'max:255'],
            'linkContent' => ['nullable', 'string', 'max:255'],
        ]);

        UtmLink::create([
            'name' => $validated['linkName'],
            'base_url' => $validated['linkBaseUrl'],
            'utm_source' => $validated['linkSource'],
            'utm_medium' => $validated['linkMedium'] ?: null,
            'utm_campaign' => $validated['linkCampaign'] ?: null,
            'utm_term' => $validated['linkTerm'] ?: null,
            'utm_content' => $validated['linkContent'] ?: null,
            'created_by' => auth()->id(),
        ]);

        $this->reset(['linkName', 'linkSource', 'linkMedium', 'linkCampaign', 'linkTerm', 'linkContent']);
        $this->linkBaseUrl = config('app.url');

        session()->flash('success', 'UTM link created.');
    }

    public function deleteLink(int $id): void
    {
        UtmLink::where('id', $id)->delete();
        session()->flash('success', 'UTM link deleted.');
    }

    public function render()
    {
        return view('livewire.admin.analytics.utm-link-builder', [
            'links' => UtmLink::with('creator')->latest()->paginate(10),
        ])->layout('layouts.admin-sidebar');
    }
}
