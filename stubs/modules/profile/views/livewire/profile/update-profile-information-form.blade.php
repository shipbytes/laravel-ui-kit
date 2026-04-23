<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public $avatar = null;

    public function mount(): void
    {
        $this->name = Auth::user()->name ?? '';
        $this->email = Auth::user()->email;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    public function updateAvatar(): void
    {
        $this->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $extension = $this->avatar->getClientOriginalExtension() ?: 'jpg';
        $path = 'avatars/'.$user->getKey().'.'.$extension;

        if (class_exists(\Intervention\Image\ImageManager::class)) {
            $manager = \Intervention\Image\ImageManager::gd();
            $image = $manager->read($this->avatar->getRealPath());
            $image->cover(200, 200);
            Storage::disk('public')->put('avatars/'.$user->getKey().'.jpg', $image->toJpeg(85));
            $path = 'avatars/'.$user->getKey().'.jpg';
        } else {
            Storage::disk('public')->putFileAs('avatars', $this->avatar, $user->getKey().'.'.$extension);
        }

        $user->update(['avatar_path' => $path]);

        $this->reset('avatar');
        $this->dispatch('avatar-updated');
        $this->dispatch('profile-updated', name: $user->name);
    }

    public function deleteAvatar(): void
    {
        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        $this->dispatch('avatar-updated');
    }

    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <h2 class="text-base/7 font-semibold text-zinc-950 dark:text-zinc-50">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm/6 text-zinc-500 dark:text-zinc-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <div class="mt-6 flex items-center gap-4">
        <div class="relative group">
            @if($avatar && $avatar instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile && $avatar->isPreviewable())
                <img src="{{ $avatar->temporaryUrl() }}" alt="" class="size-16 rounded-lg object-cover">
            @elseif(auth()->user()->avatar_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->avatar_path) }}" alt="" class="size-16 rounded-lg object-cover">
            @else
                <span class="size-16 rounded-lg bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-xl font-medium text-zinc-600 dark:text-zinc-200">
                    {{ strtoupper(mb_substr(auth()->user()->name ?? '?', 0, 1)) }}
                </span>
            @endif

            <label for="avatar-upload" class="absolute inset-0 flex items-center justify-center rounded-lg bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                <svg class="size-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </label>
            <input id="avatar-upload" type="file" wire:model="avatar" accept="image/jpeg,image/png,image/webp" class="hidden">
        </div>
        <div>
            <p class="text-sm font-medium text-zinc-950 dark:text-zinc-50">{{ auth()->user()->name }}</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ auth()->user()->email }}</p>

            <div class="mt-1.5 flex items-center gap-2">
                @if($avatar)
                    <button type="button" wire:click="updateAvatar" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">Save photo</button>
                    <button type="button" wire:click="$set('avatar', null)" class="text-xs font-medium text-zinc-500 hover:text-zinc-700">Cancel</button>
                @else
                    <label for="avatar-upload" class="text-xs font-medium text-indigo-600 hover:text-indigo-500 cursor-pointer">
                        {{ auth()->user()->avatar_path ? 'Change photo' : 'Upload photo' }}
                    </label>
                    @if(auth()->user()->avatar_path)
                        <button type="button" wire:click="deleteAvatar" wire:confirm="Remove your profile photo?" class="text-xs font-medium text-red-600 hover:text-red-500">Remove</button>
                    @endif
                @endif
            </div>
        </div>
    </div>
    @error('avatar') <p class="mt-2 text-sm/6 text-red-600">{{ $message }}</p> @enderror

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        <div>
            <label for="name" class="block text-sm/6 font-medium text-zinc-950 dark:text-zinc-50 mb-2">{{ __('Name') }}</label>
            <input type="text" id="name" wire:model="name" required autofocus autocomplete="name"
                class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('name') <p class="mt-2 text-sm/6 text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm/6 font-medium text-zinc-950 dark:text-zinc-50 mb-2">{{ __('Email') }}</label>
            <input type="email" id="email" wire:model="email" required autocomplete="username"
                class="block w-full rounded-lg border border-zinc-950/10 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-950 dark:text-zinc-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('email') <p class="mt-2 text-sm/6 text-red-600">{{ $message }}</p> @enderror

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="mt-3">
                    <p class="text-sm/6 text-zinc-600 dark:text-zinc-300">
                        {{ __('Your email address is unverified.') }}
                        <button wire:click.prevent="sendVerification" class="text-sm/6 text-zinc-950 dark:text-zinc-50 underline hover:text-zinc-700 focus:outline-none">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm/6 font-medium text-emerald-600">{{ __('A new verification link has been sent.') }}</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-zinc-950 dark:bg-indigo-600 px-4 py-2 text-sm/6 font-semibold text-white hover:bg-zinc-800 dark:hover:bg-indigo-500">
                {{ __('Save') }}
            </button>
            <x-action-message class="text-sm/6 text-zinc-500 dark:text-zinc-400" on="profile-updated">{{ __('Saved.') }}</x-action-message>
        </div>
    </form>
</section>
