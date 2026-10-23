<div class="mx-auto max-w-xl space-y-6">
    <x-ui-kit::page-header title="Contact us" subtitle="Send us a message and we'll reply by email." />

    @if ($submitted)
        <x-ds::panel body="plain">
            <div role="status" class="space-y-4 text-center">
                <div class="flex justify-center text-success">
                    <x-ds::icon name="check-circle" size="10" />
                </div>
                <div>
                    <h2 class="text-heading font-semibold text-fg">Thanks, your message is on its way</h2>
                    <p class="mt-1 text-body text-fg-muted">We've emailed you a copy, and we'll reply to {{ $email }}.</p>
                </div>
                <x-ds::button variant="ghost" wire:click="sendAnother">Send another</x-ds::button>
            </div>
        </x-ds::panel>
    @else
        <x-ds::panel body="plain">
            <form wire:submit="submit" class="space-y-5">
                @error('limit')
                    <x-ds::alert tone="danger">{{ $message }}</x-ds::alert>
                @enderror

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-ds::input
                        label="Name"
                        wire:model="name"
                        autocomplete="name"
                        maxlength="255"
                        :error="$errors->first('name')"
                    />
                    <x-ds::input
                        type="email"
                        label="Email"
                        wire:model="email"
                        autocomplete="email"
                        maxlength="255"
                        :error="$errors->first('email')"
                    />
                </div>

                <x-ds::input as="select" label="Subject" wire:model="subject" :error="$errors->first('subject')">
                    <option value="">Choose a subject</option>
                    @foreach ($subjects as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </x-ds::input>

                <x-ds::input
                    as="textarea"
                    label="Message"
                    wire:model="message"
                    :rows="6"
                    maxlength="5000"
                    :error="$errors->first('message')"
                />

                <x-ui-kit::honeypot />

                <div class="flex justify-end">
                    <x-ds::button type="submit" wire:loading.attr="disabled" wire:target="submit">
                        <span wire:loading.remove wire:target="submit">Send message</span>
                        <span wire:loading wire:target="submit">Sending...</span>
                    </x-ds::button>
                </div>
            </form>
        </x-ds::panel>
    @endif
</div>
