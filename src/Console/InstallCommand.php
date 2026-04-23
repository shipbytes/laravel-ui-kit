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
        info('UI Kit — core install');

        if (($abortCode = $this->preflightAuthConflicts()) !== null) {
            return $abortCode;
        }

        note("The following core pieces will be installed:\n  • Auth pages (login, register, verify, etc.)\n  • Admin shell (sidebar + mobile nav)\n  • Dashboard stub + Users CRUD");

        $this->publishCore();

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
