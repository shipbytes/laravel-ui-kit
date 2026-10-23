<?php

namespace Shipbytes\UiKit\Tests\Feature\Auth;

use Livewire\Livewire;
use Shipbytes\UiKit\Tests\InstalledKitTestCase;

class ConfirmPasswordTest extends InstalledKitTestCase
{
    public function test_the_right_password_is_confirmed(): void
    {
        $this->actingAs($this->makeUser());

        Livewire::test('pages.auth.confirm-password')
            ->set('password', 'password')
            ->call('confirmPassword')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertEqualsWithDelta(time(), session('auth.password_confirmed_at'), 5);
    }

    public function test_a_wrong_password_is_not_confirmed(): void
    {
        $this->actingAs($this->makeUser());

        Livewire::test('pages.auth.confirm-password')
            ->set('password', 'wrong-password')
            ->call('confirmPassword')
            ->assertHasErrors(['password'])
            ->assertNoRedirect();

        $this->assertNull(session('auth.password_confirmed_at'));
    }

    public function test_it_returns_to_the_intended_page(): void
    {
        $this->actingAs($this->makeUser());
        session()->put('url.intended', url('/profile'));

        Livewire::test('pages.auth.confirm-password')
            ->set('password', 'password')
            ->call('confirmPassword')
            ->assertRedirect('/profile');
    }
}
