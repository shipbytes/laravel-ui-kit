<div class="max-w-2xl space-y-6">
    <div>
        <x-ds::button variant="ghost" size="sm" :href="route('support.index')" wire:navigate>
            <x-ds::icon name="arrow-left" size="4" />
            All tickets
        </x-ds::button>
    </div>

    <x-ui-kit::page-header title="New ticket" subtitle="Tell us what happened. We'll answer here and by email." />

    <x-ds::panel body="plain">
        <form wire:submit="submit" class="space-y-5">
            @error('limit')
                <x-ds::alert tone="danger">{{ $message }}</x-ds::alert>
            @enderror

            <x-ds::input
                label="Subject"
                wire:model="subject"
                maxlength="255"
                :error="$errors->first('subject')"
            />

            <x-ds::input as="select" label="Category" wire:model="category" :error="$errors->first('category')">
                <option value="">Choose a category</option>
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ds::input>

            <x-ds::input
                as="textarea"
                label="Message"
                wire:model="message"
                :rows="7"
                maxlength="5000"
                help="At least 20 characters. What you did, what you expected, and what happened instead."
                :error="$errors->first('message')"
            />

            <x-ui-kit::honeypot />

            <div class="flex flex-wrap items-center justify-end gap-3">
                <x-ds::button variant="ghost" :href="route('support.index')" wire:navigate>Cancel</x-ds::button>
                <x-ds::button type="submit" wire:loading.attr="disabled" wire:target="submit">
                    <span wire:loading.remove wire:target="submit">Open ticket</span>
                    <span wire:loading wire:target="submit">Sending...</span>
                </x-ds::button>
            </div>
        </form>
    </x-ds::panel>
</div>
