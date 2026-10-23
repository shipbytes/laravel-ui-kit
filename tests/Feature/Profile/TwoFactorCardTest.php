<?php

namespace Shipbytes\UiKit\Tests\Feature\Profile;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Livewire;
use Shipbytes\UiKit\Tests\InstalledKitTestCase;

/**
 * The installed config/fortify.php enables two-factor with confirmPassword,
 * so the card's actions want a password confirmed within auth.password_timeout.
 */
class TwoFactorCardTest extends InstalledKitTestCase
{
    public function test_the_installed_config_asks_for_a_confirmed_password(): void
    {
        $this->assertTrue(config('fortify-options.two-factor-authentication.confirmPassword'));
    }

    public function test_enable_without_a_recent_confirmation_goes_to_the_confirm_page(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        Livewire::test('profile.two-factor-authentication-form')
            ->call('enableTwoFactorAuthentication')
            ->assertRedirect('/confirm-password');

        $this->assertNull($user->fresh()->two_factor_secret, 'nothing may change before the password is confirmed');
        $this->assertSame(url('/profile'), session('url.intended'), 'the confirm page must bring the user back to the profile');
    }

    public function test_a_stale_confirmation_counts_as_none(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);
        session(['auth.password_confirmed_at' => time() - config('auth.password_timeout', 10800) - 1]);

        Livewire::test('profile.two-factor-authentication-form')
            ->call('enableTwoFactorAuthentication')
            ->assertRedirect('/confirm-password');

        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_enable_with_a_recent_confirmation_proceeds(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);
        session(['auth.password_confirmed_at' => time()]);

        Livewire::test('profile.two-factor-authentication-form')
            ->call('enableTwoFactorAuthentication')
            ->assertNoRedirect();

        $this->assertNotNull($user->fresh()->two_factor_secret);
    }

    public function test_disable_and_regenerate_are_gated_too(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);
        app(EnableTwoFactorAuthentication::class)($user);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $codes = $user->fresh()->recoveryCodes();

        Livewire::test('profile.two-factor-authentication-form')
            ->call('regenerateRecoveryCodes')
            ->assertRedirect('/confirm-password');

        Livewire::test('profile.two-factor-authentication-form')
            ->call('disableTwoFactorAuthentication')
            ->assertRedirect('/confirm-password');

        $this->assertSame($codes, $user->fresh()->recoveryCodes());
        $this->assertNotNull($user->fresh()->two_factor_secret);

        session(['auth.password_confirmed_at' => time()]);

        Livewire::test('profile.two-factor-authentication-form')
            ->call('regenerateRecoveryCodes')
            ->assertNoRedirect()
            ->assertSet('showingRecoveryCodes', true);

        $this->assertNotSame($codes, $user->fresh()->recoveryCodes());

        Livewire::test('profile.two-factor-authentication-form')
            ->call('disableTwoFactorAuthentication')
            ->assertNoRedirect();

        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_with_the_option_off_nothing_asks_for_a_password(): void
    {
        config(['fortify-options.two-factor-authentication.confirmPassword' => false]);
        $user = $this->makeUser();
        $this->actingAs($user);

        Livewire::test('profile.two-factor-authentication-form')
            ->call('enableTwoFactorAuthentication')
            ->assertNoRedirect();

        $this->assertNotNull(Auth::user()->fresh()->two_factor_secret);
    }
}
