<?php

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Features;
use Livewire\Volt\Component;

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
        <h2 class="text-base/7 font-semibold text-zinc-950 dark:text-zinc-50">{{ __('Two-Factor Authentication') }}</h2>
        <p class="mt-1 text-sm/6 text-zinc-500 dark:text-zinc-400">{{ __('Add additional security to your account using two-factor authentication.') }}</p>
    </header>

    <div class="mt-6 space-y-6">
        @if($this->twoFactorEnabled())
            <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 p-4 ring-1 ring-emerald-500/20">
                <h3 class="text-sm/6 font-medium text-emerald-800 dark:text-emerald-200">{{ __('Two-factor authentication is enabled.') }}</h3>
                <p class="mt-1 text-sm/6 text-emerald-700 dark:text-emerald-300">{{ __('You will be asked for a code from your authenticator app when signing in.') }}</p>
            </div>

            @if($showingRecoveryCodes)
                <div>
                    <p class="text-sm/6 text-zinc-600 dark:text-zinc-300">{{ __('Store these recovery codes in a secure password manager. Each can be used once to sign in if you lose your device.') }}</p>
                    <div class="mt-4 rounded-lg bg-zinc-50 dark:bg-zinc-800 p-4 ring-1 ring-zinc-950/5 dark:ring-white/10">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($recoveryCodes as $code)
                                <div class="font-mono text-sm/6 text-zinc-700 dark:text-zinc-200">{{ $code }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex flex-col sm:flex-row gap-3">
                @if(! $showingRecoveryCodes)
                    <button wire:click="showRecoveryCodes" type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-white dark:bg-zinc-800 px-4 py-2 text-sm/6 font-semibold text-zinc-950 dark:text-zinc-50 ring-1 ring-zinc-950/10 dark:ring-white/10 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                        {{ __('Show Recovery Codes') }}
                    </button>
                @else
                    <button wire:click="regenerateRecoveryCodes" type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-white dark:bg-zinc-800 px-4 py-2 text-sm/6 font-semibold text-zinc-950 dark:text-zinc-50 ring-1 ring-zinc-950/10 dark:ring-white/10 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                        {{ __('Regenerate Recovery Codes') }}
                    </button>
                @endif
                <button wire:click="disableTwoFactorAuthentication" wire:confirm="{{ __('Disable two-factor authentication?') }}" type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm/6 font-semibold text-white hover:bg-red-500">
                    {{ __('Disable') }}
                </button>
            </div>
        @elseif($this->twoFactorPending())
            <div>
                <p class="text-sm/6 text-zinc-600 dark:text-zinc-300">{{ __('Scan the following QR code using your authenticator app, then enter a generated code to confirm.') }}</p>
                <div class="mt-4 inline-block rounded-lg bg-white p-4 ring-1 ring-zinc-950/5">
                    {!! Auth::user()->twoFactorQrCodeSvg() !!}
                </div>
            </div>

            <form wire:submit="confirmTwoFactorAuthentication" class="flex items-end gap-3">
                <div>
                    <label for="two_factor_confirmation_code" class="block text-sm/6 font-medium text-zinc-950 dark:text-zinc-50 mb-2">{{ __('Code') }}</label>
                    <input type="text" id="two_factor_confirmation_code" wire:model="confirmationCode" inputmode="numeric" autocomplete="one-time-code" placeholder="123456"
                        class="block w-40 rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-zinc-950 dark:bg-indigo-600 px-4 py-2 text-sm/6 font-semibold text-white hover:bg-zinc-800 dark:hover:bg-indigo-500">
                    {{ __('Confirm') }}
                </button>
                <button wire:click="disableTwoFactorAuthentication" type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-white dark:bg-zinc-800 px-4 py-2 text-sm/6 font-semibold text-zinc-950 dark:text-zinc-50 ring-1 ring-zinc-950/10 dark:ring-white/10 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                    {{ __('Cancel') }}
                </button>
            </form>
            @error('confirmationCode') <p class="text-sm/6 text-red-600">{{ $message }}</p> @enderror
            @error('code') <p class="text-sm/6 text-red-600">{{ $message }}</p> @enderror
        @else
            <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 p-4 ring-1 ring-amber-500/20">
                <h3 class="text-sm/6 font-medium text-amber-800 dark:text-amber-200">{{ __('Two-factor authentication is not enabled.') }}</h3>
                <p class="mt-1 text-sm/6 text-amber-700 dark:text-amber-300">{{ __('Enable two-factor authentication to add an extra layer of security.') }}</p>
            </div>
            <button wire:click="enableTwoFactorAuthentication" type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-zinc-950 dark:bg-indigo-600 px-4 py-2 text-sm/6 font-semibold text-white hover:bg-zinc-800 dark:hover:bg-indigo-500">
                {{ __('Enable Two-Factor Authentication') }}
            </button>
        @endif
    </div>
</section>
