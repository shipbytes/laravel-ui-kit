<?php

// ui-kit:managed — auto-loaded by UiKitServiceProvider. Delete this line to
// opt out of auto-loading and wire this file up yourself.

use Shipbytes\UiKit\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('register', 'pages.auth.register')->name('register');
    Volt::route('login', 'pages.auth.login')->name('login');
    Volt::route('forgot-password', 'pages.auth.forgot-password')->name('password.request');
    Volt::route('reset-password/{token}', 'pages.auth.reset-password')->name('password.reset');

    if (Features::enabled(Features::twoFactorAuthentication())) {
        Volt::route('two-factor-challenge', 'pages.auth.two-factor-challenge')->name('two-factor.challenge');
    }
});

Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'pages.auth.verify-email')->name('verification.notice');

    // Fortify registers its own verification.verify route when its
    // emailVerification feature is enabled — skip ours in that case so the
    // two never collide (duplicate route names break route:cache).
    if (! Features::enabled(Features::emailVerification())) {
        Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
    }

    Volt::route('confirm-password', 'pages.auth.confirm-password')->name('password.confirm');
});
