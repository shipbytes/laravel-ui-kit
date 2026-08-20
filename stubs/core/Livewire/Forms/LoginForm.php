<?php

namespace App\Livewire\Forms;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Features;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Validate the credentials and either log the user in or stage a
     * two-factor challenge.
     *
     * Returns true when fully authenticated; false when the user must be
     * sent to the two-factor challenge page (the challenged user is stashed
     * in the session, mirroring Fortify's flow).
     */
    public function authenticate(): bool
    {
        $this->ensureIsNotRateLimited();

        $guard = Auth::guard('web');

        if (! $guard->validate($this->only(['email', 'password']))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        $user = $guard->getLastAttempted();

        RateLimiter::clear($this->throttleKey());

        if ($this->twoFactorChallengeRequired($user)) {
            request()->session()->put([
                'ui-kit.login.id' => $user->getAuthIdentifier(),
                'ui-kit.login.remember' => $this->remember,
            ]);

            return false;
        }

        Auth::login($user, $this->remember);

        return true;
    }

    /**
     * The challenge only applies when Fortify's feature is on AND the user
     * model actually uses TwoFactorAuthenticatable AND this user finished
     * enabling it.
     */
    protected function twoFactorChallengeRequired(object $user): bool
    {
        return Features::enabled(Features::twoFactorAuthentication())
            && method_exists($user, 'hasEnabledTwoFactorAuthentication')
            && $user->hasEnabledTwoFactorAuthentication();
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
