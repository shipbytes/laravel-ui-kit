<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Shipbytes\UiKit\Support\UiKit;

new #[Layout('layouts.guest')] class extends Component
{
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: UiKit::homeUrl(), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();
        Session::flash('status', 'verification-link-sent');
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();
        Session::invalidate();
        Session::regenerateToken();
        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-warning-tint text-on-warning-tint">
            <x-ds::icon name="envelope" size="6" />
        </div>
        <h1 class="text-title font-semibold text-fg">{{ config('ui-kit.copy.verify_email.heading') }}</h1>
        <p class="mt-2 text-body text-fg-muted">{{ config('ui-kit.copy.verify_email.subheading') }}</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <x-ds::alert tone="success" class="mb-6">A new verification link has been sent to your email address.</x-ds::alert>
    @endif

    <div class="space-y-3">
        <x-ds::button wire:click="sendVerification" class="w-full">Resend verification email</x-ds::button>
        <x-ds::button variant="ghost" wire:click="logout" class="w-full">Log out</x-ds::button>
    </div>
</div>
