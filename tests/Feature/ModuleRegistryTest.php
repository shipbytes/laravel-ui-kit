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

    public function test_stub_directories_exist_for_each_module(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);
        $base = __DIR__.'/../../stubs/modules';

        foreach (array_keys($registry->all()) as $slug) {
            $this->assertDirectoryExists($base.'/'.$slug, "Expected stubs/modules/{$slug} to exist");
        }
    }

    public function test_unknown_module_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->app->make(ModuleRegistry::class)->get('does-not-exist');
    }
}
