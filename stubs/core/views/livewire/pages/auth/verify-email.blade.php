<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
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
    <div class="text-center mb-8">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 dark:bg-amber-900/30">
            <svg class="w-6 h-6 text-amber-600 dark:text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
            </svg>
        </div>
        <h1 class="text-2xl font-boldtext tracking-tight text-gray-950 dark:text-zinc-50">{{ config('ui-kit.copy.verify_email.heading') }}</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-zinc-400 font-booktext leading-relaxed">{{ config('ui-kit.copy.verify_email.subheading') }}</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-6 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 ring-1 ring-emerald-100 dark:ring-emerald-500/20 px-4 py-3">
            <p class="text-sm text-emerald-700 dark:text-emerald-200 font-booktext">A new verification link has been sent to your email address.</p>
        </div>
    @endif

    <div class="space-y-3">
        <button wire:click="sendVerification" class="flex w-full justify-center rounded-full bg-gray-950 dark:bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 dark:hover:bg-indigo-500 transition-all hover:shadow-lg hover:shadow-gray-950/20">
            Resend verification email
        </button>

        <button wire:click="logout" type="button" class="flex w-full justify-center rounded-full px-6 py-3 text-sm font-semibold text-gray-700 dark:text-zinc-200 ring-1 ring-gray-300 dark:ring-white/10 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-all">
            Log out
        </button>
    </div>
</div>
