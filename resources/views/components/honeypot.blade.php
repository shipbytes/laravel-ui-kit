{{-- ui-kit::honeypot: the field WithSpamProtection checks. Visually hidden
     and hidden from assistive technology, out of the tab order and never
     autofilled, so only a bot that fills every field it finds puts anything
     in it. Not display:none: some bots skip fields they can tell are hidden. --}}
<div class="sr-only" aria-hidden="true">
    <x-ds::input label="Website" name="website" wire:model="website" tabindex="-1" autocomplete="off" />
</div>
