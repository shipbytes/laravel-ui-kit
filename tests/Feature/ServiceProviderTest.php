<?php

namespace Shipbytes\UiKit\Tests\Feature;

use Shipbytes\UiKit\Console\InstallCommand;
use Shipbytes\UiKit\Console\InstallModuleCommand;
use Shipbytes\UiKit\Console\ListModulesCommand;
use Shipbytes\UiKit\Contracts\SidebarBadgeResolver;
use Shipbytes\UiKit\Support\ModuleRegistry;
use Shipbytes\UiKit\Support\NullBadgeResolver;
use Shipbytes\UiKit\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_package_config_is_merged(): void
    {
        $this->assertIsArray(config('ui-kit'));
        $this->assertArrayHasKey('brand', config('ui-kit'));
    }

    public function test_module_registry_is_singleton(): void
    {
        $a = $this->app->make(ModuleRegistry::class);
        $b = $this->app->make(ModuleRegistry::class);

        $this->assertSame($a, $b);
    }

    public function test_badge_resolver_defaults_to_null_impl(): void
    {
        $resolver = $this->app->make(SidebarBadgeResolver::class);

        $this->assertInstanceOf(NullBadgeResolver::class, $resolver);
        $this->assertSame([], $resolver->counts());
    }

    public function test_commands_are_registered(): void
    {
        $commands = array_keys($this->app->make('Illuminate\Contracts\Console\Kernel')->all());

        $this->assertContains('ui-kit:install', $commands);
        $this->assertContains('ui-kit:install-module', $commands);
        $this->assertContains('ui-kit:list-modules', $commands);
    }

    public function test_publish_tags_are_registered(): void
    {
        $tags = [
            'ui-kit-config',
            'ui-kit-views',
            'ui-kit-livewire',
            'ui-kit-assets',
            'ui-kit-routes',
            'ui-kit-migrations',
        ];

        $paths = \Illuminate\Support\ServiceProvider::pathsToPublish(null, null);
        $this->assertNotEmpty($paths);

        foreach ($tags as $tag) {
            $tagged = \Illuminate\Support\ServiceProvider::pathsToPublish(null, $tag);
            $this->assertNotEmpty($tagged, "Publish tag {$tag} has no registered paths");
        }
    }
}
