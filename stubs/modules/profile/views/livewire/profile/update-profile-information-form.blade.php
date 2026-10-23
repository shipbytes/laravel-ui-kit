<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public $avatar = null;

    // Bumped after a save or cancel. The drop zone keys on it, so Livewire
    // replaces it and its file list (Alpine state) starts empty again rather
    // than still listing the photo that was just saved.
    public int $avatarUpload = 0;

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

        // Derive the extension from the file's actual MIME type — never from
        // the client-supplied filename, which could smuggle a .php suffix
        // onto the public disk.
        $extension = $this->avatar->extension() ?: 'jpg';
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

        // forceFill, not update(): avatar_path is not in a stock User's
        // $fillable, so update() dropped it silently and no photo ever stuck.
        $user->forceFill(['avatar_path' => $path])->save();

        $this->reset('avatar');
        $this->avatarUpload++;
        $this->dispatch('avatar-updated');
        $this->dispatch('profile-updated', name: $user->name);
    }

    public function cancelAvatar(): void
    {
        $this->reset('avatar');
        $this->avatarUpload++;
    }

    public function deleteAvatar(): void
    {
        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }

        $this->dispatch('avatar-updated');
    }

    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: \Shipbytes\UiKit\Support\UiKit::homeUrl());

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <h2 class="text-heading font-semibold text-fg">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-body text-fg-muted">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    @php
        // The pending upload's preview when there is one, else the stored photo,
        // else nothing (ds::avatar falls back to the initials).
        $avatarUrl = match (true) {
            $avatar instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile && $avatar->isPreviewable() => $avatar->temporaryUrl(),
            (bool) auth()->user()->avatar_path => \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->avatar_path),
            default => null,
        };
    @endphp

    <div class="mt-6 flex items-start gap-4">
        <x-ds::avatar size="lg" :name="auth()->user()->name" :src="$avatarUrl" />

        <div class="min-w-0 flex-1">
            {{-- preview off: the avatar beside it already shows the pending photo. --}}
            <div wire:key="avatar-upload-{{ $avatarUpload }}">
                <x-ds::file-upload name="avatar" label="Profile photo" accept="image/jpeg,image/png,image/webp"
                    :maxSize="2 * 1024 * 1024" :preview="false" wire:model="avatar"
                    :error="$errors->first('avatar')" />
            </div>

            @if ($avatar || auth()->user()->avatar_path)
                <div class="mt-3 flex items-center gap-2">
                    @if ($avatar)
                        <x-ds::button size="sm" wire:click="updateAvatar">Save photo</x-ds::button>
                        <x-ds::button variant="ghost" size="sm" wire:click="cancelAvatar">Cancel</x-ds::button>
                    @else
                        <x-ds::button variant="ghost" size="sm" tone="danger" wire:click="deleteAvatar" wire:confirm="Remove your profile photo?">Remove</x-ds::button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        <x-ds::input type="text" id="name" label="{{ __('Name') }}" wire:model="name"
            required autofocus autocomplete="name"
            :error="$errors->first('name')" />

        <div>
            <x-ds::input type="email" id="email" label="{{ __('Email') }}" wire:model="email"
                required autocomplete="username"
                :error="$errors->first('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="mt-3 space-y-3">
                    <p class="text-body text-fg-muted">
                        {{ __('Your email address is unverified.') }}
                        <x-ds::button variant="ghost" size="sm" wire:click.prevent="sendVerification">
                            {{ __('Click here to re-send the verification email.') }}
                        </x-ds::button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <x-ds::alert tone="success">{{ __('A new verification link has been sent.') }}</x-ds::alert>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-ds::button type="submit">{{ __('Save') }}</x-ds::button>
            <x-action-message on="profile-updated">{{ __('Saved.') }}</x-action-message>
        </div>
    </form>
</section>
