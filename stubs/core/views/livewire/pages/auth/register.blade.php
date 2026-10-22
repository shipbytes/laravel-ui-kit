<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Shipbytes\UiKit\Support\UiKit;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
        $throttleKey = 'register:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => RateLimiter::availableIn($throttleKey),
                    'minutes' => ceil(RateLimiter::availableIn($throttleKey) / 60),
                ]),
            ]);
        }

        RateLimiter::hit($throttleKey, 3600);

        $emailRules = ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class];

        if (config('ui-kit.features.disposable_email_block')) {
            $emailRules[] = 'indisposable';
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.indisposable' => 'Please use a permanent email address. Disposable emails are not allowed.',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirectIntended(default: UiKit::homeUrl(), navigate: true);
    }
}; ?>

<div>
    <div class="mb-8 text-center">
        <h1 class="text-title font-semibold text-fg">{{ config('ui-kit.copy.register.heading') }}</h1>
        <p class="mt-2 text-body text-fg-muted">{{ config('ui-kit.copy.register.subheading') }}</p>
    </div>

    <form wire:submit="register" class="space-y-5">
        <x-ds::input wire:model="name" id="name" type="text" name="name" label="Name"
            required autofocus autocomplete="name" placeholder="Jane Doe"
            :error="$errors->first('name')" />

        <x-ds::input wire:model="email" id="email" type="email" name="email" label="Email"
            required autocomplete="username" placeholder="you@example.com"
            :error="$errors->first('email')" />

        <x-ds::input wire:model="password" id="password" type="password" name="password" label="Password"
            required autocomplete="new-password" placeholder="Create a password"
            :error="$errors->first('password')" />

        <x-ds::input wire:model="password_confirmation" id="password_confirmation" type="password" name="password_confirmation" label="Confirm password"
            required autocomplete="new-password" placeholder="Confirm your password"
            :error="$errors->first('password_confirmation')" />

        <x-ds::button type="submit" class="w-full">Create account</x-ds::button>
    </form>

    <p class="mt-6 text-center text-body text-fg-muted">
        Already have an account?
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-accent hover:text-accent-hover">Sign in</a>
    </p>
</div>
