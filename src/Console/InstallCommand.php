<?php

namespace Shipbytes\UiKit\Console;

use Shipbytes\UiKit\Console\Concerns\InstallsModule;
use Shipbytes\UiKit\Support\ModuleRegistry;
use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

class InstallCommand extends Command
{
    use InstallsModule;

    protected $signature = 'ui-kit:install
                            {--force : Overwrite existing files without prompting}
                            {--modules= : Comma-separated module slugs to install non-interactively}';

    protected $description = 'Install the UI Kit core and optionally pick modules interactively.';

    public function handle(ModuleRegistry $registry): int
    {
        $this->ensurePromptsRender();

        info('UI Kit — core install');

        if (($abortCode = $this->preflightAuthConflicts()) !== null) {
            return $abortCode;
        }

        note("The following core pieces will be installed:\n  • Auth pages (login, register, verify, etc.)\n  • Admin shell (sidebar + mobile nav)\n  • Dashboard stub + Users CRUD");

        $this->publishCore();
        $this->configureFortify();

        $selected = $this->resolveSelectedModules($registry);

        foreach ($selected as $slug) {
            $this->call('ui-kit:install-module', [
                'module' => $slug,
                '--from-parent' => true,
            ]);
        }

        note("Next steps:\n  1. php artisan migrate\n  2. npm install && npm run dev\n  3. Add `require('shipbytes/laravel-ui-kit/tailwind-preset')` to tailwind.config.js");

        return self::SUCCESS;
    }

    protected function publishCore(): void
    {
        $tags = ['ui-kit-config', 'ui-kit-views', 'ui-kit-livewire', 'ui-kit-assets', 'ui-kit-routes', 'ui-kit-migrations'];

        foreach ($tags as $tag) {
            Artisan::call('vendor:publish', [
                '--tag' => $tag,
                '--force' => (bool) $this->option('force'),
            ]);
            $this->line("  ✓ published <info>{$tag}</info>");
        }
    }

    /**
     * Publish Fortify's config and disable its default view routes.
     *
     * The kit ships its own Volt-based login/register/etc. routes (in
     * routes/auth.php, auto-loaded by the service provider). If Fortify
     * also registers its default view routes, they collide on the same
     * paths and crash with "RegisterViewResponse is not instantiable"
     * because Fortify's view responses aren't bound without a published
     * FortifyServiceProvider.
     *
     * Flipping `views` to false tells Fortify to skip GET route
     * registration, leaving the kit's routes unopposed. Fortify still
     * registers its POST action routes (login/register/etc.) which the
     * kit doesn't use directly either (Livewire handles the form POSTs),
     * but those routes are harmless — they just sit there unused.
     */
    protected function configureFortify(): void
    {
        $path = config_path('fortify.php');

        if (! file_exists($path)) {
            Artisan::call('vendor:publish', [
                '--provider' => 'Laravel\\Fortify\\FortifyServiceProvider',
                '--tag' => 'fortify-config',
            ]);
            $this->line('  ✓ published <info>fortify-config</info>');
        }

        if (! file_exists($path)) {
            warning('Could not publish config/fortify.php. Set `views => false` there manually to avoid a /register 500.');

            return;
        }

        $contents = file_get_contents($path);

        if (str_contains($contents, "'views' => true")) {
            file_put_contents($path, str_replace("'views' => true", "'views' => false", $contents));
            $this->line('  ✓ patched <info>fortify.php</info> <comment>views=false</comment> (kit supplies its own auth routes)');

            return;
        }

        if (str_contains($contents, "'views' => false")) {
            $this->line('  ✓ <info>fortify.php</info> already has views=false');

            return;
        }

        warning("Couldn't find a `views` flag in config/fortify.php. Set it to false manually so Fortify doesn't collide with the kit's auth routes.");
    }

    /**
     * Detect auth scaffolding that would collide with this kit.
     *
     * Returns null to proceed, or a command exit code to abort with.
     */
    protected function preflightAuthConflicts(): ?int
    {
        $hasJetstream = $this->composerHas('laravel/jetstream');
        $hasBreeze = $this->composerHas('laravel/breeze');

        $fileCollisions = array_values(array_filter([
            'routes/auth.php',
            'app/Livewire/Forms/LoginForm.php',
            'resources/views/livewire/pages/auth/login.blade.php',
        ], fn (string $rel) => file_exists(base_path($rel))));

        if (! $hasJetstream && ! $hasBreeze && empty($fileCollisions)) {
            return null;
        }

        if ($hasJetstream) {
            error('Jetstream detected (laravel/jetstream is installed).');
            note("This kit and Jetstream cannot coexist — they both register /login, /register, etc., and Jetstream adds teams/API-tokens this kit doesn't understand.\n\nRecommended path: start from a fresh Laravel app, or fully remove Jetstream first.\nSee README → \"Before you install — fresh vs. existing app\".");

            if (! $this->option('force')) {
                return self::FAILURE;
            }

            warning('Proceeding because --force was passed. Expect route collisions at boot.');
        }

        if ($hasBreeze) {
            warning('Breeze detected (laravel/breeze is installed).');
            note("Breeze and this kit overlap on routes/auth.php, app/Livewire/Forms/LoginForm.php, and resources/views/livewire/pages/auth/*. Without --force, Laravel will silently skip those files and you'll end up with a mixed UI.\n\nRecommended: remove Breeze first (see README).");
        }

        if (! empty($fileCollisions)) {
            warning("Existing files will be affected:\n  • ".implode("\n  • ", $fileCollisions)."\n\nWithout --force they'll be skipped. With --force they'll be overwritten.");
        }

        if ($this->option('force')) {
            warning('--force is set: existing files WILL be overwritten.');

            return null;
        }

        if (! $this->input->isInteractive()) {
            error('Auth scaffolding detected and running non-interactively. Re-run with --force to override, or remove the conflicting setup first. Aborting.');

            return self::FAILURE;
        }

        if (! confirm(label: 'Continue anyway?', default: false, hint: 'Colliding files will be left untouched; the kit will only drop in the non-overlapping pieces.')) {
            info('Aborted. See README → "Before you install" for safe migration steps.');

            return self::FAILURE;
        }

        return null;
    }

    protected function composerHas(string $package): bool
    {
        if (! class_exists(InstalledVersions::class)) {
            return false;
        }

        try {
            return InstalledVersions::isInstalled($package);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<int, string>
     */
    protected function resolveSelectedModules(ModuleRegistry $registry): array
    {
        if ($this->option('modules') !== null) {
            $requested = array_filter(array_map('trim', explode(',', (string) $this->option('modules'))));
            $unknown = array_diff($requested, array_keys($registry->all()));

            if (! empty($unknown)) {
                $this->error('Unknown modules: '.implode(', ', $unknown));

                return [];
            }

            return array_values($requested);
        }

        if (! $this->input->isInteractive()) {
            return [];
        }

        $options = [];
        foreach ($registry->all() as $slug => $meta) {
            $options[$slug] = $meta['label'].' — '.$meta['summary'];
        }

        return multiselect(
            label: 'Select optional modules to install',
            options: $options,
            hint: 'Space to toggle, Enter to confirm. You can install more later with ui-kit:install-module.'
        );
    }
}
