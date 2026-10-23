<?php

// ui-kit:managed — auto-loaded by UiKitServiceProvider. Delete this line to
// opt out of auto-loading and wire this file up yourself.

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Shipbytes\UiKit\Http\Controllers\Auth\VerifyEmailController;

Route::middleware('guest')->group(function () {
    Route::livewire('register', 'pages.auth.register')->name('register');
    Route::livewire('login', 'pages.auth.login')->name('login');
    Route::livewire('forgot-password', 'pages.auth.forgot-password')->name('password.request');
    Route::livewire('reset-password/{token}', 'pages.auth.reset-password')->name('password.reset');

    if (Features::enabled(Features::twoFactorAuthentication())) {
        Route::livewire('two-factor-challenge', 'pages.auth.two-factor-challenge')->name('two-factor.challenge');

        // Fortify's own POST /login sends a two-factor user to this name. The
        // page picks up the login Fortify staged in the session.
        Route::redirect('two-factor-login', '/two-factor-challenge')->name('two-factor.login');
    }
});

Route::middleware('auth')->group(function () {
    Route::livewire('verify-email', 'pages.auth.verify-email')->name('verification.notice');

    // Fortify registers its own verification.verify route when its
    // emailVerification feature is enabled — skip ours in that case so the
    // two never collide (duplicate route names break route:cache).
    if (! Features::enabled(Features::emailVerification())) {
        Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
    }

    Route::livewire('confirm-password', 'pages.auth.confirm-password')->name('password.confirm');
});
