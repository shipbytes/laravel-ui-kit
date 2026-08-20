<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Shipbytes\UiKit\Support\UiKit;

new #[Layout('layouts.guest')] class extends Component
{
    public string $code = '';
    public string $recovery_code = '';

    public function mount(): void
    {
        if (! session()->has('ui-kit.login.id')) {
            $this->redirectRoute('login', navigate: true);
        }
    }

    public function login(): void
    {
        $this->ensureIsNotRateLimited();

        $user = Auth::guard('web')->getProvider()->retrieveById(session('ui-kit.login.id'));

        if (! $user) {
            session()->forget(['ui-kit.login.id', 'ui-kit.login.remember']);
            $this->redirectRoute('login', navigate: true);

            return;
        }

        if ($this->code !== '') {
            $valid = app(TwoFactorAuthenticationProvider::class)->verify(
                decrypt($user->two_factor_secret),
                $this->code
            );

            if (! $valid) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'code' => __('The provided two factor authentication code was invalid.'),
                ]);
            }
        } elseif ($this->recovery_code !== '') {
            $codes = json_decode(decrypt($user->two_factor_recovery_codes), true) ?: [];

            if (! in_array($this->recovery_code, $codes, true)) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'recovery_code' => __('The provided recovery code was invalid.'),
                ]);
            }

            $user->replaceRecoveryCode($this->recovery_code);
        } else {
            throw ValidationException::withMessages([
                'code' => __('Please provide a two factor authentication code.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        Auth::login($user, (bool) session('ui-kit.login.remember', false));

        session()->forget(['ui-kit.login.id', 'ui-kit.login.remember']);
        Session::regenerate();

        $this->redirectIntended(default: UiKit::homeUrl(), navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'code' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return 'two-factor:'.session('ui-kit.login.id').'|'.request()->ip();
    }
}; ?>

<div x-data="{ recovery: false }">
    <div class="text-center mb-8">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-900/30">
            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
        </div>
        <h1 class="text-2xl font-boldtext tracking-tight text-gray-950 dark:text-zinc-50">Two-factor authentication</h1>
        <p x-show="!recovery" class="mt-2 text-sm text-gray-500 dark:text-zinc-400 font-booktext">Enter the code from your authenticator app.</p>
        <p x-show="recovery" x-cloak class="mt-2 text-sm text-gray-500 dark:text-zinc-400 font-booktext">Enter one of your emergency recovery codes.</p>
    </div>

    <form wire:submit="login" class="space-y-5">
        <div x-show="!recovery">
            <label for="code" class="block text-sm font-semibold text-gray-950 dark:text-zinc-50 mb-2">Authentication code</label>
            <input wire:model="code" id="code" type="text" inputmode="numeric" autocomplete="one-time-code" placeholder="123456"
                   class="block w-full rounded-xl border-0 px-4 py-3 text-sm text-gray-950 dark:text-zinc-50 dark:bg-zinc-800 ring-1 ring-gray-300 dark:ring-white/10 placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 transition-shadow">
            @error('code') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div x-show="recovery" x-cloak>
            <label for="recovery_code" class="block text-sm font-semibold text-gray-950 dark:text-zinc-50 mb-2">Recovery code</label>
            <input wire:model="recovery_code" id="recovery_code" type="text" autocomplete="one-time-code" placeholder="abcde-12345"
                   class="block w-full rounded-xl border-0 px-4 py-3 text-sm text-gray-950 dark:text-zinc-50 dark:bg-zinc-800 ring-1 ring-gray-300 dark:ring-white/10 placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 transition-shadow">
            @error('recovery_code') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="flex w-full justify-center rounded-full bg-gray-950 dark:bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 dark:hover:bg-indigo-500 transition-all hover:shadow-lg hover:shadow-gray-950/20">
            Verify
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500 dark:text-zinc-400 font-booktext">
        <button type="button" x-show="!recovery" @click="recovery = true; $wire.set('code', '')" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
            Use a recovery code instead
        </button>
        <button type="button" x-show="recovery" x-cloak @click="recovery = false; $wire.set('recovery_code', '')" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
            Use an authentication code instead
        </button>
    </p>
</div>
