<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public string $token = '';
    #[Locked]
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->string('email');
    }

    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status != Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        Session::flash('status', __($status));
        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div>
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-success-tint text-on-success-tint">
            <x-ds::icon name="key" size="6" />
        </div>
        <h1 class="text-title font-semibold text-fg">{{ config('ui-kit.copy.reset_password.heading') }}</h1>
        <p class="mt-2 text-body text-fg-muted">{{ config('ui-kit.copy.reset_password.subheading') }}</p>
    </div>

    <form wire:submit="resetPassword" class="space-y-5">
        <x-ds::input wire:model="email" id="email" type="email" name="email" label="Email"
            required readonly autocomplete="username"
            :error="$errors->first('email')" />

        <x-ds::input wire:model="password" id="password" type="password" name="password" label="New password"
            required autocomplete="new-password" placeholder="Enter new password"
            :error="$errors->first('password')" />

        <x-ds::input wire:model="password_confirmation" id="password_confirmation" type="password" name="password_confirmation" label="Confirm new password"
            required autocomplete="new-password" placeholder="Confirm new password"
            :error="$errors->first('password_confirmation')" />

        <x-ds::button type="submit" class="w-full">Reset password</x-ds::button>
    </form>
</div>
