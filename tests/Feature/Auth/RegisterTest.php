<?php

namespace Shipbytes\UiKit\Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Shipbytes\UiKit\Tests\InstalledKitTestCase;

class RegisterTest extends InstalledKitTestCase
{
    public function test_a_visitor_registers_and_the_session_is_regenerated(): void
    {
        $before = session()->getId();

        $this->register(['name' => "Siân O'Neil-Smith Jr.", 'email' => 'new@example.com'])
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($before, session()->getId(), 'registering must regenerate the session');
    }

    public function test_a_disposable_address_is_rejected_when_the_block_is_on(): void
    {
        config(['ui-kit.features.disposable_email_block' => true]);

        $this->register(['email' => 'someone@mailinator.com'])
            ->assertHasErrors(['email' => 'indisposable']);

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_a_disposable_address_is_allowed_when_the_block_is_off(): void
    {
        config(['ui-kit.features.disposable_email_block' => false]);

        $this->register(['email' => 'someone@mailinator.com'])->assertHasNoErrors();

        $this->assertSame(1, User::count());
    }

    public function test_a_name_with_symbols_is_rejected_in_plain_words(): void
    {
        $component = $this->register(['name' => '<script>alert(1)</script>'])
            ->assertHasErrors(['name' => 'regex']);

        $this->assertSame('Your name can only contain letters, spaces, apostrophes, hyphens and full stops.', $component->errors()->first('name'));
    }

    public function test_a_failed_submit_does_not_use_up_the_hourly_limit(): void
    {
        $key = 'register:127.0.0.1';

        foreach (range(1, 12) as $attempt) {
            $this->register(['password_confirmation' => 'does-not-match'])->assertHasErrors(['password']);
        }

        $this->assertSame(0, RateLimiter::attempts($key), 'validation failures must not count');

        $this->register()->assertHasNoErrors();

        $this->assertSame(1, RateLimiter::attempts($key));
    }

    public function test_ten_registrations_an_hour_lock_the_address_out(): void
    {
        RateLimiter::increment('register:127.0.0.1', 3600, 10);

        $this->register()->assertHasErrors(['email']);

        $this->assertSame(0, User::count());
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function register(array $fields = []): Testable
    {
        $fields += [
            'name' => 'New Person',
            'email' => 'new'.uniqid().'@example.com',
            'password' => 'a-long-password',
            'password_confirmation' => $fields['password'] ?? 'a-long-password',
        ];

        $component = Livewire::test('pages.auth.register');

        foreach ($fields as $field => $value) {
            $component->set($field, $value);
        }

        return $component->call('register');
    }
}
