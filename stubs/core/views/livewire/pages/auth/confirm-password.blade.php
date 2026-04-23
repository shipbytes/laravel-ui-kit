<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);
        $this->redirectIntended(default: route(config('ui-kit.brand.home_route'), absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="text-center mb-8">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-violet-50">
            <svg class="w-6 h-6 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
        </div>
        <h1 class="text-2xl font-boldtext tracking-tight text-gray-950">{{ config('ui-kit.copy.confirm_password.heading') }}</h1>
        <p class="mt-2 text-sm text-gray-500 font-booktext leading-relaxed">{{ config('ui-kit.copy.confirm_password.subheading') }}</p>
    </div>

    <form wire:submit="confirmPassword" class="space-y-5">
        <div>
            <label for="password" class="block text-sm font-semibold text-gray-950 mb-2">Password</label>
            <input wire:model="password" id="password" type="password" name="password" required autocomplete="current-password"
                   class="block w-full rounded-xl border-0 px-4 py-3 text-sm text-gray-950 ring-1 ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 transition-shadow"
                   placeholder="Enter your password">
            @error('password') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="flex w-full justify-center rounded-full bg-gray-950 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 transition-all hover:shadow-lg hover:shadow-gray-950/20">
            Confirm
        </button>
    </form>
</div>
