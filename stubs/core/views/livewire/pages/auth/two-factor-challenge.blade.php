<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Livewire\Attributes\Layout;
use Livewire\Component;
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
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-accent-tint text-on-accent-tint">
            <x-ds::icon name="shield-check" size="6" />
        </div>
        <h1 class="text-title font-semibold text-fg">Two-factor authentication</h1>
        <p x-show="!recovery" class="mt-2 text-body text-fg-muted">Enter the code from your authenticator app.</p>
        <p x-show="recovery" x-cloak class="mt-2 text-body text-fg-muted">Enter one of your emergency recovery codes.</p>
    </div>

    <form wire:submit="login" class="space-y-5">
        <div x-show="!recovery">
            <x-ds::input wire:model="code" id="code" type="text" label="Authentication code"
                inputmode="numeric" autocomplete="one-time-code" placeholder="123456"
                :error="$errors->first('code')" />
        </div>

        <div x-show="recovery" x-cloak>
            <x-ds::input wire:model="recovery_code" id="recovery_code" type="text" label="Recovery code"
                autocomplete="one-time-code" placeholder="abcde-12345"
                :error="$errors->first('recovery_code')" />
        </div>

        <x-ds::button type="submit" class="w-full">Verify</x-ds::button>
    </form>

    <div class="mt-6 text-center">
        <x-ds::button variant="ghost" size="sm" x-show="!recovery" @click="recovery = true; $wire.set('code', '')">
            Use a recovery code instead
        </x-ds::button>
        <x-ds::button variant="ghost" size="sm" x-show="recovery" x-cloak @click="recovery = false; $wire.set('recovery_code', '')">
            Use an authentication code instead
        </x-ds::button>
    </div>
</div>
