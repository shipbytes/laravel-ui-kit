<?php

namespace Shipbytes\UiKit\Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\FortifyServiceProvider;
use Livewire\Livewire;
use Shipbytes\UiKit\Tests\Concerns\AssertsRendersCleanly;
use Shipbytes\UiKit\Tests\Concerns\InstallsKitIntoSkeleton;
use Shipbytes\UiKit\Tests\TestCase;

/**
 * Boots a real core install in the Testbench skeleton and renders the
 * published pages over HTTP — the closest thing to clicking through a
 * fresh app that a package test can do.
 */
class AuthPagesRenderTest extends TestCase
{
    use AssertsRendersCleanly;
    use InstallsKitIntoSkeleton;

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            FortifyServiceProvider::class,
        ]);
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The profile module rides along: it has no composer dependency, and
        // its page is the other half of the screens built on ds:: forms.
        $this->installKit('profile');
    }

    protected function tearDown(): void
    {
        $this->cleanSkeleton();

        parent::tearDown();
    }

    public function test_login_page_renders(): void
    {
        $response = $this->get('/login')
            ->assertOk()
            ->assertSee('Welcome back')
            ->assertSee('Sign in');

        $this->assertRendersCleanly($response);
    }

    public function test_auth_pages_are_livewire_page_routes(): void
    {
        $this->assertSame(
            'pages.auth.login',
            Route::getRoutes()->getByName('login')?->getAction('livewire_component'),
            'routes/auth.php must register its pages through Route::livewire()'
        );
    }

    public function test_register_page_renders(): void
    {
        $response = $this->get('/register')
            ->assertOk()
            ->assertSee('Create your account')
            ->assertSee('Create account');

        $this->assertRendersCleanly($response);
    }

    public function test_forgot_password_page_renders(): void
    {
        $response = $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Send reset link');

        $this->assertRendersCleanly($response);
    }

    public function test_reset_password_page_renders(): void
    {
        $response = $this->get('/reset-password/some-token?email=someone%40example.com')
            ->assertOk()
            ->assertSee('Reset password')
            ->assertSee('someone@example.com');

        $this->assertRendersCleanly($response);
    }

    public function test_verify_email_page_renders_for_an_unverified_user(): void
    {
        $user = User::forceCreate([
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'password' => bcrypt('secret'),
        ]);

        $response = $this->actingAs($user)
            ->get('/verify-email')
            ->assertOk()
            ->assertSee('Resend verification email')
            ->assertSee('Log out');

        $this->assertRendersCleanly($response);
    }

    public function test_confirm_password_page_renders(): void
    {
        $user = User::forceCreate([
            'name' => 'Plain User',
            'email' => 'plain@example.com',
            'password' => bcrypt('secret'),
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get('/confirm-password')
            ->assertOk()
            ->assertSee('Confirm');

        $this->assertRendersCleanly($response);
    }

    public function test_two_factor_challenge_renders_with_a_staged_login(): void
    {
        $response = $this->withSession(['ui-kit.login.id' => 1])
            ->get('/two-factor-challenge')
            ->assertOk()
            ->assertSee('Two-factor authentication')
            ->assertSee('Use a recovery code instead');

        $this->assertRendersCleanly($response);
    }

    public function test_profile_page_renders_its_four_sections(): void
    {
        $user = User::forceCreate([
            'name' => 'Plain User',
            'email' => 'plain@example.com',
            'password' => bcrypt('secret'),
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Profile Information')
            ->assertSee('Update Password')
            ->assertSee('Two-Factor Authentication')
            ->assertSee('Delete Account')
            // The delete confirmation is the design system's dialog, closed
            // until Alpine opens it; the kit's own x-modal is gone.
            ->assertSee('role="dialog"', false)
            ->assertSee('form="delete-account-form"', false)
            ->assertDontSee('<x-modal', false);

        $this->assertRendersCleanly($response);
    }

    public function test_avatar_is_saved_and_removed_on_a_stock_user_model(): void
    {
        Storage::fake('public');

        $user = User::forceCreate([
            'name' => 'Plain User',
            'email' => 'plain@example.com',
            'password' => bcrypt('secret'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $form = Livewire::test('profile.update-profile-information-form')
            ->set('avatar', UploadedFile::fake()->image('me.png', 64, 64))
            ->call('updateAvatar')
            ->assertHasNoErrors();

        $path = $user->fresh()->avatar_path;
        $this->assertNotNull($path, 'the photo was stored but avatar_path was never saved');
        Storage::disk('public')->assertExists($path);

        $form->call('deleteAvatar');

        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_two_factor_challenge_redirects_without_staged_login(): void
    {
        $this->get('/two-factor-challenge')->assertRedirect('/login');
    }

    public function test_admin_redirects_guests_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_admin_is_forbidden_for_regular_users(): void
    {
        $user = User::forceCreate([
            'name' => 'Plain User',
            'email' => 'plain@example.com',
            'password' => bcrypt('secret'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_dashboard_renders_for_admins(): void
    {
        $admin = User::forceCreate([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Total users')
            // The shell: rail, phone bar and "More" sheet, all through ds::.
            ->assertSee('aria-current="page"', false)
            ->assertSee('aria-label="Primary"', false)
            ->assertSee('aria-label="More"', false);

        $this->assertRendersCleanly($response);
    }

    public function test_admin_users_list_renders_for_admins(): void
    {
        $admin = User::forceCreate([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('admin@example.com');

        $this->assertRendersCleanly($response);
    }
}
