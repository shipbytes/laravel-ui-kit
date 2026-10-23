<?php

namespace Shipbytes\UiKit\Tests\Feature\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Shipbytes\UiKit\Tests\InstalledKitTestCase;

class PasswordResetTest extends InstalledKitTestCase
{
    public function test_a_reset_link_is_requested(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        Livewire::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendPasswordResetLink')
            ->assertHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            // The mailed link must reach the kit's page, not a Fortify route.
            $url = $notification->toMail($user)->actionUrl;

            return str_contains($url, '/reset-password/'.$notification->token);
        });
    }

    public function test_the_password_is_reset_with_a_valid_token(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        Livewire::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendPasswordResetLink');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->get('/reset-password/'.$notification->token.'?email='.urlencode($user->email))->assertOk();

            // email is #[Locked]: it arrives on the mailed link's query string.
            Livewire::withQueryParams(['email' => $user->email])
                ->test('pages.auth.reset-password', ['token' => $notification->token])
                ->set('password', 'a-brand-new-password')
                ->set('password_confirmation', 'a-brand-new-password')
                ->call('resetPassword')
                ->assertHasNoErrors()
                ->assertRedirect('/login');

            return true;
        });

        $this->assertTrue(Hash::check('a-brand-new-password', $user->fresh()->password));
    }

    public function test_a_bad_token_resets_nothing(): void
    {
        $user = $this->makeUser();

        Livewire::withQueryParams(['email' => $user->email])
            ->test('pages.auth.reset-password', ['token' => 'not-a-real-token'])
            ->set('password', 'a-brand-new-password')
            ->set('password_confirmation', 'a-brand-new-password')
            ->call('resetPassword')
            ->assertHasErrors(['email']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
