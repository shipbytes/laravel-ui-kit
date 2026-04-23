<?php

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Volt\Component;

new class extends Component
{
    public bool $showingQrCode = false;
    public bool $showingRecoveryCodes = false;
    public array $recoveryCodes = [];

    public function enableTwoFactorAuthentication(): void
    {
        app(EnableTwoFactorAuthentication::class)(Auth::user());

        $this->showingQrCode = true;
        $this->showingRecoveryCodes = true;
        $this->recoveryCodes = json_decode(decrypt(Auth::user()->two_factor_recovery_codes), true);

        $this->dispatch('two-factor-enabled');
    }

    public function showRecoveryCodes(): void
    {
        $this->showingRecoveryCodes = true;
        $this->recoveryCodes = json_decode(decrypt(Auth::user()->two_factor_recovery_codes), true);
    }

    public function regenerateRecoveryCodes(): void
    {
        $user = Auth::user();
        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode(collect(range(1, 8))->map(function () {
                return \Illuminate\Support\Str::random(10).'-'.\Illuminate\Support\Str::random(10);
            })->all())),
        ])->save();

        $this->showingRecoveryCodes = true;
        $this->recoveryCodes = json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true);

        $this->dispatch('recovery-codes-regenerated');
    }

    public function disableTwoFactorAuthentication(): void
    {
        app(DisableTwoFactorAuthentication::class)(Auth::user());

        $this->showingQrCode = false;
        $this->showingRecoveryCodes = false;

        $this->dispatch('two-factor-disabled');
    }

    public function twoFactorEnabled(): bool
    {
        return ! is_null(Auth::user()->two_factor_secret);
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
            </div>

            @if($showingQrCode)
                <div>
                    <p class="text-sm/6 text-zinc-600 dark:text-zinc-300">{{ __('Scan the following QR code using your authenticator app.') }}</p>
                    <div class="mt-4 inline-block rounded-lg bg-white p-4 ring-1 ring-zinc-950/5">
                        {!! Auth::user()->twoFactorQrCodeSvg() !!}
                    </div>
                </div>
            @endif

            @if($showingRecoveryCodes)
                <div>
                    <p class="text-sm/6 text-zinc-600 dark:text-zinc-300">{{ __('Store these recovery codes in a secure password manager.') }}</p>
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
                <button wire:click="disableTwoFactorAuthentication" type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm/6 font-semibold text-white hover:bg-red-500">
                    {{ __('Disable') }}
                </button>
            </div>
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
