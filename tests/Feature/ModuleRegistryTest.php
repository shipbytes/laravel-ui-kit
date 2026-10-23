<?php

namespace Shipbytes\UiKit\Tests\Feature;

use Shipbytes\UiKit\Support\ModuleRegistry;
use Shipbytes\UiKit\Tests\TestCase;

class ModuleRegistryTest extends TestCase
{
    public function test_known_modules_resolve(): void
    {
        $expected = [
            'admin-middleware', 'support-tickets', 'changelog', 'contacts',
            'profile', 'impersonation', 'activity-log',
        ];

        $registry = $this->app->make(ModuleRegistry::class);

        $this->assertSame($expected, array_keys($registry->all()));

        foreach ($expected as $slug) {
            $this->assertTrue($registry->has($slug), "Module {$slug} should be registered");

            $meta = $registry->get($slug);
            $this->assertArrayHasKey('label', $meta);
            $this->assertArrayHasKey('summary', $meta);
        }
    }

    public function test_impersonation_module_registers_the_package_routes(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);

        // lab404/laravel-impersonate ships its routes as a macro; the installer
        // has to call it, under auth, or the partials link to nothing.
        $this->assertSame(['Route::impersonate();'], $registry->get('impersonation')['user_routes']);
    }

    public function test_stub_directories_exist_for_each_module(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);
        $base = __DIR__.'/../../stubs/modules';

        foreach (array_keys($registry->all()) as $slug) {
            $this->assertDirectoryExists($base.'/'.$slug, "Expected stubs/modules/{$slug} to exist");
        }
    }

    /**
     * A route line or a mailable the registry names is something the installer
     * promises, so the stub behind it has to ship with the module, and the
     * doctor's routes-registered row has to be able to read its name.
     */
    public function test_every_class_the_registry_names_ships_in_its_module(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);
        $base = realpath(__DIR__.'/../../stubs/modules');

        foreach ($registry->all() as $slug => $meta) {
            foreach (['admin_routes', 'user_routes', 'public_routes'] as $key) {
                foreach ((array) ($meta[$key] ?? []) as $line) {
                    if (! preg_match('/\\\\App\\\\Livewire\\\\([\\w\\\\]+)::class/', $line, $m)) {
                        continue;
                    }

                    $this->assertFileExists("{$base}/{$slug}/Livewire/".str_replace('\\', '/', $m[1]).'.php', "{$slug} {$key}: {$line}");
                    $this->assertMatchesRegularExpression("/->name\\('[^']+'\\)/", $line, "{$slug} {$key} needs a route name for the doctor: {$line}");
                }
            }

            foreach ((array) ($meta['mail'] ?? []) as $class => $view) {
                $this->assertFileExists("{$base}/{$slug}/Mail/".class_basename($class).'.php', "{$slug} mail: {$class}");
                $this->assertFileExists("{$base}/{$slug}/views/".str_replace('.', '/', $view).'.blade.php', "{$slug} mail view: {$view}");
                $this->assertStringContainsString("'{$view}'", (string) file_get_contents("{$base}/{$slug}/Mail/".class_basename($class).'.php'), "{$class} renders {$view}");
            }
        }
    }

    public function test_support_create_is_declared_before_the_ticket_route(): void
    {
        // Otherwise /support/create is read as the ticket whose ulid is "create".
        $lines = $this->app->make(ModuleRegistry::class)->get('support-tickets')['user_routes'];
        $names = array_map(fn ($line) => preg_match("/->name\\('([^']+)'\\)/", $line, $m) ? $m[1] : null, $lines);

        $this->assertLessThan(array_search('support.show', $names, true), array_search('support.create', $names, true));
    }

    public function test_unknown_module_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->app->make(ModuleRegistry::class)->get('does-not-exist');
    }
}
