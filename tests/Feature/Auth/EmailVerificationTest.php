<?php

namespace Shipbytes\UiKit\Tests\Feature\Auth;

use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Shipbytes\UiKit\Tests\InstalledKitTestCase;

/**
 * Separate processes: these need a MustVerifyEmail User model, and the other
 * classes load a plain one into the shared process first.
 */
#[RunTestsInSeparateProcesses]
class EmailVerificationTest extends InstalledKitTestCase
{
    protected bool $verifiesEmail = true;

    public function test_a_valid_link_verifies_the_email(): void
    {
        Event::fake([Verified::class]);
        $user = $this->makeUser(['email_verified_at' => null]);

        $this->actingAs($user)
            ->get($this->verificationUrl($user, sha1($user->email)))
            ->assertRedirect('/?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);
    }

    public function test_a_link_with_the_wrong_hash_verifies_nothing(): void
    {
        $user = $this->makeUser(['email_verified_at' => null]);

        $this->actingAs($user)
            ->get($this->verificationUrl($user, sha1('someone-else@example.com')))
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_an_unverified_user_is_sent_to_the_notice(): void
    {
        $user = $this->makeAdmin(['email_verified_at' => null]);

        // The admin area is behind `verified`.
        $this->actingAs($user)->get('/admin')->assertRedirect('/verify-email');

        $this->get('/verify-email')->assertOk()->assertSee('Resend verification email');
    }

    public function test_resending_is_throttled_to_six_a_minute(): void
    {
        Notification::fake();
        $user = $this->makeUser(['email_verified_at' => null]);
        $this->actingAs($user);

        foreach (range(1, 6) as $attempt) {
            Livewire::test('pages.auth.verify-email')
                ->call('sendVerification')
                ->assertSee('A new verification link has been sent');
        }

        Livewire::test('pages.auth.verify-email')
            ->call('sendVerification')
            ->assertSee('Too many requests');

        Notification::assertSentToTimes($user, VerifyEmail::class, 6);
    }

    public function test_the_profile_page_shares_the_resend_limit(): void
    {
        Notification::fake();
        $user = $this->makeUser(['email_verified_at' => null]);
        $this->actingAs($user);

        foreach (range(1, 6) as $attempt) {
            Livewire::test('pages.auth.verify-email')->call('sendVerification');
        }

        Livewire::test('profile.update-profile-information-form')
            ->call('sendVerification')
            ->assertSee('Too many requests');

        Notification::assertSentToTimes($user, VerifyEmail::class, 6);
    }

    private function verificationUrl(object $user, string $hash): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->getKey(),
            'hash' => $hash,
        ]);
    }
}
