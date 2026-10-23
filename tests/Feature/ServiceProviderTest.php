<?php

namespace Shipbytes\UiKit\Tests\Feature;

use Illuminate\Support\ServiceProvider;
use Shipbytes\UiKit\Contracts\SidebarBadgeResolver;
use Shipbytes\UiKit\Support\DefaultBadgeResolver;
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

    public function test_badge_resolver_defaults_to_the_kits_counter(): void
    {
        $resolver = $this->app->make(SidebarBadgeResolver::class);

        $this->assertInstanceOf(DefaultBadgeResolver::class, $resolver);
        // Neither module's model exists here, so there is nothing to count.
        $this->assertSame([], $resolver->counts());
        $this->assertSame($resolver, $this->app->make(SidebarBadgeResolver::class), 'one resolver, and one set of counts, per request');
    }

    public function test_an_apps_own_badge_resolver_wins(): void
    {
        // What an app's AppServiceProvider does; it registers after the kit.
        $this->app->bind(SidebarBadgeResolver::class, NullBadgeResolver::class);

        $this->assertInstanceOf(NullBadgeResolver::class, $this->app->make(SidebarBadgeResolver::class));
    }

    public function test_commands_are_registered(): void
    {
        $commands = array_keys($this->app->make('Illuminate\Contracts\Console\Kernel')->all());

        $this->assertContains('ui-kit:install', $commands);
        $this->assertContains('ui-kit:install-module', $commands);
        $this->assertContains('ui-kit:list-modules', $commands);
        $this->assertContains('ui-kit:doctor', $commands);
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

        $paths = ServiceProvider::pathsToPublish(null, null);
        $this->assertNotEmpty($paths);

        foreach ($tags as $tag) {
            $tagged = ServiceProvider::pathsToPublish(null, $tag);
            $this->assertNotEmpty($tagged, "Publish tag {$tag} has no registered paths");
        }
    }
}
