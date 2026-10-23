<?php

namespace Shipbytes\UiKit\Tests\Feature\Auth;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Shipbytes\UiKit\Tests\InstalledKitTestCase;

class LoginTest extends InstalledKitTestCase
{
    public function test_a_user_signs_in(): void
    {
        $user = $this->makeUser();
        $before = session()->getId();

        Livewire::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($before, session()->getId(), 'signing in must regenerate the session');
    }

    public function test_a_wrong_password_fails_and_fires_the_failed_event(): void
    {
        Event::fake([Failed::class]);
        $user = $this->makeUser();

        Livewire::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['form.email'])
            ->assertNoRedirect();

        $this->assertGuest();
        Event::assertDispatched(Failed::class, fn (Failed $e) => $e->guard === 'web' && $e->user?->is($user));
    }

    public function test_five_failures_lock_the_email_out(): void
    {
        Event::fake([Lockout::class]);
        $user = $this->makeUser();

        foreach (range(1, 5) as $attempt) {
            Livewire::test('pages.auth.login')
                ->set('form.email', $user->email)
                ->set('form.password', 'wrong-password')
                ->call('login')
                ->assertHasErrors(['form.email']);
        }

        // The right password no longer helps until the limiter decays.
        $component = Livewire::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors(['form.email']);

        $this->assertStringContainsString('Too many login attempts', $component->errors()->first('form.email'));
        $this->assertGuest();
        Event::assertDispatched(Lockout::class);
    }

    public function test_a_user_logs_out(): void
    {
        $this->actingAs($this->makeUser())
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
