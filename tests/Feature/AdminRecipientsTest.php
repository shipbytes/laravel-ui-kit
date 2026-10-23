<?php

namespace Shipbytes\UiKit\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Shipbytes\UiKit\Support\UiKit;
use Shipbytes\UiKit\Tests\Fixtures\FlagAdminUser;
use Shipbytes\UiKit\Tests\Fixtures\RoleAdminUser;
use Shipbytes\UiKit\Tests\TestCase;

/**
 * UiKit::admins(): who gets the mail meant for the site's admins.
 */
class AdminRecipientsTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->string('role')->nullable();
            $table->timestamps();
        });

        foreach ([
            ['Ada', 'ada@example.com', true, null],
            ['Grace', 'grace@example.com', false, 'admin'],
            ['Linus', 'linus@example.com', false, 'user'],
        ] as [$name, $email, $isAdmin, $role]) {
            FlagAdminUser::create(['name' => $name, 'email' => $email, 'password' => 'x', 'is_admin' => $isAdmin, 'role' => $role]);
        }
    }

    public function test_without_roles_the_admins_are_the_is_admin_users(): void
    {
        config(['auth.providers.users.model' => FlagAdminUser::class]);

        $this->assertSame(['ada@example.com'], UiKit::admins()->pluck('email')->all());
    }

    public function test_with_roles_the_admins_hold_the_admin_role(): void
    {
        config(['auth.providers.users.model' => RoleAdminUser::class]);

        // The role decides, not the column: Ada's is_admin does not count here.
        $this->assertSame(['grace@example.com'], UiKit::admins()->pluck('email')->all());
    }

    public function test_configured_addresses_replace_the_admin_users(): void
    {
        config([
            'auth.providers.users.model' => FlagAdminUser::class,
            'ui-kit.mail.admin_addresses' => ['support@example.com', '', 'ops@example.com'],
        ]);

        $this->assertSame(['support@example.com', 'ops@example.com'], UiKit::admins()->all());
    }

    public function test_the_default_config_lists_no_addresses(): void
    {
        $this->assertSame([], config('ui-kit.mail.admin_addresses'));
        $this->assertCount(5, config('ui-kit.contacts.subjects'));
    }
}
