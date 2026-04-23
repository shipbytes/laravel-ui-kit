<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
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

        $this->redirectIntended(default: route(config('ui-kit.brand.home_route'), absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="text-center mb-8">
        <h1 class="text-2xl font-boldtext tracking-tight text-gray-950">{{ config('ui-kit.copy.register.heading') }}</h1>
        <p class="mt-2 text-sm text-gray-500 font-booktext">{{ config('ui-kit.copy.register.subheading') }}</p>
    </div>

    @include('layouts.partials.social-buttons')

    <form wire:submit="register" class="space-y-5">
        <div>
            <label for="name" class="block text-sm font-semibold text-gray-950 mb-2">Name</label>
            <input wire:model="name" id="name" type="text" name="name" required autofocus autocomplete="name"
                   class="block w-full rounded-xl border-0 px-4 py-3 text-sm text-gray-950 ring-1 ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 transition-shadow"
                   placeholder="Jane Doe">
            @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-semibold text-gray-950 mb-2">Email</label>
            <input wire:model="email" id="email" type="email" name="email" required autocomplete="username"
                   class="block w-full rounded-xl border-0 px-4 py-3 text-sm text-gray-950 ring-1 ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 transition-shadow"
                   placeholder="you@example.com">
            @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-semibold text-gray-950 mb-2">Password</label>
            <input wire:model="password" id="password" type="password" name="password" required autocomplete="new-password"
                   class="block w-full rounded-xl border-0 px-4 py-3 text-sm text-gray-950 ring-1 ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 transition-shadow"
                   placeholder="Create a password">
            @error('password') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-semibold text-gray-950 mb-2">Confirm password</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                   class="block w-full rounded-xl border-0 px-4 py-3 text-sm text-gray-950 ring-1 ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 transition-shadow"
                   placeholder="Confirm your password">
            @error('password_confirmation') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="flex w-full justify-center rounded-full bg-gray-950 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 transition-all hover:shadow-lg hover:shadow-gray-950/20">
            Create account
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500 font-booktext">
        Already have an account?
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">Sign in</a>
    </p>
</div>
