<?php

namespace Shipbytes\UiKit\Tests\Feature;

use App\Models\User;
use Shipbytes\UiKit\Tests\InstalledKitTestCase;

/**
 * After an install, Fortify keeps only what the kit does not do itself: its
 * login POST, logout, password confirmation and the two-factor endpoints.
 * The five it would 500 on are gone, and the kit's own pages answer instead.
 */
class FortifyEndpointsTest extends InstalledKitTestCase
{
    public function test_the_endpoints_the_kit_does_not_own_are_gone(): void
    {
        $guest = [
            ['POST', '/register', ['name' => 'Bot', 'email' => 'bot@example.com', 'password' => 'secret-password', 'password_confirmation' => 'secret-password']],
            ['POST', '/forgot-password', ['email' => 'someone@example.com']],
            ['POST', '/reset-password', ['token' => 'x', 'email' => 'someone@example.com', 'password' => 'secret-password', 'password_confirmation' => 'secret-password']],
        ];

        foreach ($guest as [$method, $uri, $data]) {
            $status = $this->call($method, $uri, $data)->status();
            $this->assertContains($status, [404, 405], "{$method} {$uri} answered {$status}");
        }

        $this->actingAs($this->makeUser());

        foreach ([
            ['PUT', '/user/profile-information', ['name' => 'Changed', 'email' => 'changed@example.com']],
            ['PUT', '/user/password', ['current_password' => 'password', 'password' => 'secret-password', 'password_confirmation' => 'secret-password']],
        ] as [$method, $uri, $data]) {
            $status = $this->call($method, $uri, $data)->status();
            $this->assertContains($status, [404, 405], "{$method} {$uri} answered {$status}");
        }

        $this->assertSame(0, User::where('email', 'bot@example.com')->count(), 'nothing registered behind the kit\'s back');
    }

    public function test_the_kits_own_pages_still_answer(): void
    {
        foreach (['/login', '/register', '/forgot-password', '/reset-password/a-token?email=a%40example.com'] as $page) {
            $this->assertRendersCleanly($this->get($page)->assertOk());
        }
    }

    public function test_fortifys_login_still_signs_in_a_user_without_two_factor(): void
    {
        $user = $this->makeUser();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }
}
