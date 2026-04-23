<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route(config('ui-kit.brand.home_route'), absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="text-center mb-8">
        <h1 class="text-2xl font-boldtext tracking-tight text-gray-950">{{ config('ui-kit.copy.login.heading') }}</h1>
        <p class="mt-2 text-sm text-gray-500 font-booktext">{{ config('ui-kit.copy.login.subheading') }}</p>
    </div>

    @include('layouts.partials.social-buttons')

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <div>
            <label for="email" class="block text-sm font-semibold text-gray-950 mb-2">Email</label>
            <input wire:model="form.email" id="email" type="email" name="email" required autofocus autocomplete="username"
                   class="block w-full rounded-xl border-0 px-4 py-3 text-sm text-gray-950 ring-1 ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 transition-shadow"
                   placeholder="you@example.com">
            @error('form.email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-semibold text-gray-950 mb-2">Password</label>
            <input wire:model="form.password" id="password" type="password" name="password" required autocomplete="current-password"
                   class="block w-full rounded-xl border-0 px-4 py-3 text-sm text-gray-950 ring-1 ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 transition-shadow"
                   placeholder="Enter your password">
            @error('form.password') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between">
            <label for="remember" class="flex items-center gap-2 cursor-pointer">
                <input wire:model="form.remember" id="remember" type="checkbox" name="remember"
                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                <span class="text-sm text-gray-600 font-booktext">Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" wire:navigate class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">
                    Forgot password?
                </a>
            @endif
        </div>

        <button type="submit" class="flex w-full justify-center rounded-full bg-gray-950 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 transition-all hover:shadow-lg hover:shadow-gray-950/20">
            Sign in
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500 font-booktext">
        Don't have an account?
        <a href="{{ route('register') }}" wire:navigate class="font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">Create one free</a>
    </p>
</div>
