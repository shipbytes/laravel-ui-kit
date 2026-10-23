<?php

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Features;
use Livewire\Component;

new class extends Component
{
    public bool $showingRecoveryCodes = false;
    public array $recoveryCodes = [];
    public string $confirmationCode = '';

    public function enableTwoFactorAuthentication(EnableTwoFactorAuthentication $enable): void
    {
        $enable(Auth::user());

        if (! $this->confirmationRequired()) {
            $this->showRecoveryCodes();
        }
    }

    public function confirmTwoFactorAuthentication(ConfirmTwoFactorAuthentication $confirm): void
    {
        $this->validate([
            'confirmationCode' => ['required', 'string'],
        ]);

        // Throws a validation exception on an invalid code.
        $confirm(Auth::user(), $this->confirmationCode);

        $this->confirmationCode = '';
        $this->showRecoveryCodes();
        $this->dispatch('two-factor-enabled');
    }

    public function showRecoveryCodes(): void
    {
        $this->recoveryCodes = json_decode(decrypt(Auth::user()->two_factor_recovery_codes), true) ?: [];
        $this->showingRecoveryCodes = true;
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generate): void
    {
        $generate(Auth::user());
        $this->showRecoveryCodes();
        $this->dispatch('recovery-codes-regenerated');
    }

    public function disableTwoFactorAuthentication(DisableTwoFactorAuthentication $disable): void
    {
        $disable(Auth::user());

        $this->showingRecoveryCodes = false;
        $this->recoveryCodes = [];
        $this->confirmationCode = '';

        $this->dispatch('two-factor-disabled');
    }

    public function twoFactorEnabled(): bool
    {
        return Auth::user()->hasEnabledTwoFactorAuthentication();
    }

    public function twoFactorPending(): bool
    {
        return ! is_null(Auth::user()->two_factor_secret)
            && ! $this->twoFactorEnabled();
    }

    public function confirmationRequired(): bool
    {
        return Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
    }
}; ?>

<section>
    <header>
        <h2 class="text-heading font-semibold text-fg">{{ __('Two-Factor Authentication') }}</h2>
        <p class="mt-1 text-body text-fg-muted">{{ __('Add additional security to your account using two-factor authentication.') }}</p>
    </header>

    <div class="mt-6 space-y-6">
        @if($this->twoFactorEnabled())
            <x-ds::alert tone="success" title="{{ __('Two-factor authentication is enabled.') }}">
                {{ __('You will be asked for a code from your authenticator app when signing in.') }}
            </x-ds::alert>

            @if($showingRecoveryCodes)
                <div>
                    <p class="text-body text-fg-body">{{ __('Store these recovery codes in a secure password manager. Each can be used once to sign in if you lose your device.') }}</p>
                    <div class="mt-4 rounded-control border border-border bg-surface-subtle p-4">
                        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                            @foreach($recoveryCodes as $code)
                                <div class="font-mono text-body text-fg-body">{{ $code }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex flex-col gap-3 sm:flex-row">
                @if(! $showingRecoveryCodes)
                    <x-ds::button variant="secondary" wire:click="showRecoveryCodes">{{ __('Show Recovery Codes') }}</x-ds::button>
                @else
                    <x-ds::button variant="secondary" wire:click="regenerateRecoveryCodes">{{ __('Regenerate Recovery Codes') }}</x-ds::button>
                @endif
                <x-ds::button variant="danger" wire:click="disableTwoFactorAuthentication" wire:confirm="{{ __('Disable two-factor authentication?') }}">
                    {{ __('Disable') }}
                </x-ds::button>
            </div>
        @elseif($this->twoFactorPending())
            <div>
                <p class="text-body text-fg-body">{{ __('Scan the following QR code using your authenticator app, then enter a generated code to confirm.') }}</p>
                {{-- bg-white whatever the theme: a QR code has to be dark on light to scan. --}}
                <div class="mt-4 inline-block rounded-control border border-border bg-white p-4">
                    {!! Auth::user()->twoFactorQrCodeSvg() !!}
                </div>
            </div>

            {{-- Fortify's confirm action reports a wrong code under `code`, the
                 component's own required rule under `confirmationCode`. --}}
            <form wire:submit="confirmTwoFactorAuthentication" class="flex items-end gap-3">
                <x-ds::input type="text" id="two_factor_confirmation_code" label="{{ __('Code') }}" class="w-40"
                    wire:model="confirmationCode" inputmode="numeric" autocomplete="one-time-code" placeholder="123456"
                    :error="$errors->first('confirmationCode') ?: $errors->first('code')" />
                <x-ds::button type="submit">{{ __('Confirm') }}</x-ds::button>
                <x-ds::button variant="ghost" wire:click="disableTwoFactorAuthentication">{{ __('Cancel') }}</x-ds::button>
            </form>
        @else
            <x-ds::alert tone="warning" title="{{ __('Two-factor authentication is not enabled.') }}">
                {{ __('Enable two-factor authentication to add an extra layer of security.') }}
            </x-ds::alert>
            <x-ds::button wire:click="enableTwoFactorAuthentication">{{ __('Enable Two-Factor Authentication') }}</x-ds::button>
        @endif
    </div>
</section>
