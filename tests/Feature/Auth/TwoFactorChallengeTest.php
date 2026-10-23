<?php

namespace Shipbytes\UiKit\Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Shipbytes\UiKit\Tests\InstalledKitTestCase;

class TwoFactorChallengeTest extends InstalledKitTestCase
{
    private string $secret;

    protected function tearDown(): void
    {
        Fortify::encryptUsing(null);

        parent::tearDown();
    }

    public function test_the_kit_login_stages_the_challenge(): void
    {
        $user = $this->twoFactorUser();

        Livewire::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertRedirect('/two-factor-challenge');

        $this->assertGuest();
        $this->assertSame($user->id, session('ui-kit.login.id'));
    }

    public function test_a_totp_code_completes_sign_in(): void
    {
        $user = $this->twoFactorUser();
        session(['ui-kit.login.id' => $user->id, 'ui-kit.login.remember' => false]);

        Livewire::test('pages.auth.two-factor-challenge')
            ->set('code', $this->currentCode())
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('ui-kit.login.id'));
    }

    public function test_a_recovery_code_completes_sign_in_once(): void
    {
        $user = $this->twoFactorUser();
        session(['ui-kit.login.id' => $user->id]);

        Livewire::test('pages.auth.two-factor-challenge')
            ->set('recovery_code', 'first-recovery-code')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertNotContains('first-recovery-code', $user->fresh()->recoveryCodes(), 'a used recovery code must be replaced');
    }

    public function test_a_wrong_code_fails_and_five_lock_the_challenge(): void
    {
        $user = $this->twoFactorUser();
        session(['ui-kit.login.id' => $user->id]);

        foreach (range(1, 5) as $attempt) {
            Livewire::test('pages.auth.two-factor-challenge')
                ->set('code', '000000')
                ->call('login')
                ->assertHasErrors(['code']);
        }

        $component = Livewire::test('pages.auth.two-factor-challenge')
            ->set('code', $this->currentCode())
            ->call('login')
            ->assertHasErrors(['code']);

        $this->assertStringContainsString('Too many', $component->errors()->first('code'));
        $this->assertGuest();
    }

    public function test_fortifys_session_keys_are_accepted(): void
    {
        $user = $this->twoFactorUser();
        session(['login.id' => $user->id, 'login.remember' => true]);

        Livewire::test('pages.auth.two-factor-challenge')
            ->assertNoRedirect()
            ->set('code', $this->currentCode())
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('login.id'));
    }

    public function test_fortifys_own_login_endpoint_reaches_the_challenge_and_finishes(): void
    {
        $user = $this->twoFactorUser();

        // Fortify's POST /login sends a two-factor user to route two-factor.login.
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.login'));

        $this->get(route('two-factor.login'))->assertRedirect('/two-factor-challenge');

        $this->get('/two-factor-challenge')
            ->assertOk()
            ->assertSee('Two-factor authentication');

        Livewire::test('pages.auth.two-factor-challenge')
            ->set('code', $this->currentCode())
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Route::has('two-factor.challenge'), 'the kit route name upgraded apps link to stays');
    }

    public function test_secrets_written_with_fortifys_own_encrypter_are_read_back(): void
    {
        Fortify::encryptUsing(new Encrypter(random_bytes(32), 'aes-256-cbc'));

        $user = $this->twoFactorUser();
        session(['ui-kit.login.id' => $user->id]);

        Livewire::test('pages.auth.two-factor-challenge')
            ->set('code', $this->currentCode())
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_two_factor_columns_never_reach_json(): void
    {
        $array = $this->twoFactorUser()->fresh()->toArray();

        $this->assertArrayNotHasKey('two_factor_secret', $array);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $array);
        $this->assertArrayNotHasKey('password', $array);
    }

    private function twoFactorUser(): User
    {
        $this->secret = app(TwoFactorAuthenticationProvider::class)->generateSecretKey();
        $encrypter = Fortify::currentEncrypter();

        $user = $this->makeUser();
        $user->forceFill([
            'two_factor_secret' => $encrypter->encrypt($this->secret),
            'two_factor_recovery_codes' => $encrypter->encrypt(json_encode(['first-recovery-code', 'second-recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $user;
    }

    private function currentCode(): string
    {
        return (new Google2FA)->getCurrentOtp($this->secret);
    }
}
